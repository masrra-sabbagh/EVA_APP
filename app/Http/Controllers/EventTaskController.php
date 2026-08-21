<?php

namespace App\Http\Controllers;

use App\Models\EventTask;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventTaskController extends Controller {
    /**
     * عرض مهام فعالية معينة مع نسبة الإنجاز
     */
    public function index(Request $request, $eventId) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }

        $event = Event::where('id', $eventId)
            ->where('userId', $user->id)
            ->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }

        $tasks = EventTask::where('eventId', $eventId)
            ->orderBy('due_date', 'asc')
            ->get();

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('is_completed', true)->count();
        $progressPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'message' => 'Tasks retrieved successfully',
            'data' => [
                'event' => $event,
                'tasks' => $tasks,
                'progress' => [
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'percentage' => $progressPercentage,
                ]
            ]
        ]);
    }

    /**
     * إنشاء مهمة جديدة
     */
    public function store(Request $request, $eventId) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }

        $event = Event::where('id', $eventId)
            ->where('userId', $user->id)
            ->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'task_name' => 'required|string|max:255',
                'due_date' => 'required|date_format:Y-m-d H:i:s|after:now',
                'priority' => 'required|in:high,medium,normal',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        }

        $task = EventTask::create([
            'task_name' => $validated['task_name'],
            'due_date' => $validated['due_date'],
            'priority' => $validated['priority'],
            'is_completed' => false,
            'eventId' => $eventId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $task
        ], 201);
    }

    /**
     * تعديل مهمة
     */
    public function update(Request $request, $taskId) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }

        $task = EventTask::where('taskId', $taskId)
            ->whereHas('event', function ($query) use ($user) {
                $query->where('userId', $user->id);
            })
            ->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'task_name' => 'sometimes|string|max:255',
                'due_date' => 'sometimes|date_format:Y-m-d H:i:s|after:now',
                'priority' => 'sometimes|in:high,medium,normal',
                'is_completed' => 'sometimes|boolean',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        }

        $task->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => $task->fresh()
        ]);
    }

    /**
     * حذف مهمة
     */
    public function destroy(Request $request, $taskId) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }

        $task = EventTask::where('taskId', $taskId)
            ->whereHas('event', function ($query) use ($user) {
                $query->where('userId', $user->id);
            })
            ->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully'
        ]);
    }

    /**
     * تبديل حالة المهمة
     */
    public function toggleCompletion(Request $request, $taskId) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'auth' => ['Invalid or expired token']
                ]
            ], 401);
        }

        $task = EventTask::where('taskId', $taskId)
            ->whereHas('event', function ($query) use ($user) {
                $query->where('userId', $user->id);
            })
            ->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }

        $task->update([
            'is_completed' => !$task->is_completed
        ]);

        return response()->json([
            'success' => true,
            'message' => $task->is_completed ? 'Task marked as completed' : 'Task marked as incomplete',
            'data' => $task
        ]);
    }
}
