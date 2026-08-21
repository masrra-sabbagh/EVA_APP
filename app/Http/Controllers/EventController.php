<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Enums\CityAreaEnum;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class EventController extends Controller {
    /**
     * عرض فعالياتي (التي أنشأتها)
     */
    public function index(Request $request) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }

        $events = Event::where('userId', $user->id)
            ->with('tasks')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Your events retrieved successfully',
            'data' => $events
        ]);
    }

    /**
     * إنشاء فعالية جديدة
     */
    public function storeDynamic(Request $request) {
        try {
            $validated = $request->validate([
                'description' => 'required|string',
                'category' => 'required|in:wedding,birthday,party,meeting,concert',
                'city' => ['required', 'string', Rule::in(CityAreaEnum::countries())],
                'area' => ['required', 'string', Rule::in(CityAreaEnum::allCities())],
                'start_date' => 'required|date_format:Y-m-d|after:today',
                'end_date'   => 'required|date_format:Y-m-d|after:today',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        }
        $event = Event::create([
            'description' => $validated['description'],
            'nature' => 'dynamic',
            'category' => $validated['category'],
            'city' => $validated['city'],
            'area' => $validated['area'],
            'start_date'  => $validated['start_date'],
            'end_date'    => $validated['end_date'],
            'userId' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'data' => $event
        ], 201);
    }

    /**
     * عرض تفاصيل فعالية
     */
    public function show(Request $request, $id) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }

        $event = Event::where('id', $id)
            ->where('userId', $user->id)
            ->with(['tasks', 'bookings'])
            ->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }
}
