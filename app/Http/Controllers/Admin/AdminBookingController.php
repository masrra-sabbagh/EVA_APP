<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBookingController extends Controller {
    public function __construct(protected WalletService $walletService) {
    }

    public function index(Request $request) {
        $query = Booking::with(['user', 'services', 'event']);

        // فلترة حسب الحالة
        if ($request->has('status')) {
            $query->where('booking_status', $request->status);
        }

        // فلترة حسب التاريخ
        if ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        $bookings = $query->get();

        // إحصائيات سريعة
        $statistics = [
            'total' => Booking::count(),
            'pending' => Booking::where('booking_status', 'pending')->count(),
            'confirmed' => Booking::where('booking_status', 'confirmed')->count(),
            'cancelled' => Booking::where('booking_status', 'cancelled')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'All bookings retrieved successfully',
            'statistics' => $statistics,
            'data' => $bookings
        ]);
    }

    /**
     * عرض تفاصيل حجز معين
     */
    public function show($id) {
        $booking = Booking::with(['user', 'services', 'event'])
            ->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking details retrieved successfully',
            'data' => $booking
        ]);
    }

    /**
     * عرض الحجوزات المعلقة فقط
     */
    public function pendingBookings() {
        $bookings = Booking::where('booking_status', 'pending')
            ->with(['user', 'services', 'event'])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Pending bookings retrieved successfully',
            'data' => $bookings
        ]);
    }

    /**
     *  عرض الحجوزات المؤكدة فقط من قبل مزود الخدمة
     */
    public function confirmedBookings() {
        $bookings = Booking::where('booking_status', 'confirmed')
            ->with(['user', 'services', 'event'])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Confirmed bookings retrieved successfully',
            'data' => $bookings
        ]);
    }

    /**
     * عرض الحجوزات الملغاة فقط
     */
    public function cancelledBookings() {
        $bookings = Booking::where('booking_status', 'cancelled')
            ->with(['user', 'services', 'event'])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Cancelled bookings retrieved successfully',
            'data' => $bookings
        ]);
    }
    // يشوف الحجوزات اللي بانتظار تأكيده
    public function pendingCompletions() {
        $bookings = Booking::with(['user', 'services.provider'])
            ->where('booking_status', 'pending_completion')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $bookings]);
    }

    // يؤكد ويوزع المبالغ
    public function completeBooking(Booking $booking) {
        if ($booking->booking_status !== 'pending_completion') {
            return response()->json(['success' => false, 'message' => 'Booking is not pending completion.'], 422);
        }

        $commissionRate   = (float) DB::table('settings')->where('key', 'commission_rate')->value('value');
        $heldAmount       = $booking->paid_amount;
        $commissionAmount = $heldAmount * ($commissionRate / 100);
        $providerAmount   = $heldAmount - $commissionAmount;

        $userWallet     = $booking->user->wallet;
        $provider       = $booking->services->first()->provider;
        $providerWallet = $provider->wallet;

        DB::transaction(function () use ($booking, $userWallet, $providerWallet, $heldAmount, $providerAmount) {
            $this->walletService->forfeitHold($userWallet, $heldAmount, $booking);
            $this->walletService->credit($providerWallet, 'payout', $providerAmount, $booking);
            $booking->update(['booking_status' => 'completed']);
        });

        return response()->json([
            'success'         => true,
            'message'         => 'Booking completed and funds distributed.',
            'held_amount'     => $heldAmount,
            'commission'      => $commissionAmount,
            'provider_payout' => $providerAmount,
        ]);
    }
}
