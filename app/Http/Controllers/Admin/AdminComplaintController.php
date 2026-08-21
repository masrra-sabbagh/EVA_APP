<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Complaint;
use Illuminate\Http\Request;

class AdminComplaintController extends Controller {
    /**
     * عرض كل التقييمات
     */
    public function allReviews() {
        $reviews = Review::with(['user', 'service'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'All reviews retrieved successfully',
            'data' => $reviews
        ]);
    }

    /**
     * حذف تقييم
     */
    public function deleteReview($reviewId) {
        $review = Review::find($reviewId);

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

    /**
     * عرض كل الشكاوى مع معلومات المزود
     */
    public function allComplaints() {
        $complaints = Complaint::with(['user', 'provider', 'booking'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'All complaints retrieved successfully',
            'data' => $complaints
        ]);
    }

    /**
     * حذف شكوى
     */
    public function deleteComplaint($complaintId) {
        $complaint = Complaint::find($complaintId);

        if (!$complaint) {
            return response()->json([
                'success' => false,
                'message' => 'Complaint not found'
            ], 404);
        }

        $complaint->delete();

        return response()->json([
            'success' => true,
            'message' => 'Complaint deleted successfully'
        ]);
    }
}
