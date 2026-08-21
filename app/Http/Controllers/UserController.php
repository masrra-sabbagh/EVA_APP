<?php

namespace App\Http\Controllers;

use App\Exceptions\WhatsappServiceException;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller {
    protected $whatsappService;

    public function __construct(WhatsappService $whatsappService) {
        $this->whatsappService = $whatsappService;
    }
    public function Sign_up(StoreUserRequest $request) {
        $validatedData = $request->validated();

        $path = $request->file('profile_image')->store('profiles', 'public');
        $validatedData['profile_image'] = $path;
        $validatedData['password'] = Hash::make($validatedData['password']);

        $user = User::create($validatedData);

        $userRoleId = Role::where('role_type', 'user')->value('id');
        $user->roles()->attach($userRoleId);
        $user->wallet()->create([
            'balance'      => 0,
            'held_balance' => 0,
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $otpSent = true;
        $verification = null;

        try {
            $verificationController = app(VerificationCodeController::class);
            $verification = $verificationController->generateAndSend($user, 'phone_verification');
        } catch (WhatsappServiceException $e) {
            $otpSent = false;
        }

        return response()->json([
            'message'  => $otpSent
                ? 'Account created. Please verify your phone number.'
                : 'Account created, but failed to send verification code. Please request a new one.',
            'token'    => $token,
            'user'     => $user->load('roles'),
            'otp_sent' => $otpSent,
        ], 201);
    }
    public function Log_in(Request $request) {
        $request->validate([
            'phone_number' => 'required|regex:/^\+?[0-9]{10,15}$/',
            'password' => 'required|string|min:8',
        ]);

        if (!Auth::attempt($request->only('phone_number', 'password'))) {
            return response()->json([
                'message' => 'Invalid phone or password'
            ], 401);
        }

        $user = User::where('phone_number', $request->phone_number)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user'    => $user->load('roles'),
            'Token'   => $token
        ], 201);
    }

    public function Log_out(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout successful']);
    }
    public function changePassword(Request $request) {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'      => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Password changed successfully',
        ], 200);
    }
    public function forgotPassword(Request $request) {
        $request->validate([
            'phone_number' => 'required|regex:/^\+?[0-9]{10,15}$/|exists:users,phone_number',
        ]);

        $user = User::where('phone_number', $request->phone_number)->firstOrFail();

        $verificationController = app(VerificationCodeController::class);

        try {
            $verificationController->generateAndSend($user, 'password_reset');
        } catch (WhatsappServiceException $e) {
            return response()->json([
                'message' => 'Failed to send verification code. Please try again.',
            ], 502);
        }

        return response()->json([
            'message' => 'Verification code sent to your phone.',
            'user_id' => $user->id, // الفرونت إند رح يحتاجها بالخطوة الجاية
        ], 200);
    }

    public function resetPassword(Request $request) {
        $request->validate([
            'user_id'      => 'required|exists:users,id',
            'code'         => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $verification = VerificationCode::where('user_id', $request->user_id)
            ->where('type', 'password_reset')
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

        $verification->user()->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Password reset successfully',
        ], 200);
    }
    public function updateProfile(UpdateProfileRequest $request) {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->hasFile('profile_image')) {
            // حذف الصورة القديمة (لو موجودة) قبل رفع الجديدة، لتوفير مساحة تخزين
            if ($user->profile_image) {
                \Storage::disk('public')->delete($user->profile_image);
            }

            $path = $request->file('profile_image')->store('profiles', 'public');
            $validated['profile_image'] = $path;
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user'    => $user->fresh()->load('roles'),
        ], 200);
    }
    public function requestPhoneChange(Request $request) {
        $request->validate([
            'current_password' => 'required|string',
            'new_phone_number'  => 'required|regex:/^\+?[0-9]{10,15}$/|unique:users,phone_number',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user = $request->user();

        VerificationCode::where('user_id', $user->id)
            ->where('type', 'phone_change')
            ->where('is_used', false)
            ->update(['is_used' => true]);

        $otpCode = (string) rand(1000, 9999);

        VerificationCode::create([
            'code'         => $otpCode,
            'type'         => 'phone_change',
            'phone_change' => $request->new_phone_number,
            'is_used'      => false,
            'expired_at'   => now()->addMinutes(5),
            'user_id'      => $user->id,
        ]);

        $otpSent = true;

        try {
            $message = "Your verification code is: {$otpCode}";
            app(WhatsappService::class)->sendOtp($request->new_phone_number, $message);
        } catch (WhatsappServiceException $e) {
            $otpSent = false;
        }

        return response()->json([
            'message'  => $otpSent
                ? 'Verification code sent to your new phone number.'
                : 'Failed to send verification code. Please try again.',
            'otp_sent' => $otpSent,
        ], $otpSent ? 200 : 502);
    }
}
