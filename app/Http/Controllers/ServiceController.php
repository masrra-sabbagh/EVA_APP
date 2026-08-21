<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller {

    public function myServices(Request $request) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }

        $services = Service::where('userId', $user->id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Your services retrieved successfully',
            'data' => $services
        ]);
    }

    public function index(Request $request) {
        try {
            $validated = $request->validate([
                'category' => 'required|in:dj,decoration,lighting,photography',
                'location' => 'required|string|max:255'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        }

        $services = Service::where('category', $validated['category'])
            ->where('is_available', true)
            ->where('location', 'LIKE', '%' . $validated['location'] . '%')
            ->get();

        return response()->json([
            'success' => true,
            'message' => "Services in {$validated['category']} category in {$validated['location']} retrieved successfully",
            'data' => $services
        ]);
    }

    public function store(Request $request) {
        // نجلب المستخدم من التوكن مباشرة
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }
        try {
            $validated = $request->validate([
                'category' => 'required|in:dj,decoration,lighting,photography',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price_per_day' => 'required|numeric|min:0',
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
                'main_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'location' => 'required|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        }
        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('service_image', 'public');
            $validated['main_image'] = $path;
        } else {
            return response()->json([
                'message' => 'This field is required.',
                'errors' => [
                    'main_image' => ['The photo is required']
                ]
            ], 422);
        }

        $validated['userId'] = $user->id;
        $service = Service::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully',
            'data' => $service
        ], 201);
    }

    public function update(Request $request, $id) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }

        $service = Service::where('id', $id)
            ->where('userId', $user->id)
            ->first();

        if (!$service) {
            return response()->json([
                'message' => 'Service not found.',
                'errors' => [
                    'service' => ['Service not found or you do not own this service']
                ]
            ], 404);
        }

        try {
            $validated = $request->validate([
                'category' => 'sometimes|in:dj,decoration,lighting,photography',
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'price_per_day' => 'sometimes|numeric|min:0',
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
                'location' => 'sometimes|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        }

        if ($request->hasFile('main_image')) {
            $image = $request->file('main_image');
            $main_imageName = time() . '_main.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $main_imageName);
            $validated['main_image'] = 'images/' . $main_imageName;
        }

        $service->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully',
            'data' => $service->fresh()
        ]);
    }

    public function toggleAvailability(Request $request, $id) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }

        $service = Service::where('id', $id)
            ->where('userId', $user->id)
            ->first();

        if (!$service) {
            return response()->json([
                'message' => 'Service not found.',
                'errors' => [
                    'service' => ['Service not found or you do not own this service']
                ]
            ], 404);
        }

        $service->update([
            'is_available' => !$service->is_available
        ]);

        $status = $service->is_available ? 'available' : 'hidden';

        return response()->json([
            'success' => true,
            'message' => "Service is now {$status}",
            'data' => $service
        ]);
    }
}
