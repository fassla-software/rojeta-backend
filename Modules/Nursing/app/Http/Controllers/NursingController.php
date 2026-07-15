<?php

namespace Modules\Nursing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Booking;
use App\Models\FinancialTransaction;
use App\Models\NursingService;
use App\Models\NursingStaff;
use App\Models\WorkingHour;
use App\Support\TimeFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NursingController extends Controller
{
    public function dashboard(Request $request)
    {
        $officeId = $request->user()->id;
        $today = Carbon::today();

        $todaysBookings = Booking::where('provider_id', $officeId)->whereDate('date', $today)->count();
        $yesterdayBookings = Booking::where('provider_id', $officeId)->whereDate('date', $today->copy()->subDay())->count();
        $bookingsTrend = $this->formatBookingsTrend($todaysBookings, $yesterdayBookings);
        $activeNurses = NursingStaff::where('nursing_office_id', $officeId)->whereIn('status', ['available', 'on_duty'])->count();
        $onDuty = NursingStaff::where('nursing_office_id', $officeId)->where('status', 'on_duty')->count();
        $available = NursingStaff::where('nursing_office_id', $officeId)->where('status', 'available')->count();
        $earnings = FinancialTransaction::where('provider_id', $officeId)
            ->where('transaction_date', '>=', Carbon::now()->startOfWeek())
            ->sum('earning');

        $upcoming = Booking::where('provider_id', $officeId)
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->orderBy('time')
            ->limit(3)
            ->get()
            ->map(fn ($b) => [
                'dateMonth' => strtoupper($b->date?->format('M')),
                'dateDay' => $b->date?->format('d'),
                'patientName' => $b->patient_name,
                'serviceType' => $b->service_name,
                'time' => TimeFormatter::toDisplay($b->time),
            ]);

        return ApiResponse::data([
            'todaysBookings' => $todaysBookings,
            'bookingsTrend' => $bookingsTrend,
            'activeNurses' => $activeNurses,
            'activeNursesDesc' => "{$onDuty} on duty, {$available} available",
            'totalEarnings' => (float) $earnings,
            'earningsDesc' => 'This week',
            'upcomingBookings' => $upcoming,
        ]);
    }

    public function services(Request $request)
    {
        $services = NursingService::where('nursing_office_id', $request->user()->id)->get()->map(fn ($s) => [
            'title' => $s->title,
            'subtitle' => $s->subtitle,
            'durationTag' => $s->duration_tag,
            'bulletPoints' => $s->bullet_points ?? [],
        ]);

        return ApiResponse::data($services);
    }

    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string',
            'durationTag' => 'nullable|string',
            'bulletPoints' => 'nullable|array',
        ]);

        NursingService::create([
            'nursing_office_id' => $request->user()->id,
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'duration_tag' => $validated['durationTag'] ?? null,
            'bullet_points' => $validated['bulletPoints'] ?? [],
        ]);

        return ApiResponse::message('Service added successfully');
    }

    public function staff(Request $request)
    {
        $staff = NursingStaff::where('nursing_office_id', $request->user()->id)->get();

        return ApiResponse::data([
            'stats' => [
                'totalStaff' => $staff->count(),
                'available' => $staff->where('status', 'available')->count(),
                'onDuty' => $staff->where('status', 'on_duty')->count(),
                'onLeave' => $staff->where('status', 'on_leave')->count(),
            ],
            'staff' => $staff->map(fn ($s) => [
                'id' => 'ns' . $s->id,
                'name' => $s->name,
                'imageUrl' => $s->image_url,
                'gender' => $s->gender,
                'status' => $s->status,
                'skills' => $s->skills ?? [],
                'yearsOfExperience' => $s->years_of_experience,
            ]),
        ]);
    }

    public function storeStaff(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'imageUrl' => 'nullable|string',
            'gender' => 'nullable|string',
            'status' => 'nullable|string',
            'skills' => 'nullable|array',
            'yearsOfExperience' => 'nullable|integer',
        ]);

        $staff = NursingStaff::create([
            'nursing_office_id' => $request->user()->id,
            'name' => $validated['name'],
            'image_url' => $validated['imageUrl'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'status' => $validated['status'] ?? 'available',
            'skills' => $validated['skills'] ?? [],
            'years_of_experience' => $validated['yearsOfExperience'] ?? 0,
        ]);

        return ApiResponse::message('Staff member added successfully', ['id' => 'ns' . $staff->id], 201);
    }

    public function profile(Request $request)
    {
        $profile = $request->user()->nursingProfile;

        return ApiResponse::data([
            'id' => 'no_' . $request->user()->id,
            'name' => $profile?->office_name ?? $request->user()->name,
            'licenseNumber' => $profile?->license_number,
            'avatarUrl' => $profile?->avatar_url,
            'servicesCount' => NursingService::where('nursing_office_id', $request->user()->id)->count(),
            'nursesCount' => NursingStaff::where('nursing_office_id', $request->user()->id)->count(),
        ]);
    }

    public function officeInfo(Request $request)
    {
        $profile = $request->user()->nursingProfile;

        return ApiResponse::data([
            'officeName' => $profile?->office_name,
            'licenseNumber' => $profile?->license_number,
            'email' => $profile?->email,
            'phone' => $profile?->phone_number,
            'description' => $profile?->description,
            'verificationStatus' => $profile?->verification_status,
        ]);
    }

    public function updateOfficeInfo(Request $request)
    {
        $validated = $request->validate([
            'officeName' => 'nullable|string|max:255',
            'licenseNumber' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
            'description' => 'nullable|string',
        ]);

        $request->user()->nursingProfile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            array_filter([
                'office_name' => $validated['officeName'] ?? null,
                'license_number' => $validated['licenseNumber'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone_number' => $validated['phone'] ?? null,
                'description' => $validated['description'] ?? null,
            ], fn ($v) => $v !== null)
        );

        return ApiResponse::message('Office info updated successfully');
    }

    public function workingHours(Request $request)
    {
        $hours = WorkingHour::where('user_id', $request->user()->id)->get();

        return ApiResponse::data([
            'days' => $hours->map(fn ($h) => [
                'day' => $h->day,
                'isOpen' => !$h->is_closed,
                'openTime' => TimeFormatter::toDisplay($h->from_time),
                'closeTime' => TimeFormatter::toDisplay($h->to_time),
            ]),
        ]);
    }

    public function updateWorkingHours(Request $request)
    {
        $validated = $request->validate([
            'days' => 'required|array',
            'days.*.day' => 'required|string',
            'days.*.isOpen' => 'required|boolean',
            'days.*.openTime' => 'nullable|string',
            'days.*.closeTime' => 'nullable|string',
        ]);

        WorkingHour::where('user_id', $request->user()->id)->delete();

        foreach ($validated['days'] as $day) {
            WorkingHour::create([
                'user_id' => $request->user()->id,
                'day' => $day['day'],
                'is_closed' => !$day['isOpen'],
                'from_time' => \App\Support\TimeFormatter::toDatabase($day['openTime'] ?? null),
                'to_time' => \App\Support\TimeFormatter::toDatabase($day['closeTime'] ?? null),
            ]);
        }

        return ApiResponse::message('Working hours updated successfully');
    }

    public function settings(Request $request)
    {
        $profile = $request->user()->nursingProfile;
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

        $profile = $request->user()->nursingProfile;
        $current = $profile?->settings ?? [];
        $merged = array_merge($current, array_filter($validated, fn ($v) => $v !== null));

        $request->user()->nursingProfile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['settings' => $merged]
        );

        return ApiResponse::message('Settings updated successfully');
    }

    private function formatBookingsTrend(int $today, int $yesterday): string
    {
        if ($yesterday === 0) {
            return $today > 0 ? '+100% from yesterday' : '0% from yesterday';
        }

        $percent = (int) round((($today - $yesterday) / $yesterday) * 100);

        return sprintf('%+d%% from yesterday', $percent);
    }
}
