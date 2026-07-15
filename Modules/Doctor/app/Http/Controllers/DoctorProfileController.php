<?php

namespace Modules\Doctor\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Clinic;
use App\Models\ClinicService;
use App\Support\WorkingHoursFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DoctorProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load(['doctorProfile', 'clinics.services']);

        $profile = $user->doctorProfile;
        $clinics = $user->clinics->map(fn ($clinic) => $this->formatClinic($clinic));

        $services = $user->clinics->flatMap(fn ($clinic) => $clinic->services)->map(fn ($s) => [
            'id' => 'srv_' . $s->id,
            'name' => ucwords(str_replace('_', ' ', $s->type)),
            'isEnabled' => (bool) $s->is_enabled,
            'price' => (float) $s->price,
            'currency' => $s->currency ?? 'EGP',
        ])->values();

        $accountNumber = $profile?->account_number;
        $maskedAccount = $accountNumber ? '**** **** **** ' . substr($accountNumber, -4) : null;

        return ApiResponse::data([
            'id' => 'doc_' . $user->id,
            'fullName' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone_number,
            'specialty' => $profile?->specialty,
            'subSpecialty' => $profile?->sub_specialty ?? '',
            'yearsOfExperience' => (string) ($profile?->experience_years ?? ''),
            'about' => $profile?->about,
            'education' => $profile?->education,
            'imageUrl' => $profile?->image_url,
            'isPro' => (bool) ($profile?->is_pro ?? false),
            'clinics' => $clinics,
            'services' => $services,
            'paymentDetails' => [
                'paymentType' => $profile?->payment_type,
                'accountHolderName' => $profile?->account_holder_name,
                'bankName' => $profile?->bank_name,
                'accountNumber' => $maskedAccount,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'fullName' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'subSpecialty' => 'nullable|string|max:255',
            'yearsOfExperience' => 'nullable|integer',
            'about' => 'nullable|string',
            'education' => 'nullable|string',
        ]);

        $user = $request->user();

        if (isset($validated['fullName'])) {
            $user->update(['name' => $validated['fullName']]);
        }

        $user->doctorProfile()->updateOrCreate(
            ['user_id' => $user->id],
            array_filter([
                'specialty' => $validated['specialty'] ?? null,
                'sub_specialty' => $validated['subSpecialty'] ?? null,
                'experience_years' => $validated['yearsOfExperience'] ?? null,
                'about' => $validated['about'] ?? null,
                'education' => $validated['education'] ?? null,
            ], fn ($v) => $v !== null)
        );

        return ApiResponse::message('Profile updated successfully');
    }

    public function storeClinic(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'governorate' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'fullAddress' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:5120',
            'consultationTime' => 'nullable|integer',
            'workingHours' => 'nullable',
            'clinicVisitPrice' => 'nullable|numeric',
            'followUpPrice' => 'nullable|numeric',
            'homeVisitPrice' => 'nullable|numeric',
            'videoCallPrice' => 'nullable|numeric',
        ]);

        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photos[] = Storage::disk('public')->url($photo->store('clinics', 'public'));
            }
        }

        $clinic = Clinic::create([
            'doctor_id' => $request->user()->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'governorate' => $validated['governorate'] ?? null,
            'city' => $validated['city'] ?? null,
            'address' => $validated['fullAddress'] ?? null,
            'consultation_time' => $validated['consultationTime'] ?? null,
            'photos' => $photos ?: null,
            'working_hours' => WorkingHoursFormatter::parseClinicWorkingHours($validated['workingHours'] ?? null),
            'clinic_visit_price' => $validated['clinicVisitPrice'] ?? null,
            'follow_up_price' => $validated['followUpPrice'] ?? null,
            'home_visit_price' => $validated['homeVisitPrice'] ?? null,
            'video_call_price' => $validated['videoCallPrice'] ?? null,
        ]);

        return ApiResponse::message('Clinic added successfully', ['id' => 'clinic_' . $clinic->id], 201);
    }

    public function updateClinic(Request $request, int $clinicId)
    {
        $clinic = Clinic::where('doctor_id', $request->user()->id)->where('id', $clinicId)->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:30',
            'governorate' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'fullAddress' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:5120',
            'consultationTime' => 'nullable|integer',
            'workingHours' => 'nullable',
            'clinicVisitPrice' => 'nullable|numeric',
            'followUpPrice' => 'nullable|numeric',
            'homeVisitPrice' => 'nullable|numeric',
            'videoCallPrice' => 'nullable|numeric',
        ]);

        $photos = $clinic->photos ?? [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photos[] = Storage::disk('public')->url($photo->store('clinics', 'public'));
            }
        }

        $clinic->update(array_filter([
            'name' => $validated['name'] ?? $clinic->name,
            'phone' => $validated['phone'] ?? $clinic->phone,
            'governorate' => $validated['governorate'] ?? $clinic->governorate,
            'city' => $validated['city'] ?? $clinic->city,
            'address' => $validated['fullAddress'] ?? $clinic->address,
            'consultation_time' => $validated['consultationTime'] ?? $clinic->consultation_time,
            'working_hours' => array_key_exists('workingHours', $validated)
                ? WorkingHoursFormatter::parseClinicWorkingHours($validated['workingHours'])
                : $clinic->working_hours,
            'photos' => $photos,
            'clinic_visit_price' => $validated['clinicVisitPrice'] ?? $clinic->clinic_visit_price,
            'follow_up_price' => $validated['followUpPrice'] ?? $clinic->follow_up_price,
            'home_visit_price' => $validated['homeVisitPrice'] ?? $clinic->home_visit_price,
            'video_call_price' => $validated['videoCallPrice'] ?? $clinic->video_call_price,
        ], fn ($v) => $v !== null));

        return ApiResponse::message('Clinic updated successfully');
    }

    public function updateServices(Request $request)
    {
        $validated = $request->validate([
            'services' => 'required|array',
            'services.*.id' => 'required|string',
            'services.*.isEnabled' => 'nullable|boolean',
            'services.*.price' => 'nullable|numeric',
            'services.*.currency' => 'nullable|string',
        ]);

        foreach ($validated['services'] as $serviceData) {
            $serviceId = (int) str_replace('srv_', '', $serviceData['id']);
            $service = ClinicService::whereHas('clinic', fn ($q) => $q->where('doctor_id', $request->user()->id))
                ->where('id', $serviceId)
                ->first();

            if ($service) {
                $service->update(array_filter([
                    'is_enabled' => $serviceData['isEnabled'] ?? $service->is_enabled,
                    'price' => $serviceData['price'] ?? $service->price,
                    'currency' => $serviceData['currency'] ?? $service->currency,
                ], fn ($v) => $v !== null));
            }
        }

        return ApiResponse::message('Services updated successfully');
    }

    public function updatePaymentDetails(Request $request)
    {
        $validated = $request->validate([
            'paymentType' => 'required|in:bank_transfer,mobile_wallet',
            'accountHolderName' => 'nullable|string|max:255',
            'bankName' => 'nullable|string|max:255',
            'accountNumber' => 'nullable|string|max:255',
        ]);

        $request->user()->doctorProfile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'payment_type' => $validated['paymentType'],
                'account_holder_name' => $validated['accountHolderName'] ?? null,
                'bank_name' => $validated['bankName'] ?? null,
                'account_number' => $validated['accountNumber'] ?? null,
            ]
        );

        return ApiResponse::message('Payment details updated successfully');
    }

    private function formatClinic(Clinic $clinic): array
    {
        return [
            'id' => 'clinic_' . $clinic->id,
            'name' => $clinic->name,
            'phone' => $clinic->phone,
            'governorate' => $clinic->governorate,
            'city' => $clinic->city,
            'fullAddress' => $clinic->address,
            'photos' => $clinic->photos ?? [],
            'consultationTime' => $clinic->consultation_time,
            'workingHours' => $clinic->working_hours ?? [],
            'clinicVisitPrice' => (float) ($clinic->clinic_visit_price ?? 0),
            'followUpPrice' => (float) ($clinic->follow_up_price ?? 0),
            'homeVisitPrice' => (float) ($clinic->home_visit_price ?? 0),
            'videoCallPrice' => (float) ($clinic->video_call_price ?? 0),
        ];
    }
}
