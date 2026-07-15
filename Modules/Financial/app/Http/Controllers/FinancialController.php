<?php

namespace Modules\Financial\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\FinancialTransaction;
use App\Support\PaginationHelper;
use App\Support\TimeFormatter;
use Illuminate\Http\Request;

class FinancialController extends Controller
{
    public function summary(Request $request)
    {
        $query = FinancialTransaction::where('provider_id', $request->user()->id);

        if ($request->filled('startDate')) {
            $query->whereDate('transaction_date', '>=', $request->query('startDate'));
        }

        if ($request->filled('endDate')) {
            $query->whereDate('transaction_date', '<=', $request->query('endDate'));
        }

        $totalIncome = (float) $query->sum('service_fee');
        $commission = (float) (clone $query)->sum('platform_commission');
        $netIncome = (float) (clone $query)->sum('earning');
        $completedAppointments = (clone $query)->where('status', 'completed')->count();

        return ApiResponse::data([
            'totalIncome' => $totalIncome,
            'completedAppointments' => $completedAppointments,
            'commission' => $commission,
            'netIncome' => $netIncome,
        ]);
    }

    public function transactions(Request $request)
    {
        [$page, $limit] = PaginationHelper::fromRequest($request);

        $query = FinancialTransaction::where('provider_id', $request->user()->id);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('startDate')) {
            $query->whereDate('transaction_date', '>=', $request->query('startDate'));
        }

        if ($request->filled('endDate')) {
            $query->whereDate('transaction_date', '<=', $request->query('endDate'));
        }

        $paginator = $query->orderByDesc('transaction_date')->paginate($limit, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(fn ($t) => [
            'patientName' => $t->patient_name,
            'visitType' => $t->visit_type,
            'status' => $t->status,
            'date' => $t->transaction_date?->format('j M Y'),
            'time' => TimeFormatter::toDisplay($t->transaction_time),
            'serviceFee' => (float) $t->service_fee,
            'platformCommission' => (float) $t->platform_commission,
            'yourEarning' => (float) $t->earning,
        ]);

        return ApiResponse::paginated($paginator, $data);
    }
}
