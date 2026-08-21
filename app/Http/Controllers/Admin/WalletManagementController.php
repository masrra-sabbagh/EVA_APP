<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransactionRequest;
use App\Services\Api\NotificationService;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletManagementController extends Controller {
    public function __construct(protected WalletService $walletService) {
    }
    public function pendingDeposits() {
        $requests = TransactionRequest::with('wallet.user')
            ->where('type', 'deposit')
            ->where('status', 'pending')
            ->latest()
            ->get();
        return response()->json($requests);
    }
    public function pendingWithdrawals() {
        $requests = TransactionRequest::with('wallet.user')
            ->where('type', 'withdraw')
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json($requests);
    }
    public function approveDeposit(TransactionRequest $transactionRequest) {
        if ($transactionRequest->status !== 'pending') {
            return response()->json([
                'message' => 'This request is not pending.',
            ], 422);
        }
        if ($transactionRequest->type !== 'deposit') {
            return response()->json([
                'message' => 'This is not a deposit request.',
            ], 422);
        }
        $this->walletService->credit(
            $transactionRequest->wallet,
            'deposit',
            $transactionRequest->amount,
            $transactionRequest
        );
        $transactionRequest->update(['status' => 'approved']);

        // TODO: إشعار لليوزر إنه رصيده اتعبأ
        // app(NotificationService::class)->send(
        //     $wallet->user,
        //     'Deposit Approved',
        //     "Your deposit of {$transactionRequest->amount} has been approved. New balance: {$wallet->fresh()->balance}",
        //     'deposit_approved'
        // );

        return response()->json([
            'message' => 'Deposit approved.',
            'new_balance' => $transactionRequest->wallet->fresh()->balance,
        ]);
    }

    public function rejectDeposit(Request $request, TransactionRequest $transactionRequest) {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($transactionRequest->status !== 'pending') {
            return response()->json([
                'message' => 'This request is not pending.',
            ], 422);
        }
        $transactionRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        // TODO: إشعار لليوزر إنه طلبه انرفض
        // app(NotificationService::class)->send(
        //     $transactionRequest->wallet->user,
        //     'Deposit Rejected',
        //     $request->rejection_reason,
        //     'deposit_rejected'
        // );

        return response()->json([
            'message' => 'Deposit rejected.',
        ], 200);
    }

    public function approveWithdraw(TransactionRequest $transactionRequest) {
        if ($transactionRequest->status !== 'pending' || $transactionRequest->type !== 'withdraw') {
            return response()->json(['message' => 'Invalid request.'], 422);
        }
        // ملاحظة: هون الأدمن لازم يكون فعلياً حول المصاري يدوياً
        // (متل الإيداع بالضبط بس بالاتجاه المعاكس) قبل ما يضغط approve
        $this->walletService->debit(
            $transactionRequest->wallet,
            'withdraw',
            $transactionRequest->amount,
            $transactionRequest
        );

        $transactionRequest->update(['status' => 'approved']);

        return response()->json([
            'message' => 'Withdrawal approved.',
            'new_balance' => $transactionRequest->wallet->fresh()->balance,
        ]);
    }
}
