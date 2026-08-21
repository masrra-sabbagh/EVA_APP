<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\Request;

class ReviewController extends Controller {

    public function serviceReviews($serviceId) {
        $reviews = Review::where('service_id', $serviceId)
            ->with('user:id,user_name,profile_image')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Reviews retrieved successfully',
            'data' => $reviews
        ]);
    }

    public function store(Request $request) {
        $user = $request->user();

        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // التحقق من أن المستخدم قد حجز هذه الخدمة من قبل
        $hasBooked = Booking::where('userId', $user->id)
            ->where('booking_status', 'confirmed')
            ->whereHas('services', function ($query) use ($validated) {
                $query->where('services.id', $validated['service_id']);
            })
            ->exists();

        if (!$hasBooked) {
            return response()->json([
                'success' => false,
                'message' => 'You can only review services you have booked'
            ], 403);
        }

        // التحقق من عدم وجود تقييم مسبق
        $existingReview = Review::where('service_id', $validated['service_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this service'
            ], 403);
        }

        $review = Review::create([
            'service_id' => $validated['service_id'],
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully',
            'data' => $review
        ], 201);
    }

    public function update(Request $request, $id) {
        $user = $request->user();

        $review = Review::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found'
            ], 404);
        }

        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully',
            'data' => $review->fresh()
        ]);
    }

    public function destroy(Request $request, $id) {
        $user = $request->user();

        $review = Review::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found'
            ], 404);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    }
}
