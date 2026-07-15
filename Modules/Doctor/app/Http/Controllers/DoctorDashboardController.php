<?php

namespace Modules\Doctor\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Appointment;
use App\Models\FinancialTransaction;
use App\Models\Notification;
use App\Support\PaginationHelper;
use App\Support\TimeFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DoctorDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $doctorId = $request->user()->id;
        $today = Carbon::today();

        $todayAppointments = Appointment::where('doctor_id', $doctorId)->whereDate('date', $today)->count();
        $pendingRequests = Appointment::where('doctor_id', $doctorId)->where('status', 'pending')->count();
        $completedAppointments = Appointment::where('doctor_id', $doctorId)->where('status', 'completed')->count();
        $newRequests = Appointment::where('doctor_id', $doctorId)
            ->where('status', 'pending')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        $rating = (float) ($request->user()->doctorProfile?->rating ?? 0);
        $totalEarnings = (string) FinancialTransaction::where('provider_id', $doctorId)->sum('earning');

        return ApiResponse::data([
            'todayAppointments' => $todayAppointments,
            'pendingRequests' => $pendingRequests,
            'completedAppointments' => $completedAppointments,
            'newRequests' => $newRequests,
            'rating' => $rating,
            'totalEarnings' => $totalEarnings,
        ]);
    }

    public function profileSummary(Request $request)
    {
        $user = $request->user()->load('doctorProfile');

        return ApiResponse::data([
            'name' => $user->name,
            'specialty' => $user->doctorProfile?->specialty ?? '',
            'imageUrl' => $user->doctorProfile?->image_url,
        ]);
    }
}
