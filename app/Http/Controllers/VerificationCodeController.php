<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VerificationCode;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

class VerificationCodeController extends Controller {
    protected $whatsappService;
    public function __construct(WhatsappService $whatsappService) {
        $this->whatsappService = $whatsappService;
    }

    public function generateAndSend(User $user, string $type = 'phone_verification'): VerificationCode {
        VerificationCode::where('user_id', $user->id)
            ->where('type', $type)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        $otpCode = (string) rand(1000, 9999);

        $verification = VerificationCode::create([
            'code'       => $otpCode,
            'type'       => $type,
            'is_used'    => false,
            'expired_at' => now()->addMinutes(3),
            'user_id'    => $user->id,
        ]);

        $message = "Your verification code is: {$otpCode}";
        $this->whatsappService->sendOtp($user->phone_number, $message);

        return $verification;
    }

    public function verify(Request $request) {
        $request->validate([
            'code' => 'required|string',
            'type' => 'required|string',
        ]);

        $verification = VerificationCode::where('user_id', $request->user()->id)
            ->where('type', $request->type)
            ->where('code', $request->code)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$verification) {
            return response()->json(['message' => 'Invalid verification code'], 422);
        }

        if ($verification->isExpired()) {
            return response()->json(['message' => 'Verification code has expired'], 422);
        }

        $verification->update(['is_used' => true]);

        if ($verification->type === 'phone_verification') {
            $verification->user()->update(['is_verified' => true]);
        } elseif ($verification->type === 'phone_change') {
            $verification->user()->update(['phone_number' => $verification->phone_change]);
        }

        return response()->json(['message' => 'Verification successful'], 200);
    }

    public function resend(Request $request) {
        $request->validate([
            'type' => 'required|string',
        ]);

        $this->generateAndSend($request->user(), $request->type);

        return response()->json(['message' => 'Verification code resent'], 200);
    }
}
