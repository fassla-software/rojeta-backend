<?php

namespace Modules\Laboratory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Booking;
use App\Models\LabBranch;
use App\Models\LabHomeVisitConfig;
use App\Models\LabService;
use App\Models\LabServiceArea;
use App\Models\LabTimeSlot;
use App\Models\WorkingHour;
use App\Support\TimeFormatter;
use App\Support\WorkingHoursFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaboratoryController extends Controller
{
    public function dashboard(Request $request)
    {
        $providerId = $request->user()->id;
        $today = Carbon::today();

        $todayBookings = Booking::where('provider_id', $providerId)->whereDate('date', $today)->count();
        $completedToday = Booking::where('provider_id', $providerId)->whereDate('date', $today)->where('status', 'completed')->count();
        $pending = Booking::where('provider_id', $providerId)->where('status', 'pending')->count();
        $homeVisits = Booking::where('provider_id', $providerId)->where('type', 'homeVisit')->whereDate('date', $today)->count();

        $upcoming = Booking::where('provider_id', $providerId)
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->orderBy('time')
            ->first();

        return ApiResponse::data(array_filter([
            'todayBookings' => $todayBookings,
            'todayBookingsProgress' => "{$completedToday}/{$todayBookings}",
            'pendingRequests' => $pending,
            'homeVisits' => $homeVisits,
            'upcomingBooking' => $upcoming ? [
                'patientName' => $upcoming->patient_name,
                'testCategory' => $upcoming->service_name,
                'appointmentTime' => TimeFormatter::toDisplay($upcoming->time),
                'appointmentDate' => $upcoming->date?->format('Y-m-d'),
            ] : null,
            'specialOffer' => $this->resolveSpecialOffer($request),
        ], fn ($v) => $v !== null));
    }

    public function services(Request $request)
    {
        $query = LabService::where('provider_id', $request->user()->id);

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        $services = $query->get()->map(fn ($s) => [
            'id' => 'ls' . $s->id,
            'name' => $s->name,
            'price' => (float) $s->price,
            'isHomeCollectionAvailable' => (bool) $s->is_home_collection_available,
            'turnaroundTime' => $s->turnaround_time,
            'category' => $s->category,
        ]);

        return ApiResponse::data($services);
    }

    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'isHomeCollectionAvailable' => 'nullable|boolean',
            'turnaroundTime' => 'nullable|string',
            'category' => 'nullable|string',
        ]);

        $service = LabService::create([
            'provider_id' => $request->user()->id,
            'provider_role' => $request->user()->role,
            'name' => $validated['name'],
            'price' => $validated['price'],
            'is_home_collection_available' => $validated['isHomeCollectionAvailable'] ?? false,
            'turnaround_time' => $validated['turnaroundTime'] ?? null,
            'category' => $validated['category'] ?? null,
        ]);

        return ApiResponse::message('Service added successfully', ['id' => 'ls' . $service->id], 201);
    }

    public function branches(Request $request)
    {
        $branches = LabBranch::where('provider_id', $request->user()->id)->get()->map(fn ($b) => [
            'id' => 'br' . $b->id,
            'name' => $b->name,
            'address' => $b->address,
            'phone' => $b->phone,
            'workingHours' => $b->working_hours,
            'isHomeCollectionAvailable' => (bool) $b->is_home_collection_available,
            'imageUrl' => $b->image_url,
        ]);

        return ApiResponse::data($branches);
    }

    public function storeBranch(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'workingHours' => 'nullable|string',
            'isHomeCollectionAvailable' => 'nullable|boolean',
            'imageUrl' => 'nullable|string',
        ]);

        $branch = LabBranch::create([
            'provider_id' => $request->user()->id,
            'provider_role' => $request->user()->role,
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'working_hours' => $validated['workingHours'] ?? null,
            'is_home_collection_available' => $validated['isHomeCollectionAvailable'] ?? false,
            'image_url' => $validated['imageUrl'] ?? null,
        ]);

        return ApiResponse::message('Branch added successfully', ['id' => 'br' . $branch->id], 201);
    }

    public function homeVisitConfig(Request $request)
    {
        $config = LabHomeVisitConfig::with(['serviceAreas', 'timeSlots'])
            ->where('provider_id', $request->user()->id)
            ->first();

        if (!$config) {
            return ApiResponse::data([
                'isServiceVisible' => false,
                'serviceAreas' => [],
                'collectionFee' => 0,
                'minimumBookingAmount' => 0,
                'weekdaySlots' => [],
                'weekendSlots' => [],
            ]);
        }

        return ApiResponse::data($this->formatHomeVisitConfig($config));
    }

    public function updateHomeVisitConfig(Request $request)
    {
        $validated = $request->validate([
            'isServiceVisible' => 'nullable|boolean',
            'serviceAreas' => 'nullable|array',
            'collectionFee' => 'nullable|numeric',
            'minimumBookingAmount' => 'nullable|numeric',
            'weekdaySlots' => 'nullable|array',
            'weekendSlots' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $config = LabHomeVisitConfig::updateOrCreate(
                ['provider_id' => $request->user()->id],
                [
                    'provider_role' => $request->user()->role,
                    'is_service_visible' => $validated['isServiceVisible'] ?? true,
                    'collection_fee' => $validated['collectionFee'] ?? 0,
                    'minimum_booking_amount' => $validated['minimumBookingAmount'] ?? 0,
                ]
            );

            if (isset($validated['serviceAreas'])) {
                LabServiceArea::where('config_id', $config->id)->delete();
                foreach ($validated['serviceAreas'] as $area) {
                    LabServiceArea::create([
                        'config_id' => $config->id,
                        'name' => $area['name'],
                        'radius_km' => $area['radiusKm'] ?? 10,
                        'techs_available' => $area['techsAvailable'] ?? 1,
                    ]);
                }
            }

            if (isset($validated['weekdaySlots']) || isset($validated['weekendSlots'])) {
                LabTimeSlot::where('config_id', $config->id)->delete();
                foreach ($validated['weekdaySlots'] ?? [] as $slot) {
                    LabTimeSlot::create([
                        'config_id' => $config->id,
                        'slot_type' => 'weekday',
                        'time' => $slot['time'],
                        'am_pm' => $slot['amPm'] ?? 'AM',
                        'status' => $slot['status'] ?? 'active',
                    ]);
                }
                foreach ($validated['weekendSlots'] ?? [] as $slot) {
                    LabTimeSlot::create([
                        'config_id' => $config->id,
                        'slot_type' => 'weekend',
                        'time' => $slot['time'],
                        'am_pm' => $slot['amPm'] ?? 'AM',
                        'status' => $slot['status'] ?? 'active',
                    ]);
                }
            }
        });

        return ApiResponse::message('Home visit configuration updated successfully');
    }

    public function profile(Request $request)
    {
        $profile = $this->getLabProfile($request);

        return ApiResponse::data([
            'labName' => $profile?->lab_name,
            'licenseNo' => $profile?->license_number,
            'branchesCount' => LabBranch::where('provider_id', $request->user()->id)->count(),
            'scansCount' => LabService::where('provider_id', $request->user()->id)->count(),
            'iconUrl' => $profile?->icon_url,
        ]);
    }

    public function info(Request $request)
    {
        $profile = $this->getLabProfile($request);

        return ApiResponse::data([
            'labName' => $profile?->lab_name,
            'licenseNumber' => $profile?->license_number,
            'primaryContactEmail' => $profile?->primary_contact_email,
            'phoneNumber' => $profile?->phone_number,
            'laboratoryDescription' => $profile?->description,
            'isVerified' => (bool) ($profile?->is_verified ?? false),
            'verificationYear' => $profile?->verification_year,
        ]);
    }

    public function updateInfo(Request $request)
    {
        $validated = $request->validate([
            'labName' => 'nullable|string|max:255',
            'licenseNumber' => 'nullable|string|max:255',
            'primaryContactEmail' => 'nullable|email',
            'phoneNumber' => 'nullable|string|max:30',
            'laboratoryDescription' => 'nullable|string',
        ]);

        $this->updateLabProfile($request, [
            'lab_name' => $validated['labName'] ?? null,
            'license_number' => $validated['licenseNumber'] ?? null,
            'primary_contact_email' => $validated['primaryContactEmail'] ?? null,
            'phone_number' => $validated['phoneNumber'] ?? null,
            'description' => $validated['laboratoryDescription'] ?? null,
        ]);

        return ApiResponse::message('Lab information updated successfully');
    }

    public function workingHours(Request $request)
    {
        return ApiResponse::data(
            WorkingHoursFormatter::toLabResponse($request->user()->id)
        );
    }

    public function updateWorkingHours(Request $request)
    {
        $validated = $request->validate([
            'standardWeek' => 'required|array',
            'standardWeek.*.dayName' => 'required|string',
            'standardWeek.*.isClosed' => 'required|boolean',
            'standardWeek.*.openingTime' => 'nullable|string',
            'standardWeek.*.closingTime' => 'nullable|string',
            'upcomingOverrides' => 'nullable|array',
            'upcomingOverrides.*.dateStr' => 'required_with:upcomingOverrides|date',
            'upcomingOverrides.*.eventName' => 'required_with:upcomingOverrides|string',
            'upcomingOverrides.*.isClosed' => 'nullable|boolean',
            'upcomingOverrides.*.openingTime' => 'nullable|string',
            'upcomingOverrides.*.closingTime' => 'nullable|string',
        ]);

        WorkingHoursFormatter::syncLabHours(
            $request->user()->id,
            $validated['standardWeek'],
            $validated['upcomingOverrides'] ?? []
        );

        return ApiResponse::message('Working hours updated successfully');
    }

    public function settings(Request $request)
    {
        $profile = $this->getLabProfile($request);
        $defaults = [
            'emailNotifications' => true,
            'bookingUpdates' => true,
            'pushNotifications' => true,
            'appointmentReminders' => true,
            'marketingEmails' => false,
            'language' => 'English',
        ];

        return ApiResponse::data($profile?->settings ?? $defaults);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'emailNotifications' => 'nullable|boolean',
            'bookingUpdates' => 'nullable|boolean',
            'pushNotifications' => 'nullable|boolean',
            'appointmentReminders' => 'nullable|boolean',
            'marketingEmails' => 'nullable|boolean',
            'language' => 'nullable|string',
        ]);

        $profile = $this->getLabProfile($request);
        $current = $profile?->settings ?? [];
        $merged = array_merge($current, array_filter($validated, fn ($v) => $v !== null));

        $this->updateLabProfile($request, ['settings' => $merged]);

        return ApiResponse::message('Settings updated successfully');
    }

    private function resolveSpecialOffer(Request $request): ?array
    {
        $profile = $this->getLabProfile($request);

        if (!$profile?->special_offer_title) {
            return null;
        }

        return [
            'title' => $profile->special_offer_title,
            'description' => $profile->special_offer_description,
            'discount' => $profile->special_offer_discount,
        ];
    }

    private function getLabProfile(Request $request)
    {
        $user = $request->user();

        return $user->role === 'radiology'
            ? $user->radiologyProfile
            : $user->laboratoryProfile;
    }

    private function updateLabProfile(Request $request, array $data): void
    {
        $user = $request->user();
        $data = array_filter($data, fn ($v) => $v !== null);

        if ($user->role === 'radiology') {
            $user->radiologyProfile()->updateOrCreate(['user_id' => $user->id], $data);
        } else {
            $user->laboratoryProfile()->updateOrCreate(['user_id' => $user->id], $data);
        }
    }

    private function formatHomeVisitConfig(LabHomeVisitConfig $config): array
    {
        return [
            'isServiceVisible' => (bool) $config->is_service_visible,
            'serviceAreas' => $config->serviceAreas->map(fn ($a) => [
                'id' => 'sa' . $a->id,
                'name' => $a->name,
                'radiusKm' => $a->radius_km,
                'techsAvailable' => $a->techs_available,
            ]),
            'collectionFee' => (float) $config->collection_fee,
            'minimumBookingAmount' => (float) $config->minimum_booking_amount,
            'weekdaySlots' => $config->timeSlots->where('slot_type', 'weekday')->map(fn ($s) => [
                'id' => 'ts' . $s->id,
                'time' => $s->time,
                'amPm' => $s->am_pm,
                'status' => $s->status,
            ])->values(),
            'weekendSlots' => $config->timeSlots->where('slot_type', 'weekend')->map(fn ($s) => [
                'id' => 'ts' . $s->id,
                'time' => $s->time,
                'amPm' => $s->am_pm,
                'status' => $s->status,
            ])->values(),
        ];
    }
}
