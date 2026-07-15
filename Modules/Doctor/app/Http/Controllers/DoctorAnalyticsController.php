<?php

namespace Modules\Doctor\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Appointment;
use App\Models\FinancialTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DoctorAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $doctorId = $request->user()->id;
        $monthLabel = $request->query('month', Carbon::now()->format('F Y'));

        try {
            $month = Carbon::parse('first day of ' . $monthLabel);
        } catch (\Exception) {
            $month = Carbon::now()->startOfMonth();
            $monthLabel = $month->format('F Y');
        }

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $prevStart = $month->copy()->subMonth()->startOfMonth();
        $prevEnd = $month->copy()->subMonth()->endOfMonth();

        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereBetween('date', [$start, $end])
            ->get();

        $prevAppointments = Appointment::where('doctor_id', $doctorId)
            ->whereBetween('date', [$prevStart, $prevEnd])
            ->count();

        $patientIds = $appointments->pluck('patient_id')->unique();
        $newPatients = Appointment::where('doctor_id', $doctorId)
            ->whereIn('patient_id', $patientIds)
            ->where('created_at', '<', $start)
            ->pluck('patient_id')
            ->unique();

        $totalPatients = $patientIds->count();
        $returningPatients = $patientIds->diff($newPatients)->count();
        $newPatientCount = $totalPatients - $returningPatients;

        $totalRevenue = (float) FinancialTransaction::where('provider_id', $doctorId)
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('earning');

        $prevRevenue = (float) FinancialTransaction::where('provider_id', $doctorId)
            ->whereBetween('transaction_date', [$prevStart, $prevEnd])
            ->sum('earning');

        $completed = $appointments->where('status', 'completed')->count();
        $cancelled = $appointments->where('status', 'cancelled')->count();
        $totalAppointments = $appointments->count();

        $expenses = (float) FinancialTransaction::where('provider_id', $doctorId)
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('platform_commission');
        $netIncome = $totalRevenue - $expenses;
        $avgPerPatient = $totalPatients > 0 ? round($totalRevenue / $totalPatients, 2) : 0;
        $successRate = $totalAppointments > 0 ? round(($completed / $totalAppointments) * 100, 1) : 0;
        $patientGrowth = $prevAppointments > 0 ? round((($totalAppointments - $prevAppointments) / $prevAppointments) * 100, 1) : 0;
        $revenueGrowth = $prevRevenue > 0 ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : 0;

        return ApiResponse::data([
            'monthLabel' => $monthLabel,
            'totalPatients' => $totalPatients,
            'newPatients' => max(0, $newPatientCount),
            'returningPatients' => $returningPatients,
            'totalRevenue' => $totalRevenue,
            'expenses' => $expenses,
            'netIncome' => $netIncome,
            'avgPerPatient' => $avgPerPatient,
            'totalAppointments' => $totalAppointments,
            'completedAppointments' => $completed,
            'cancelledAppointments' => $cancelled,
            'patientGrowthPercent' => $patientGrowth,
            'revenueGrowthPercent' => $revenueGrowth,
            'successRate' => $successRate,
        ]);
    }
}
