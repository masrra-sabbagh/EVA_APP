<?php

namespace App\Http\Controllers;

use App\Models\TransactionRequest;
use Illuminate\Http\Request;

class WalletController extends Controller {
    public function show(Request $request) {
        $wallet = $request->user()->wallet;

        $wallet->load('transactionRequests');

        return response()->json($wallet);
    }

    public function requestDeposit(Request $request) {
        $validated = $request->validate([
            'amount'           => 'required|numeric|min:1',
            'transfer_number'  => 'required|string|max:100',
            'receipt_image'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $wallet = $request->user()->wallet;

        if ($request->hasFile('receipt_image')) {
            $validated['receipt_image'] = $request->file('receipt_image')->store('receipts', 'public');
        }

        $transactionRequest = TransactionRequest::create([
            'amount'          => $validated['amount'],
            'type'            => 'deposit',
            'transfer_number' => $validated['transfer_number'],
            'receipt_image'   => $validated['receipt_image'] ?? null,
            'status'          => 'pending',
            'wallet_id'       => $wallet->id,
        ]);

        return response()->json([
            'message' => 'Deposit request submitted. Waiting for admin approval.',
            'request' => $transactionRequest,
        ], 201);
    }
}
