<?php

namespace Modules\Doctor\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Appointment;
use App\Models\LabResult;
use App\Models\User;
use App\Models\VisitRecord;
use App\Support\IdResolver;
use App\Support\PaginationHelper;
use Illuminate\Http\Request;

class DoctorPatientController extends Controller
{
    public function index(Request $request)
    {
        [$page, $limit] = PaginationHelper::fromRequest($request);
        $doctorId = $request->user()->id;

        $query = User::query()
            ->select('users.*')
            ->join('appointments', 'appointments.patient_id', '=', 'users.id')
            ->where('appointments.doctor_id', $doctorId)
            ->with('patientProfile')
            ->distinct();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.phone_number', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($limit, ['users.*'], 'page', $page);

        $data = collect($paginator->items())->map(function (User $patient) use ($doctorId) {
            return $this->formatPatientSummary($patient, $doctorId);
        });

        return ApiResponse::paginated($paginator, $data);
    }

    public function show(Request $request, string $patientId)
    {
        $doctorId = $request->user()->id;
        $resolvedPatientId = IdResolver::patientId($patientId);

        $hasRelation = Appointment::where('doctor_id', $doctorId)
            ->where('patient_id', $resolvedPatientId)
            ->exists();

        if (!$hasRelation) {
            return ApiResponse::error('NOT_FOUND', 'Resource not found', 404);
        }

        $patient = User::with('patientProfile')->findOrFail($resolvedPatientId);
        $profile = $patient->patientProfile;

        $visitHistory = VisitRecord::where('patient_id', $resolvedPatientId)
            ->where('doctor_id', $doctorId)
            ->orderByDesc('date')
            ->get()
            ->map(fn ($v) => [
                'id' => 'v' . $v->id,
                'date' => $v->date?->toISOString(),
                'type' => $v->type,
                'diagnosis' => $v->diagnosis,
                'prescription' => $v->prescription,
                'notes' => $v->notes,
                'followUpDate' => $v->follow_up_date?->toISOString(),
            ]);

        $labResults = LabResult::where('patient_id', $resolvedPatientId)
            ->orderByDesc('date')
            ->get()
            ->map(fn ($lr) => [
                'id' => 'lr' . $lr->id,
                'date' => $lr->date?->toISOString(),
                'testsPerformed' => $lr->tests_performed,
                'results' => $lr->results,
                'notes' => $lr->notes,
                'isNormal' => (bool) $lr->is_normal,
            ]);

        $visitsCount = Appointment::where('doctor_id', $doctorId)->where('patient_id', $resolvedPatientId)->count();
        $lastVisit = VisitRecord::where('patient_id', $resolvedPatientId)->where('doctor_id', $doctorId)->orderByDesc('date')->first();

        return ApiResponse::data([
            'basicInfo' => [
                'id' => 'p' . $patient->id,
                'name' => $patient->name,
                'phone' => $patient->phone_number ?? $profile?->phone_number,
                'age' => $profile?->date_of_birth ? \Carbon\Carbon::parse($profile->date_of_birth)->age : null,
                'gender' => $profile?->gender,
                'visitsCount' => $visitsCount,
                'lastVisitDate' => $lastVisit?->date?->toISOString(),
                'lastDiagnosis' => $lastVisit?->diagnosis,
            ],
            'allergies' => $profile?->allergies ?? [],
            'chronicConditions' => $profile?->chronic_conditions ?? [],
            'visitHistory' => $visitHistory,
            'labResults' => $labResults,
        ]);
    }

    private function formatPatientSummary(User $patient, string $doctorId): array
    {
        $profile = $patient->patientProfile;
        $lastVisit = VisitRecord::where('patient_id', $patient->id)->where('doctor_id', $doctorId)->orderByDesc('date')->first();
        $visitsCount = Appointment::where('doctor_id', $doctorId)->where('patient_id', $patient->id)->count();

        return [
            'id' => 'p' . $patient->id,
            'name' => $patient->name,
            'phone' => $patient->phone_number ?? $profile?->phone_number,
            'age' => $profile?->date_of_birth ? \Carbon\Carbon::parse($profile->date_of_birth)->age : null,
            'gender' => $profile?->gender,
            'visitsCount' => $visitsCount,
            'lastVisitDate' => $lastVisit?->date?->toISOString(),
            'lastDiagnosis' => $lastVisit?->diagnosis,
        ];
    }
}
