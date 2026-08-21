<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Booking;
use Illuminate\Http\Request;

class ComplaintController extends Controller {
    /**
     * عرض شكاوي المستخدم الحالي
     */
    public function myComplaints(Request $request) {
        $user = $request->user();

        $complaints = Complaint::where('user_id', $user->id)
            ->with(['booking', 'provider'])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Your complaints retrieved successfully',
            'data' => $complaints
        ]);
    }

    /**
     * عرض الشكاوي الواردة لمزود الخدمة
     */
    public function receivedComplaints(Request $request) {
        $user = $request->user();

        $complaints = Complaint::where('provider_id', $user->id)
            ->with(['booking', 'user'])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Received complaints retrieved successfully',
            'data' => $complaints
        ]);
    }

    /**
     * إنشاء شكوى جديدة
     */
    public function store(Request $request) {
        $user = $request->user();

        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'content' => 'required|string|max:2000',
        ]);

        // جلب الحجز
        $booking = Booking::where('id', $validated['booking_id'])
            ->where('userId', $user->id)
            ->with('services')
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or you are not authorized'
            ], 404);
        }

        // ✅ حساب provider_id من الخدمة المرتبطة بالحجز
        $providerId = null;

        if ($booking->services->isNotEmpty()) {
            $providerId = $booking->services->first()->userId;
        }

        if (!$providerId) {
            return response()->json([
                'success' => false,
                'message' => 'No provider found for this booking'
            ], 404);
        }

        // التحقق من عدم وجود شكوى مسبقة على نفس الحجز
        $existingComplaint = Complaint::where('booking_id', $validated['booking_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($existingComplaint) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted a complaint for this booking'
            ], 400);
        }

        $complaint = Complaint::create([
            'user_id' => $user->id,
            'booking_id' => $validated['booking_id'],
            'provider_id' => $providerId,
            'content' => $validated['content'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Complaint created successfully',
            'data' => $complaint
        ], 201);
    }
}
