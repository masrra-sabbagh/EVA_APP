<?php

use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminComplaintController;
use App\Http\Controllers\Admin\AdminProviderController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\WalletManagementController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTaskController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProviderRequestController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationCodeController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [UserController::class, 'Sign_up']);
Route::post('/login', [UserController::class, 'Log_in']);
Route::post('/forgot-password', [UserController::class, 'forgotPassword']);
Route::post('/reset-password', [UserController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/logout', [UserController::class, 'Log_out']);
    Route::post('/verify-code', [VerificationCodeController::class, 'verify']);
    Route::post('/resend-code', [VerificationCodeController::class, 'resend'])
        ->middleware('throttle:3,1');
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles');
    })->middleware('auth:sanctum');
});
//أخواتي هاجر و آية أي راوتات تانية بده يكون الرقم متحقق منه يعني : أي راوت حطوه بهي المجموعة
Route::middleware(['auth:sanctum', 'verified.phone'])->group(function () {
    Route::post('/change-password', [UserController::class, 'changePassword']);
    Route::patch('/profile', [UserController::class, 'updateProfile']);
    Route::post('/phone/request-change', [UserController::class, 'requestPhoneChange']);
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::post('/wallet/deposit', [WalletController::class, 'requestDeposit']);
    Route::post('/provider-request', [ProviderRequestController::class, 'store']);
    Route::get('/provider-request-me', [ProviderRequestController::class, 'myRequest']);

    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events/dynamic', [EventController::class, 'storeDynamic']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::get('/locations/countries', [LocationController::class, 'countries']);
    Route::get('/locations/cities', [LocationController::class, 'cities']);

    // راوتات المهام
    Route::get('/events/{eventId}/tasks', [EventTaskController::class, 'index']);
    Route::post('/events/{eventId}/tasks', [EventTaskController::class, 'store']);
    Route::put('/tasks/{taskId}', [EventTaskController::class, 'update']);
    Route::delete('/tasks/{taskId}', [EventTaskController::class, 'destroy']);
    Route::patch('/tasks/{taskId}/toggle', [EventTaskController::class, 'toggleCompletion']);
    // عرض الخدمات حسب الفئة والموقع
    Route::get('/services', [ServiceController::class, 'index']);

    // راوتات الحجز للمستخدم العادي
    Route::get('/my-bookings', [BookingController::class, 'myBookings']);
    Route::post('/bookings/static', [BookingController::class, 'bookStaticEvent']);
    Route::post('/bookings/dynamic', [BookingController::class, 'bookDynamicEventServices']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancelBooking']);
    Route::patch('/bookings/{id}/confirm-completion', [BookingController::class, 'confirmCompletion']);
    Route::get('/services/{serviceId}/bookings', [BookingController::class, 'serviceBookings']);

    //  راوتات التقييم
    Route::get('/services/{serviceId}/reviews', [ReviewController::class, 'serviceReviews']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    //راوتات الشكوى
    Route::post('/complaints', [ComplaintController::class, 'store']);
    Route::get('/my-complaints', [ComplaintController::class, 'myComplaints']);

    // راوتات المفضلة
    Route::get('/my-favorites', [FavoriteController::class, 'myFavorites']);
    Route::post('/services/{id}/favorite', [FavoriteController::class, 'addToFavorites']);
    Route::delete('/services/{id}/favorite', [FavoriteController::class, 'removeFromFavorites']);

    // راوتات الخدمة للمزود
    Route::middleware(['auth:sanctum', 'verified.phone', 'provider'])->group(function () {
        Route::get('/my-services', [ServiceController::class, 'myServices']);
        Route::post('/services', [ServiceController::class, 'store']);
        Route::put('/services/{id}', [ServiceController::class, 'update']);
        Route::patch('/services/{id}/toggle', [ServiceController::class, 'toggleAvailability']);

        // راوتات الحجز لمزود الخدمة
        Route::get('/received-bookings', [BookingController::class, 'receivedBookings']);
        Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);
    });
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [UserManagementController::class, 'index']);
    //Route::get('/users/{user}', [UserManagementController::class, 'show']);
    // مؤجلة لحتى يصير عندي المودل تبع المحفظة و الحجز فائدة هاد الراوت مشان يرجع كل المعلومات عن اليوزر كل طلبات الترقي تبعه و محفظته و كل حجوزاته
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);
    Route::get('/provider-requests', [ProviderRequestController::class, 'pendingRequests']);
    Route::patch('/provider-requests/{providerRequest}/approve', [ProviderRequestController::class, 'approve']);
    Route::patch('/provider-requests/{providerRequest}/reject', [ProviderRequestController::class, 'reject']);

    Route::get('/deposits', [WalletManagementController::class, 'pendingDeposits']);
    Route::patch('/deposits/{transactionRequest}/approve', [WalletManagementController::class, 'approveDeposit']);
    Route::patch('/deposits/{transactionRequest}/reject', [WalletManagementController::class, 'rejectDeposit']);

    // إدارة مزودي الخدمة
    Route::get('/providers', [AdminProviderController::class, 'index']);
    Route::get('/providers/{id}', [AdminProviderController::class, 'show']);
    Route::patch('/providers/{id}/suspend', [AdminProviderController::class, 'suspend']);
    Route::patch('/providers/{id}/activate', [AdminProviderController::class, 'activate']);
    Route::delete('/providers/{id}', [AdminProviderController::class, 'destroy']);

    //  مراقبة الحجوزات
    Route::get('/bookings', [AdminBookingController::class, 'index']);
    Route::get('/bookings/pending', [AdminBookingController::class, 'pendingBookings']);
    Route::get('/bookings/confirmed', [AdminBookingController::class, 'confirmedBookings']);
    Route::get('/bookings/cancelled', [AdminBookingController::class, 'cancelledBookings']);
    Route::get('/bookings/pending-completion', [AdminBookingController::class, 'pendingCompletions']); // ✅ قبل {id}
    Route::get('/bookings/{id}', [AdminBookingController::class, 'show']);
    Route::patch('/bookings/{id}/cancel', [AdminBookingController::class, 'cancel']);
    Route::patch('/bookings/{id}/confirm', [AdminBookingController::class, 'confirm']);
    Route::patch('/bookings/{booking}/complete', [AdminBookingController::class, 'completeBooking']);
    // مراقبة الشكوى والتقييمات
    Route::get('/reviews', [AdminComplaintController::class, 'allReviews']);
    Route::delete('/reviews/{reviewId}', [AdminComplaintController::class, 'deleteReview']);
    Route::get('/complaints', [AdminComplaintController::class, 'allComplaints']);
    Route::delete('/complaints/{complaintId}', [AdminComplaintController::class, 'deleteComplaint']);

    Route::get('/settings/commission', [SettingsController::class, 'getCommission']);
    Route::patch('/settings/commission', [SettingsController::class, 'updateCommission']);
});
