<?php

namespace Modules\Hospital\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Appointment;
use App\Models\FinancialTransaction;
use App\Models\HospitalLabTest;
use App\Models\HospitalRadiologyService;
use App\Models\HospitalService;
use App\Models\HospitalSpecialty;
use App\Models\HospitalStaff;
use App\Models\IcuRoom;
use App\Models\Incubator;
use App\Models\ProviderSetting;
use App\Models\User;
use App\Support\PaginationHelper;
use App\Support\TimeFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HospitalController extends Controller
{
    public function dashboard(Request $request)
    {
        $hospitalId = $request->user()->id;
        $doctorsCount = HospitalStaff::whereHas('specialty', fn ($q) => $q->where('hospital_id', $hospitalId))->count();
        $todaysIncome = FinancialTransaction::where('provider_id', $hospitalId)
            ->whereDate('transaction_date', Carbon::today())
            ->sum('earning');
        $appointmentsCount = Appointment::where('hospital_id', $hospitalId)
            ->whereDate('date', '>=', Carbon::today())
            ->count();

        return ApiResponse::data([
            'doctors' => (string) $doctorsCount,
            'todaysIncome' => number_format($todaysIncome) . ' EGP',
            'appointments' => (string) $appointmentsCount,
        ]);
    }

    public function appointments(Request $request)
    {
        [$page, $limit] = PaginationHelper::fromRequest($request);

        $query = Appointment::where('hospital_id', $request->user()->id)
            ->with(['patient.patientProfile', 'doctor']);

        if ($request->filled('date')) {
            $query->whereDate('date', $request->query('date'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $paginator = $query->orderByDesc('date')->paginate($limit, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(fn ($apt) => [
            'id' => (string) $apt->id,
            'patientName' => $apt->patient?->name,
            'patientAge' => $apt->patient?->patientProfile?->date_of_birth
                ? (string) Carbon::parse($apt->patient->patientProfile->date_of_birth)->age
                : null,
            'patientGender' => $apt->patient?->patientProfile?->gender,
            'appointmentDate' => $apt->date?->format('Y-m-d'),
            'appointmentTime' => TimeFormatter::toDisplay($apt->time),
            'type' => $apt->doctor?->name,
            'status' => $apt->status === 'confirmed' ? 'scheduled' : $apt->status,
            'isOnline' => (bool) $apt->is_online,
        ]);

        return ApiResponse::paginated($paginator, $data);
    }

    public function services(Request $request)
    {
        $services = HospitalService::where('hospital_id', $request->user()->id)->get()->map(fn ($s) => [
            'id' => (string) $s->id,
            'titleKey' => $s->title_key,
            'descriptionKey' => $s->description_key,
            'isEnabled' => (bool) $s->is_enabled,
            'iconPath' => $s->icon_path,
            'extraInfoKey' => $s->extra_info_key,
            'extraInfoValue' => $s->extra_info_value,
        ]);

        return ApiResponse::data($services);
    }

    public function updateService(Request $request, int $serviceId)
    {
        $service = HospitalService::where('hospital_id', $request->user()->id)
            ->where('id', $serviceId)
            ->firstOrFail();

        $request->validate(['isEnabled' => 'nullable|boolean']);

        if ($request->has('isEnabled')) {
            $service->update(['is_enabled' => $request->boolean('isEnabled')]);
        } else {
            $service->update(['is_enabled' => !$service->is_enabled]);
        }

        return ApiResponse::message('Service updated successfully');
    }

    public function specialties(Request $request)
    {
        $specialties = HospitalSpecialty::where('hospital_id', $request->user()->id)
            ->with('staff')
            ->get()
            ->map(fn ($s) => [
                'id' => 's' . $s->id,
                'name' => $s->name,
                'description' => $s->description,
                'hasEmergency' => (bool) $s->has_emergency,
                'doctorsCount' => $s->staff->filter(fn ($st) => str_contains(strtolower($st->role), 'doctor'))->count(),
                'nursesCount' => $s->staff->filter(fn ($st) => str_contains(strtolower($st->role), 'nurse'))->count(),
                'staffList' => $s->staff->map(fn ($st) => [
                    'id' => 'd' . $st->id,
                    'name' => $st->name,
                    'role' => $st->role,
                    'price' => $st->price,
                    'availability' => $st->availability,
                    'imagePath' => $st->image_path,
                ]),
            ]);

        return ApiResponse::data($specialties);
    }

    public function storeSpecialty(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hasEmergency' => 'nullable|boolean',
        ]);

        $specialty = HospitalSpecialty::create([
            'hospital_id' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'has_emergency' => $validated['hasEmergency'] ?? false,
        ]);

        return ApiResponse::message('Specialty added successfully', ['id' => 's' . $specialty->id], 201);
    }

    public function storeStaff(Request $request, int $specialtyId)
    {
        $specialty = HospitalSpecialty::where('hospital_id', $request->user()->id)
            ->where('id', $specialtyId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'price' => 'nullable|string',
            'availability' => 'nullable|string',
            'imagePath' => 'nullable|string',
        ]);

        $staff = HospitalStaff::create([
            'specialty_id' => $specialty->id,
            'name' => $validated['name'],
            'role' => $validated['role'],
            'price' => $validated['price'] ?? null,
            'availability' => $validated['availability'] ?? null,
            'image_path' => $validated['imagePath'] ?? null,
        ]);

        return ApiResponse::message('Staff member added successfully', ['id' => 'd' . $staff->id], 201);
    }

    public function icuRooms(Request $request)
    {
        $rooms = IcuRoom::where('hospital_id', $request->user()->id)->get()->map(fn ($r) => [
            'id' => 'icu' . $r->id,
            'roomNumber' => $r->room_number,
            'bedId' => $r->bed_id,
            'floorWing' => $r->floor_wing,
            'equipmentList' => $r->equipment_list ?? [],
            'status' => $r->status,
            'assignedPatient' => $r->assigned_patient,
        ]);

        return ApiResponse::data($rooms);
    }

    public function incubators(Request $request)
    {
        $items = Incubator::where('hospital_id', $request->user()->id)->get()->map(fn ($i) => [
            'id' => 'inc' . $i->id,
            'unitId' => $i->unit_id,
            'model' => $i->model,
            'wingSection' => $i->wing_section,
            'monitoringType' => $i->monitoring_type ?? [],
            'status' => $i->status,
            'temperature' => (float) $i->temperature,
            'humidity' => $i->humidity,
        ]);

        return ApiResponse::data($items);
    }

    public function labTests(Request $request)
    {
        $tests = HospitalLabTest::where('hospital_id', $request->user()->id)->get()->map(fn ($t) => [
            'id' => 'lt' . $t->id,
            'name' => $t->name,
            'price' => (float) $t->price,
            'category' => $t->category,
            'homeCollectionAvailable' => (bool) $t->home_collection_available,
            'preparationInstructions' => $t->preparation_instructions,
            'estimatedResultTime' => $t->estimated_result_time,
        ]);

        return ApiResponse::data($tests);
    }

    public function radiologyServices(Request $request)
    {
        $services = HospitalRadiologyService::where('hospital_id', $request->user()->id)->get()->map(fn ($s) => [
            'id' => 'rs' . $s->id,
            'name' => $s->name,
            'price' => (float) $s->price,
            'category' => $s->category,
            'contrastAgentRequired' => (bool) $s->contrast_agent_required,
            'preparationInstructions' => $s->preparation_instructions,
            'estimatedDuration' => $s->estimated_duration,
        ]);

        return ApiResponse::data($services);
    }

    public function settings(Request $request)
    {
        $defaults = [
            'pushNotificationsEnabled' => true,
            'languageCode' => 'en',
            'newBookingsEnabled' => true,
            'appointmentRemindersEnabled' => true,
            'emergencyRequestsEnabled' => true,
            'paymentNotificationsEnabled' => false,
        ];

        $profile = $request->user()->hospitalProfile;

        return ApiResponse::data($profile?->settings ?? $defaults);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'pushNotificationsEnabled' => 'nullable|boolean',
            'languageCode' => 'nullable|string',
            'newBookingsEnabled' => 'nullable|boolean',
            'appointmentRemindersEnabled' => 'nullable|boolean',
            'emergencyRequestsEnabled' => 'nullable|boolean',
            'paymentNotificationsEnabled' => 'nullable|boolean',
        ]);

        $profile = $request->user()->hospitalProfile;
        $current = $profile?->settings ?? [];
        $merged = array_merge($current, array_filter($validated, fn ($v) => $v !== null));

        $request->user()->hospitalProfile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['settings' => $merged]
        );

        return ApiResponse::message('Settings updated successfully');
    }
}
