<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;

class FavoriteController extends Controller {

    public function myFavorites(Request $request) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }

        $favorites = $user->favoriteServices()
            ->where('is_available', true)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Your favorite services retrieved successfully',
            'data' => $favorites
        ]);
    }

    public function addToFavorites(Request $request, $id) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }

        $service = Service::find($id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found'
            ], 404);
        }

        if ($user->favoriteServices()->where('service_id', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Service already in favorites'
            ], 400);
        }

        $user->favoriteServices()->attach($service->id);

        return response()->json([
            'success' => true,
            'message' => 'Service added to favorites successfully',
            'data' => $service
        ], 201);
    }

    public function removeFromFavorites(Request $request, $id) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }

        $service = Service::find($id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found'
            ], 404);
        }

        if (!$user->favoriteServices()->where('service_id', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Service not in favorites'
            ], 400);
        }

        $user->favoriteServices()->detach($service->id);

        return response()->json([
            'success' => true,
            'message' => 'Service removed from favorites successfully'
        ]);
    }
}
