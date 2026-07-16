<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PatientBookingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_provider_id' => 'required',
            'service_provider_type' => 'required|in:doctor,hospital,laboratory,radiology,nursing',
            'service_id' => 'required',
            'booking_date' => 'required|date_format:Y-m-d',
            'booking_time' => 'required|date_format:H:i',
            'visit_type' => 'required|in:clinic,home_visit,video_call',
            'payment_type' => 'required|in:cash,credit_card,wallet,points',
            'promo_code' => 'nullable|string',
            'patient_notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'url',
            'address' => 'required_if:visit_type,home_visit|array',
        ]);

        $provider = User::where('id', $validated['service_provider_id'])->where('role', $validated['service_provider_type'])->first();

        if (!$provider) {
            return ApiResponse::error('NOT_FOUND', 'Service Provider not found.', 404);
        }

        // Mocking price calculation
        $subtotal = 450.00;
        $discount = 90.00;
        $totalPrice = 360.00;

        $booking = Booking::create([
            'provider_id' => $provider->id,
            'provider_type' => $validated['service_provider_type'],
            'patient_id' => $request->user()->id,
            'type' => $validated['visit_type'],
            'patient_name' => $request->user()->name,
            'patient_phone' => $request->user()->phone_number ?? $request->user()->mobile,
            'service_id' => $validated['service_id'],
            'service_name' => 'Service ' . $validated['service_id'], // Mock service name
            'date' => $validated['booking_date'],
            'time' => $validated['booking_time'],
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_type' => $validated['payment_type'],
            'promo_code' => $validated['promo_code'] ?? null,
            'patient_notes' => $validated['patient_notes'] ?? null,
            'attachments' => $validated['attachments'] ?? null,
            'address' => $validated['address'] ?? null,
            'fee' => $totalPrice,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total_price' => $totalPrice,
            'points_earned' => 50,
            'payment_url' => $validated['payment_type'] === 'credit_card' ? 'https://pay.gateway.com/checkout/chk_' . Str::random(10) : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking request submitted successfully.',
            'data' => [
                'booking_id' => $booking->id,
                'service_provider_id' => $booking->provider_id,
                'service_id' => $booking->service_id,
                'status' => $booking->status,
                'visit_type' => $booking->type,
                'booking_date' => Carbon::parse($booking->date)->format('Y-m-d'),
                'booking_time' => Carbon::parse($booking->time)->format('H:i'),
                'subtotal' => (float) $booking->subtotal,
                'discount' => (float) $booking->discount,
                'total_price' => (float) $booking->total_price,
                'points_earned' => $booking->points_earned,
                'payment_url' => $booking->payment_url,
                'created_at' => $booking->created_at->toIso8601String(),
            ]
        ], 201);
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $limit = $request->query('limit', 10);

        $query = Booking::where('patient_id', $request->user()->id)->with('provider');

        if ($status) {
            if ($status === 'upcoming') {
                $query->whereIn('status', ['pending', 'confirmed']);
            } else {
                $query->where('status', $status);
            }
        }

        $bookings = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'meta' => [
                'total_bookings' => $bookings->total(),
                'current_page' => $bookings->currentPage(),
                'total_pages' => $bookings->lastPage(),
            ],
            'data' => collect($bookings->items())->map(function ($booking) {
                return [
                    'booking_id' => $booking->id,
                    'service_provider' => [
                        'id' => $booking->provider_id,
                        'name' => $booking->provider->name ?? 'Unknown',
                        'type' => $booking->provider_type,
                        'specialty' => $booking->provider->doctorProfile->specialty ?? null,
                    ],
                    'service_name' => $booking->service_name,
                    'status' => in_array($booking->status, ['pending', 'confirmed']) ? 'upcoming' : $booking->status,
                    'visit_type' => $booking->type,
                    'booking_date' => Carbon::parse($booking->date)->format('Y-m-d'),
                    'booking_time' => Carbon::parse($booking->time)->format('H:i'),
                    'total_price' => (float) $booking->total_price,
                ];
            })
        ]);
    }

    public function show(Request $request, $id)
    {
        $booking = Booking::where('patient_id', $request->user()->id)->where('id', $id)->first();

        if (!$booking) {
            return ApiResponse::error('NOT_FOUND', 'Booking not found.', 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'booking_id' => $booking->id,
                'service_provider_id' => $booking->provider_id,
                'service_id' => $booking->service_id,
                'status' => in_array($booking->status, ['pending', 'confirmed']) ? 'upcoming' : $booking->status,
                'visit_type' => $booking->type,
                'booking_date' => Carbon::parse($booking->date)->format('Y-m-d'),
                'booking_time' => Carbon::parse($booking->time)->format('H:i'),
                'subtotal' => (float) $booking->subtotal,
                'discount' => (float) $booking->discount,
                'total_price' => (float) $booking->total_price,
                'points_earned' => $booking->points_earned,
                'patient_notes' => $booking->patient_notes,
                'video_call_link' => $booking->video_call_link,
                'created_at' => $booking->created_at->toIso8601String(),
            ]
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $request->validate([
            'cancel_reason' => 'required|string',
        ]);

        $booking = Booking::where('patient_id', $request->user()->id)->where('id', $id)->first();

        if (!$booking || in_array($booking->status, ['completed', 'cancelled'])) {
            return ApiResponse::error('BAD_REQUEST', 'This booking cannot be cancelled.', 400);
        }

        $booking->update([
            'status' => 'cancelled',
            // 'cancel_reason' => $request->cancel_reason // Add this column if needed, or save to notes
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'data' => [
                'booking_id' => $booking->id,
                'status' => 'cancelled'
            ]
        ]);
    }

    public function reschedule(Request $request, $id)
    {
        $request->validate([
            'booking_date' => 'required|date_format:Y-m-d',
            'booking_time' => 'required|date_format:H:i',
        ]);

        $booking = Booking::where('patient_id', $request->user()->id)->where('id', $id)->first();

        if (!$booking || in_array($booking->status, ['completed', 'cancelled'])) {
            return ApiResponse::error('BAD_REQUEST', 'This booking cannot be rescheduled.', 400);
        }

        // Check for conflict here if needed

        $booking->update([
            'date' => $request->booking_date,
            'time' => $request->booking_time,
            'status' => 'pending', // or keep confirmed if auto-confirm
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking rescheduled successfully.',
            'data' => [
                'booking_id' => $booking->id,
                'status' => 'upcoming',
                'booking_date' => $request->booking_date,
                'booking_time' => $request->booking_time,
            ]
        ]);
    }

    public function submitReview(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $booking = Booking::where('patient_id', $request->user()->id)->where('id', $id)->first();

        if (!$booking || $booking->status !== 'completed') {
            return ApiResponse::error('BAD_REQUEST', 'You can only review completed bookings.', 400);
        }

        // Mock creating review
        // In real scenario, insert into reviews table

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => [
                'review_id' => 'rev_' . Str::random(6),
                'rating' => (float) $request->rating,
                'created_at' => now()->toIso8601String(),
            ]
        ], 201);
    }
}
