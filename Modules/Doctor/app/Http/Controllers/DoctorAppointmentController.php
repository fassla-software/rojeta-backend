<?php

namespace Modules\Doctor\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Appointment;
use App\Support\PaginationHelper;
use App\Support\TimeFormatter;
use Illuminate\Http\Request;

class DoctorAppointmentController extends Controller
{
    public function index(Request $request)
    {
        [$page, $limit] = PaginationHelper::fromRequest($request);

        $query = Appointment::where('doctor_id', $request->user()->id)
            ->with(['patient.patientProfile', 'clinic']);

        if ($request->filled('date')) {
            $query->whereDate('date', $request->query('date'));
        }

        if ($request->filled('status')) {
            $query->where('status', strtolower($request->query('status')));
        }

        if ($request->filled('type')) {
            $query->where('visit_type', $this->mapVisitTypeToDb($request->query('type')));
        }

        $paginator = $query->orderByDesc('date')->orderByDesc('time')->paginate($limit, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(fn ($apt) => $this->formatAppointment($apt));

        return ApiResponse::paginated($paginator, $data);
    }

    public function updateStatus(Request $request, int $appointmentId)
    {
        $request->validate([
            'status' => 'required|in:Confirmed,Completed,Cancelled,confirmed,completed,cancelled',
        ]);

        $appointment = Appointment::where('doctor_id', $request->user()->id)
            ->where('id', $appointmentId)
            ->firstOrFail();

        $status = strtolower($request->input('status'));
        $appointment->update(['status' => $status]);

        return ApiResponse::message('Appointment status updated successfully');
    }

    private function formatAppointment(Appointment $apt): array
    {
        $profile = $apt->patient?->patientProfile;
        $age = $profile?->date_of_birth
            ? \Carbon\Carbon::parse($profile->date_of_birth)->age . ' yrs'
            : null;

        $originalFee = $apt->original_fee ?? $apt->consultation_fee;
        $discount = $apt->discount_percent ? $apt->discount_percent . '%' : null;

        return [
            'id' => (string) $apt->id,
            'patientName' => $apt->patient?->name ?? 'Unknown',
            'patientAge' => $age,
            'patientGender' => $profile?->gender,
            'appointmentTime' => TimeFormatter::toDisplay($apt->time),
            'appointmentDate' => $apt->date?->format('Y-m-d'),
            'type' => $this->mapVisitTypeFromDb($apt->visit_type),
            'status' => ucfirst($apt->status),
            'patientPhone' => $apt->patient?->phone_number ?? $profile?->phone_number,
            'isUrgent' => (bool) $apt->is_urgent,
            'location' => $apt->location ?? $apt->clinic?->city,
            'notes' => $apt->important_notes,
            'feeOriginal' => $originalFee ? $originalFee . ' EGP' : null,
            'feeCurrent' => $apt->consultation_fee ? $apt->consultation_fee . ' EGP' : null,
            'discount' => $discount,
            'paymentMethod' => $apt->payment_method ?? 'cash',
            'isOnline' => (bool) $apt->is_online,
        ];
    }

    private function mapVisitTypeToDb(string $type): string
    {
        return match (strtolower($type)) {
            'clinic visit' => 'clinic_visit',
            'home visit' => 'home_visit',
            'video call' => 'video_call',
            default => strtolower(str_replace(' ', '_', $type)),
        };
    }

    private function mapVisitTypeFromDb(?string $type): string
    {
        return match ($type) {
            'clinic_visit' => 'Clinic Visit',
            'home_visit' => 'Home Visit',
            'video_call' => 'Video Call',
            default => ucwords(str_replace('_', ' ', $type ?? '')),
        };
    }
}
