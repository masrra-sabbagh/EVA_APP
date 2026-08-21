<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminProviderController extends Controller {
    public function index(Request $request) {
        $providers = User::whereHas('roles', function ($query) {
            $query->where('role_type', 'provider');
        })
            ->with('services')
            ->get();

        $providers->each(function ($provider) {
            $provider->services_count = $provider->services->count();
            $provider->bookings_count = $provider->bookings()->count();
        });

        return response()->json([
            'success' => true,
            'message' => 'All providers retrieved successfully',
            'data' => $providers
        ]);
    }

    public function show($id) {
        $provider = User::whereHas('roles', function ($query) {
            $query->where('role_type', 'provider');
        })
            ->with(['services', 'services.bookings', 'services.reviews'])
            ->find($id);

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'Provider not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Provider details retrieved successfully',
            'data' => $provider
        ]);
    }

    public function suspend($id) {
        $provider = User::whereHas('roles', function ($query) {
            $query->where('role_type', 'provider');
        })->find($id);

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'Provider not found'
            ], 404);
        }

        $provider->services()->update([
            'is_available' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Provider suspended successfully. All services are now hidden.',
            'data' => $provider
        ]);
    }

    public function activate($id) {
        $provider = User::whereHas('roles', function ($query) {
            $query->where('role_type', 'provider');
        })->find($id);

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'Provider not found'
            ], 404);
        }

        $provider->services()->update([
            'is_available' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Provider activated successfully. All services are now visible.',
            'data' => $provider
        ]);
    }

    public function destroy($id) {
        $provider = User::whereHas('roles', function ($query) {
            $query->where('role_type', 'provider');
        })->find($id);

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'Provider not found'
            ], 404);
        }

        $provider->services()->delete();
        $provider->delete();

        return response()->json([
            'success' => true,
            'message' => 'Provider deleted successfully'
        ]);
    }
}
