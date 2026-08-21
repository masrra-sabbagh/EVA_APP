<?php

namespace App\Services;

use App\Models\Notification as NotificationModel;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class NotificationService {
    public function index() {
        return auth()->user()->notifications;
    }

    /**

     * @param User $user
     * @param string $title
     * @param string $message
     * @param string $type نوع الإشعار: booking_confirmed, provider_status_changed, provider_request_status, task_reminder, admin_broadcast...
     */
    public function send(User $user, string $title, string $message, string $type = 'general') {
        // لو المستخدم ما عنده fcm_token، بنخزن الإشعار بس بدون push فعلي
        if (empty($user->fcm_token)) {
            Log::warning('No FCM token for user, saving notification without push.', [
                'user_id' => $user->id,
            ]);

            return $this->saveNotificationOnly($user, $title, $message, $type);
        }

        $serviceAccountPath = storage_path('app/firebase/firebase-credentials.json');

        $factory = (new Factory)->withServiceAccount($serviceAccountPath);
        $messaging = $factory->createMessaging();

        $notification = [
            'title' => $title,
            'body'  => $message,
            'sound' => 'default',
        ];

        $data = [
            'type'    => $type,
            'id'      => (string) $user->id,
            'message' => $message,
        ];

        $cloudMessage = CloudMessage::withTarget('token', $user->fcm_token)
            ->withNotification($notification)
            ->withData($data);

        try {
            $messaging->send($cloudMessage);

            return $this->saveNotificationOnly($user, $title, $message, $type);
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            Log::error('FCM MessagingException: ' . $e->getMessage());
            return 0;
        } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
            Log::error('FCM FirebaseException: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * حفظ الإشعار بقاعدة البيانات فقط (بدون push)، مفيدة لو ما في fcm_token
     * أو كخطوة منفصلة بعد نجاح الإرسال
     */
    private function saveNotificationOnly(User $user, string $title, string $message, string $type): int {
        NotificationModel::query()->create([
            'type'            => $type,
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'data'            => json_encode([
                'title'   => $title,
                'message' => $message,
                'user'    => $user->user_name,
            ]),
        ]);

        return 1;
    }

    public function markAsRead($notificationId): bool {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);

        $notification->markAsRead();
        return true;
    }

    public function destroy($id): bool {
        $notification = auth()->user()->notifications()->findOrFail($id);

        $notification->delete();
        return true;
    }
}
