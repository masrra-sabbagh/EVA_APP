<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller {
    public function index(Request $request) {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $query->where('user_name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('role_type', $request->role);
            });
        }

        if ($request->has('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        $users = $query->latest()->paginate(15);

        return response()->json($users);
    }

    public function show(User $user) {
        return response()->json(
            $user->load([
                'roles',
                'providerRequests',
                // (هاجر/آية): لما تبنوا موديلات Wallet, Booking, Event
                // أضيفوا هون العلاقات المتعلقة فيهم:
                // 'wallet',
                // 'bookings',
                // 'events',
            ])
        );
    }

    public function destroy(User $user) {
        $user->delete();
        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
