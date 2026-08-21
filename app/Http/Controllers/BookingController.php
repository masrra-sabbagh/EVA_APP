<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\WalletService;

class BookingController extends Controller {
    public function __construct(protected WalletService $walletService) {
    }
    //عرض حجوزاتي (كمستخدم عادي)
    public function myBookings(Request $request) {
        $user = $request->user();

        $bookings = Booking::where('userId', $user->id)
            ->with(['services', 'event'])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Your bookings retrieved successfully',
            'data' => $bookings
        ]);
    }

    // عرض طلبات الحجز الواردة (لمزود الخدمة)
    public function receivedBookings(Request $request) {
        $user = $request->user();

        $bookings = Booking::whereHas('services', function ($query) use ($user) {
            $query->where('userId', $user->id);
        })->with(['services', 'user', 'event'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Received bookings retrieved successfully',
            'data' => $bookings
        ]);
    }

    //   عرض حجوزات خدمة معينة
    public function serviceBookings(Request $request, $serviceId) {
        $service = Service::find($serviceId);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found'
            ], 404);
        }

        $bookings = Booking::whereHas('services', function ($query) use ($serviceId) {
            $query->where('services.id', $serviceId);
        })
            ->with(['services', 'user', 'event'])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Service bookings retrieved successfully',
            'data' => [
                'bookings' => $bookings
            ]
        ]);
    }

    public function bookStaticEvent(Request $request) {
        $validated = $request->validate([
            'eventId' => 'required|exists:events,id',
        ]);

        $event = Event::findOrFail($validated['eventId']);

        // تأكد إنها فعالية ثابتة
        if ($event->nature !== 'static') {
            return response()->json([
                'success' => false,
                'message' => 'This event is not a static event.',
            ], 422);
        }

        // تأكد إنها بالمستقبل
        if ($event->start_date->lt(today())) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot book a past event.',
            ], 422);
        }

        // تأكد ما حجز نفس الفعالية قبل
        $alreadyBooked = Booking::where('userId', $request->user()->id)
            ->where('eventId', $event->id)
            ->where('booking_status', '!=', 'cancelled')
            ->exists();

        if ($alreadyBooked) {
            return response()->json([
                'success' => false,
                'message' => 'You already booked this event.',
            ], 409);
        }

        $booking = Booking::create([
            'userId'         => $request->user()->id,
            'eventId'        => $event->id,
            'booking_status' => 'confirmed', // مجاني = confirmed مباشرة
            'start_date'     => $event->event_date,
            'end_date'       => $event->event_date,
            'total_price'    => 0,
            'paid_amount'    => 0,
            'is_refunded'    => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event booked successfully.',
            'data'    => $booking->load('event'),
        ], 201);
    }

    // =========================================================
    // نوع 2: حجز خدمات لفعالية ديناميكية (مع 40%)
    // =========================================================
    public function bookDynamicEventServices(Request $request) {
        $validated = $request->validate([
            'eventId'        => 'required|exists:events,id',
            'start_date'     => 'required|date_format:Y-m-d|after:today',
            'end_date'       => 'required|date_format:Y-m-d|after:start_date',
            'services'       => 'required|array',
            'services.*'     => 'exists:services,id',
            'extra_services' => 'nullable|string',
        ]);

        $event = Event::findOrFail($validated['eventId']);

        // تأكد إنها فعالية ديناميكية
        if ($event->nature !== 'dynamic') {
            return response()->json([
                'success' => false,
                'message' => 'This event is not a dynamic event.',
            ], 422);
        }

        // تأكد إنها بالمستقبل
        if ($event->start_date->lt(today())) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot book services for a past event.',
            ], 422);
        }

        // حساب الأيام
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate   = Carbon::parse($validated['end_date'])->startOfDay();
        $numberOfDays = max(1, $startDate->diffInDays($endDate) + 1);
        if ($startDate->lt($event->start_date) || $endDate->gt($event->end_date)) {
            return response()->json([
                'success' => false,
                'message' => 'Booking dates must be within the event dates.',
            ], 422);
        }
        // تحقق من التعارض
        $conflictingServices = [];
        $conflictingBookings = [];

        foreach ($validated['services'] as $serviceId) {
            $conflict = Booking::whereHas('services', fn($q) => $q->where('services.id', $serviceId))
                ->where('booking_status', '!=', 'cancelled')
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhere(fn($q) => $q->where('start_date', '<=', $validated['start_date'])
                            ->where('end_date', '>=', $validated['end_date']));
                })->first();

            if ($conflict) {
                $conflictingServices[] = $serviceId;
                $conflictingBookings[] = [
                    'service_id' => $serviceId,
                    'booking_id' => $conflict->id,
                    'start_date' => $conflict->start_date,
                    'end_date'   => $conflict->end_date,
                    'status'     => $conflict->booking_status,
                ];
            }
        }

        if (!empty($conflictingServices)) {
            return response()->json([
                'success' => false,
                'message' => 'Some services are already booked for the selected dates',
                'errors'  => [
                    'conflicting_services' => $conflictingServices,
                    'conflicting_bookings' => $conflictingBookings,
                ],
            ], 409);
        }

        // حساب السعر
        $totalPrice      = 0;
        $servicesDetails = [];
        $services        = Service::whereIn('id', $validated['services'])->get();

        foreach ($services as $service) {
            $pricePerDay     = $service->price_per_day;
            $discount        = $service->discount_percentage ?? 0;
            $discountedPrice = $pricePerDay - ($pricePerDay * $discount / 100);
            $serviceTotal    = $discountedPrice * $numberOfDays;
            $totalPrice     += $serviceTotal;

            $servicesDetails[] = [
                'service_id'              => $service->id,
                'title'                   => $service->title,
                'price_per_day'           => $pricePerDay,
                'discount_percentage'     => $discount,
                'discounted_price_per_day' => $discountedPrice,
                'number_of_days'          => $numberOfDays,
                'total_price'             => $serviceTotal,
            ];
        }

        $paidAmount = $totalPrice * 0.40;

        // تحقق من رصيد المحفظة
        $wallet = $request->user()->wallet;

        if ($wallet->balance < $paidAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance.',
                'required' => $paidAmount,
                'available' => $wallet->balance,
            ], 422);
        }

        // إنشاء الحجز
        $booking = Booking::create([
            'userId'         => $request->user()->id,
            'eventId'        => $event->id,
            'booking_status' => 'pending',
            'start_date'     => $validated['start_date'],
            'end_date'       => $validated['end_date'],
            'total_price'    => $totalPrice,
            'paid_amount'    => $paidAmount,
            'is_refunded'    => false,
            'extra_services' => $validated['extra_services'] ?? null,
        ]);

        $booking->services()->attach($validated['services']);

        // خصم الـ 40% من المحفظة
        $this->walletService->holdForBooking($wallet, $paidAmount, $booking);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully. 40% has been held from your wallet.',
            'data'    => [
                'booking'         => $booking->load(['services', 'event']),
                'price_breakdown' => [
                    'number_of_days'  => $numberOfDays,
                    'services_details' => $servicesDetails,
                    'total_price'     => $totalPrice,
                    'paid_amount'     => $paidAmount,
                    'remaining_amount' => $totalPrice - $paidAmount,
                ],
            ],
        ], 201);
    }

    //  * عرض تفاصيل حجز
    public function show(Request $request, $id) {
        $user = $request->user();

        $booking = Booking::where('id', $id)
            ->where('userId', $user->id)
            ->with(['services', 'event'])
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $booking
        ]);
    }

    //قبول أو رفض حجز (لمزود الخدمة)
    public function updateStatus(Request $request, $id) {
        $user = $request->user();
        $validated = $request->validate([
            'booking_status' => 'required|in:confirmed,cancelled'
        ]);

        $booking = Booking::where('id', $id)
            ->whereHas('services', function ($query) use ($user) {
                $query->where('userId', $user->id);
            })->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or you are not authorized'
            ], 404);
        }
        // إذا مزود الخدمة لغى، رجع الـ 40% للمستخدم
        if ($validated['booking_status'] === 'cancelled') {
            $wallet = $booking->user->wallet;
            $this->walletService->refundHold($wallet, $booking->paid_amount, $booking);
        }
        $booking->update([
            'booking_status' => $validated['booking_status']
        ]);

        return response()->json([
            'success' => true,
            'message' => "Booking status updated to {$booking->booking_status}",
            'data' => $booking
        ]);
    }

    /**
     * إلغاء حجز من قبل المستخدم
     */
    public function cancelBooking(Request $request, $id) {
        $user    = $request->user();
        $booking = Booking::where('id', $id)
            ->where('userId', $user->id)
            ->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if ($booking->booking_status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Booking is already cancelled.'], 400);
        }

        $hoursPassed = Carbon::parse($booking->created_at)->diffInHours(now());
        $canRefund   = false;
        $message     = '';

        if ($booking->booking_status === 'pending') {
            $canRefund = true;
            $message   = 'Booking cancelled. Full amount will be refunded.';
        } elseif ($booking->booking_status === 'confirmed') {
            if ($hoursPassed < 24) {
                $canRefund = true;
                $message   = 'Booking cancelled. Amount will be refunded (within 24 hours).';
            } else {
                $canRefund = false;
                $message   = 'Booking cancelled. Amount will NOT be refunded (24 hours passed).';
            }
        }

        $booking->update([
            'booking_status' => 'cancelled',
            'is_refunded'    => $canRefund,
        ]);

        // تحديث المحفظة فقط إذا كان في مبلغ محجوز (فعالية ديناميكية)
        if ($booking->paid_amount > 0) {
            $wallet = $user->wallet;
            if ($canRefund) {
                $this->walletService->refundHold($wallet, $booking->paid_amount, $booking);
            } else {
                $this->walletService->forfeitHold($wallet, $booking->paid_amount, $booking);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => [
                'booking'        => $booking,
                'hours_passed'   => $hoursPassed,
                'can_refund'     => $canRefund,
                'paid_amount'    => $booking->paid_amount,
                'refund_status'  => $canRefund ? 'Refundable' : 'Non-refundable',
            ],
        ]);
    }
    public function confirmCompletion(Request $request, $id) {
        $booking = Booking::where('id', $id)
            ->where('userId', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if ($booking->booking_status !== 'confirmed') {
            return response()->json(['success' => false, 'message' => 'Booking is not confirmed yet.'], 422);
        }

        $booking->update(['booking_status' => 'pending_completion']);

        return response()->json(['success' => true, 'message' => 'Sent to admin for final confirmation.']);
    }
}
