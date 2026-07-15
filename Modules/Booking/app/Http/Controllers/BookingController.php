<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Booking;
use App\Support\PaginationHelper;
use App\Support\TimeFormatter;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    private const USER_TYPE_MAP = [
        'laboratory' => 'laboratory',
        'radiologyCenter' => 'radiology',
        'nursingOffice' => 'nursing',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'userType' => 'required|in:laboratory,radiologyCenter,nursingOffice',
            'status' => 'nullable|in:pending,confirmed,completed,rejected',
        ]);

        $role = self::USER_TYPE_MAP[$request->query('userType')];
        if ($request->user()->role !== $role) {
            return ApiResponse::error('FORBIDDEN', 'You do not have permission to access this resource', 403);
        }

        [$page, $limit] = PaginationHelper::fromRequest($request);

        $query = Booking::where('provider_id', $request->user()->id)
            ->where('provider_type', $request->query('userType'));

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $paginator = $query->orderByDesc('date')->paginate($limit, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(fn ($b) => [
            'id' => (string) $b->id,
            'type' => $b->type,
            'patientName' => $b->patient_name,
            'patientPhone' => $b->patient_phone,
            'serviceName' => $b->service_name,
            'locationName' => $b->location_name,
            'date' => $b->date?->format('Y-m-d'),
            'time' => TimeFormatter::toDisplay($b->time),
            'status' => $b->status,
            'paymentStatus' => $b->payment_status,
            'fee' => (float) $b->fee,
        ]);

        return ApiResponse::paginated($paginator, $data);
    }

    public function updateStatus(Request $request, int $bookingId)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,rejected',
        ]);

        $booking = Booking::where('provider_id', $request->user()->id)
            ->where('id', $bookingId)
            ->firstOrFail();

        $booking->update(['status' => $request->input('status')]);

        return ApiResponse::message('Booking status updated successfully');
    }
}
