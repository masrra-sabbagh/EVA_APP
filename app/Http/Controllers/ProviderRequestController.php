<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProviderRequestRequest;
use App\Models\ProviderRequest;
use App\Models\Role;
use App\Services\Api\NotificationService;
use Illuminate\Http\Request;

class ProviderRequestController extends Controller {
    public function store(StoreProviderRequestRequest $request) {
        $user = $request->user();
        $hasApproved = $user->providerRequests()->where('request_status', 'approved')->exists();

        if ($hasApproved) {
            return response()->json([
                'message' => 'You are already an approved provider.',
            ], 422);
        }
        $hasPending = $user->providerRequests()->where('request_status', 'pending')->exists();

        if ($hasPending) {
            return response()->json([
                'message' => 'You already have a pending request. Please wait for admin review.',
            ], 422);
        }

        $idCardPath    = $request->file('id_card')->store('provider_requests', 'public');
        $ownershipPath = $request->file('ownership')->store('provider_requests', 'public');

        $providerRequest = ProviderRequest::create([
            'user_id'            => $user->id,
            'id_card'            => $idCardPath,
            'ownership'          => $ownershipPath,
            'has_accepted_terms' => true,
            'request_status'     => 'pending',
        ]);

        return response()->json([
            'message' => 'Provider request submitted successfully. Waiting for admin approval.',
            'request' => $providerRequest,
        ], 201);
    }

    public function myRequest(Request $request) {
        $providerRequest = $request->user()
            ->providerRequests()
            ->latest()
            ->first();

        if (!$providerRequest) {
            return response()->json(['message' => 'No provider request found.'], 404);
        }

        return response()->json($providerRequest);
    }

    public function pendingRequests() {
        $requests = ProviderRequest::with('user')
            ->where('request_status', 'pending')
            ->latest()
            ->get();

        return response()->json($requests);
    }

    public function approve(ProviderRequest $providerRequest) {
        if ($providerRequest->request_status !== 'pending') {
            return response()->json([
                'message' => 'This request is not pending.',
            ], 422);
        }

        $providerRequest->update([
            'request_status' => 'approved',
        ]);

        $providerRoleId = Role::where('role_type', 'provider')->value('id');
        $providerRequest->user->roles()->syncWithoutDetaching([$providerRoleId]);
        /*   app(NotificationService::class)->send(
            $providerRequest->user,
            'Provider Request Approved',
            'Congratulations! Your provider account has been approved.',
            'provider_request_approved'
        );*/
        return response()->json([
            'message' => 'Provider request approved.',
        ], 200);
    }
    public function reject(Request $request, ProviderRequest $providerRequest) {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($providerRequest->request_status !== 'pending') {
            return response()->json(['message' => 'This request is not pending.'], 422);
        }

        $providerRequest->update(['request_status' => 'rejected']);
        /*  app(NotificationService::class)->send(
            $providerRequest->user,
            'Provider Request Rejected',
            $request->rejection_reason,
            'provider_request_rejected'
        );*/
        return response()->json(['message' => 'Provider request rejected.'], 200);
    }
}
