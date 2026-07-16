<?php

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function login(Request $request)
{
    $validated = $request->validate([
        'phone'    => ['required', 'string', 'max:30'],
        'password' => ['required', 'string'],
    ]);

    $user = $this->findUserByPhone($validated['phone']);

    if (! $user) {
        return response()->json([
            'message' => 'Invalid phone or password',
        ], 422);
    }

    if (! Hash::check($validated['password'], $user->password)) {
        return response()->json([
            'message' => 'Invalid phone or password',
        ], 422);
    }

    // optional: revoke old tokens (security best practice)
    $user->tokens()->delete();

    $token = $user->createToken('auth-token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'phone_number'  => $user->phone_number,
            'role'          => $user->role,
            'profile'       => $this->resolveProfileData($user->role, $user->id),
        ],
    ]);
}
    /**
     * Show the form for creating a new resource.
     */
public function resendOtp(Request $request)
{
    $validated = $request->validate([
        'phone' => ['required', 'string', 'max:30'],
    ]);

    $user = $this->findUserByPhone($validated['phone']);

    $purpose = $user ? 'reset' : 'register';

    DB::table('auth_otp_codes')
        ->where('phone', $validated['phone'])
        ->where('purpose', $purpose)
        ->whereNull('consumed_at')
        ->update([
            'consumed_at' => now(),
            'updated_at'  => now(),
        ]);

    $otpData = $this->createOtpRecord($validated['phone'], $purpose);

    return response()->json([
        'message' => 'OTP resent successfully',
        'otp' => app()->environment(['local', 'testing']) ? $otpData['otp'] : null,
    ]);
}


    public function checkPhone(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $phone = $validated['phone'];

        $exists = false;

        if (Schema::hasColumn('users', 'phone_number')) {
            $exists = DB::table('users')->where('phone_number', $phone)->exists();
        }

        if (! $exists && Schema::hasColumn('users', 'mobile')) {
            $exists = DB::table('users')->where('mobile', $phone)->exists();
        }

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Phone number already registered' : 'Phone number is available',
        ]);
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $user = $this->findUserByPhone($validated['phone']);

        if (! $user) {
            return response()->json([
                'message' => 'Phone number is not registered',
            ], 404);
        }

        $otp = $this->issueOtp($validated['phone'], 'reset');

        $response = [
            'message' => 'OTP sent successfully',
        ];

        if (app()->environment(['local', 'testing'])) {
            $response['otp'] = $otp;
        }

        return response()->json($response);
    }


    public function verifyOtp(Request $request)
{
    $validated = $request->validate([
        'phone' => ['required', 'string', 'max:30'],
        'code'  => ['required', 'string', 'max:10'],
    ]);

    $record = DB::table('auth_otp_codes')
        ->where('phone', $validated['phone'])
        ->whereNull('consumed_at')
        ->whereNull('verified_at')
        ->orderByDesc('id')
        ->first();

    if (! $record) {
        return response()->json([
            'message' => 'Invalid or expired OTP',
        ], 422);
    }

    if (Carbon::parse($record->code_expires_at)->isPast()) {
        return response()->json([
            'message' => 'Invalid or expired OTP',
        ], 422);
    }

    if (! Hash::check($validated['code'], $record->code_hash)) {
        DB::table('auth_otp_codes')
            ->where('id', $record->id)
            ->increment('attempts');

        return response()->json([
            'message' => 'Invalid OTP code',
        ], 422);
    }

    // Mark OTP as verified
    DB::table('auth_otp_codes')
        ->where('id', $record->id)
        ->update([
            'verified_at' => now(),
            'updated_at'  => now(),
        ]);

    $user = $this->findUserByPhone($validated['phone']);

    /**
     * =========================
     * FORGOT PASSWORD FLOW
     * =========================
     */
  if ($user) {

    $verificationToken = Str::random(64);

    DB::table('auth_otp_codes')->where('id', $record->id)->update([
        'verified_at' => now(),
        'purpose' => 'reset', // 👈 مهم جدًا
        'verification_token' => $verificationToken,
        'token_expires_at' => now()->addMinutes(15),
        'updated_at' => now(),
    ]);

    return response()->json([
        'message' => 'Phone verified successfully',
        'verification_token' => $verificationToken,
    ]);
}

    /**
     * =========================
     * REGISTER FLOW
     * =========================
     */

    $user = User::create([
        'phone_number' => $validated['phone'],
        // حط باقي الحقول لو عندك
    ]);

    DB::table('auth_otp_codes')
        ->where('id', $record->id)
        ->update([
            'consumed_at' => now(),
            'updated_at'  => now(),
        ]);

    $token = $user->createToken('auth-token')->plainTextToken;

    return response()->json([
        'message' => 'Phone verified successfully',
        'token' => $token,
        'user' => [
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'phone_number'  => $user->phone_number,
            'role'          => $user->role,
            'profile'       => $this->resolveProfileData($user->role, $user->id),
        ],
    ]);
}

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'verification_token' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('auth_otp_codes')
    ->where('verification_token', $validated['verification_token'])
    ->whereNotNull('verified_at')
    ->whereNull('consumed_at')
    ->first();

        if (! $record || Carbon::parse($record->token_expires_at)->isPast()) {
            return response()->json([
                'message' => 'Invalid or expired verification token',
            ], 422);
        }

        $user = $this->findUserByPhone($record->phone);

        if (! $user) {
            return response()->json([
                'message' => 'Phone number is not registered',
            ], 404);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        DB::table('auth_otp_codes')->where('id', $record->id)->update([
            'consumed_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Password reset successfully',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['doctor', 'hospital', 'laboratory', 'radiology', 'nursing', 'patient'])],

            'clinics' => ['required_if:role,doctor', 'array', 'min:1'],
            'clinics.*.name' => ['required_with:clinics', 'string', 'max:255'],
            'clinics.*.clinic_type' => ['nullable', Rule::in(['online', 'visit'])],
            'clinics.*.phone' => ['nullable', 'string', 'max:30'],
            'clinics.*.consultation_time' => ['nullable', 'integer', 'min:1'],
            'clinics.*.governorate' => ['nullable', 'string', 'max:255'],
            'clinics.*.city' => ['nullable', 'string', 'max:255'],
            'clinics.*.full_address' => ['nullable', 'string'],
            'clinics.*.photos' => ['nullable', 'array', 'max:6'],
            'clinics.*.photos.*' => ['nullable'],
            'clinics.*.services' => ['nullable', 'array'],
            'clinics.*.services.*.type' => ['required_with:clinics.*.services', Rule::in(['visit', 'follow_up'])],
            'clinics.*.services.*.price' => ['required_with:clinics.*.services', 'numeric', 'min:0'],
            'clinics.*.services.*.duration' => ['nullable', 'integer', 'min:0'],
            'clinics.*.working_hours' => ['nullable', 'array'],
            'clinics.*.working_hours.*.day' => ['required_with:clinics.*.working_hours', 'string', 'max:20'],
            'clinics.*.working_hours.*.from_time' => ['nullable', 'string', 'max:20'],
            'clinics.*.working_hours.*.to_time' => ['nullable', 'string', 'max:20'],
            'clinics.*.working_hours.*.is_closed' => ['nullable', 'boolean'],

            'organization_name' => ['nullable', 'string', 'max:255'],
            'organization_type' => ['nullable', 'string', 'max:255'],
            'organization_number' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string'],
            'logo' => ['nullable', 'string', 'max:255'],
            'governorate' => ['required_if:role,laboratory', 'string', 'max:255'],
            'city' => ['required_if:role,laboratory', 'string', 'max:255'],
            'full_address' => ['required_if:role,laboratory', 'string'],
            'license_number' => ['required_if:role,laboratory', 'string', 'max:255'],
            'medical_license' => ['nullable', 'string', 'max:255'],
            'certificates' => ['nullable', 'array'],
            'certificates.*' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'string', 'max:255'],
            'working_hours' => ['nullable', 'array'],
            'working_hours.*.day' => ['required_with:working_hours', 'string', 'max:20'],
            'working_hours.*.from_time' => ['nullable', 'string', 'max:20'],
            'working_hours.*.to_time' => ['nullable', 'string', 'max:20'],
            'working_hours.*.is_closed' => ['nullable', 'boolean'],

            'specialty' => ['required_if:role,doctor', 'nullable', 'string', 'max:255'],
            'sub_specialty' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'education' => ['nullable', 'string'],
            'home_visit_price' => ['nullable', 'numeric', 'min:0'],
            'call_price' => ['nullable', 'numeric', 'min:0'],
            'chat_price' => ['nullable', 'numeric', 'min:0'],

            'tax_id' => ['nullable', 'string', 'max:255'],
            'home_sample_collection' => ['nullable', 'boolean'],
            'collection_fee' => ['nullable', 'numeric', 'min:0'],
            'service_radius_km' => ['nullable', 'integer', 'min:0'],

            'first_name' => ['required_if:role,patient', 'nullable', 'string', 'max:255'],
            'last_name' => ['required_if:role,patient', 'nullable', 'string', 'max:255'],
            'phone_number' => ['required_if:role,patient', 'nullable', 'string', 'max:30'],
            'area' => ['nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            if ($validated['role'] !== 'patient') {
                DB::table('user_profiles')->insert([
                    'user_id' => $user->id,
                    'organization_name' => $validated['organization_name'] ?? null,
                    'organization_type' => $validated['organization_type'] ?? null,
                    'organization_number' => $validated['organization_number'] ?? null,
                    'about' => $validated['about'] ?? null,
                    'logo' => $validated['logo'] ?? null,
                    'governorate' => $validated['governorate'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'full_address' => $validated['full_address'] ?? null,
                    'license_number' => $validated['license_number'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            switch ($validated['role']) {
                case 'doctor':
                    DB::table('doctor_profiles')->insert([
                        'user_id' => $user->id,
                        'specialty' => $validated['specialty'],
                        'sub_specialty' => $validated['sub_specialty'] ?? null,
                        'experience_years' => $validated['experience_years'] ?? null,
                        'education' => $validated['education'] ?? null,
                        'about' => $validated['about'] ?? null,
                        'medical_license' => $validated['medical_license'] ?? null,
                        'home_visit_price' => $validated['home_visit_price'] ?? null,
                        'call_price' => $validated['call_price'] ?? null,
                        'chat_price' => $validated['chat_price'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($validated['clinics'] ?? [] as $clinicData) {
                        $clinicId = DB::table('clinics')->insertGetId([
                            'doctor_id' => $user->id,
                            'clinic_type' => $clinicData['clinic_type'] ?? null,
                            'name' => $clinicData['name'],
                            'phone' => $clinicData['phone'] ?? null,
                            'consultation_time' => $clinicData['consultation_time'] ?? null,
                            'governorate' => $clinicData['governorate'] ?? null,
                            'city' => $clinicData['city'] ?? null,
                            'address' => $clinicData['full_address'] ?? null,
                            'photos' => isset($clinicData['photos']) ? json_encode(array_values($clinicData['photos'])) : null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        foreach ($clinicData['services'] ?? [] as $serviceData) {
                            DB::table('clinic_services')->insert([
                                'clinic_id' => $clinicId,
                                'type' => $serviceData['type'],
                                'price' => $serviceData['price'],
                                'duration' => $serviceData['duration'] ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        foreach ($clinicData['working_hours'] ?? [] as $workingHour) {
                            DB::table('working_hours')->insert([
                                'user_id' => $user->id,
                                'context_type' => 'clinic:' . $clinicId,
                                'day' => $workingHour['day'],
                                'from_time' => $this->normalizeTime($workingHour['from_time'] ?? null),
                                'to_time' => $this->normalizeTime($workingHour['to_time'] ?? null),
                                'is_closed' => (bool) ($workingHour['is_closed'] ?? false),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                    break;

                case 'hospital':
                    DB::table('hospital_profiles')->insert([
                        'user_id' => $user->id,
                        'tax_id' => $validated['tax_id'] ?? null,
                        'medical_license' => $validated['medical_license'] ?? null,
                        'certificates' => isset($validated['certificates']) ? json_encode(array_values($validated['certificates'])) : null,
                        'images' => isset($validated['images']) ? json_encode(array_values($validated['images'])) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    break;

                case 'laboratory':
                    DB::table('laboratory_profiles')->insert([
                        'user_id' => $user->id,
                        'home_sample_collection' => (bool) ($validated['home_sample_collection'] ?? false),
                        'collection_fee' => $validated['collection_fee'] ?? null,
                        'service_radius_km' => $validated['service_radius_km'] ?? null,
                        'tax_id' => $validated['tax_id'] ?? null,
                        'medical_license' => $validated['medical_license'] ?? null,
                        'certificates' => isset($validated['certificates']) ? json_encode(array_values($validated['certificates'])) : null,
                        'images' => isset($validated['images']) ? json_encode(array_values($validated['images'])) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($validated['working_hours'] ?? [] as $workingHour) {
                        DB::table('working_hours')->insert([
                            'user_id' => $user->id,
                            'context_type' => 'laboratory',
                            'day' => $workingHour['day'],
                            'from_time' => $this->normalizeTime($workingHour['from_time'] ?? null),
                            'to_time' => $this->normalizeTime($workingHour['to_time'] ?? null),
                            'is_closed' => (bool) ($workingHour['is_closed'] ?? false),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    break;

                case 'radiology':
                    DB::table('radiology_profiles')->insert([
                        'user_id' => $user->id,
                        'home_sample_collection' => (bool) ($validated['home_sample_collection'] ?? false),
                        'collection_fee' => $validated['collection_fee'] ?? null,
                        'service_radius_km' => $validated['service_radius_km'] ?? null,
                        'tax_id' => $validated['tax_id'] ?? null,
                        'medical_license' => $validated['medical_license'] ?? null,
                        'certificates' => isset($validated['certificates']) ? json_encode(array_values($validated['certificates'])) : null,
                        'images' => isset($validated['images']) ? json_encode(array_values($validated['images'])) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    break;

                case 'nursing':
                    DB::table('nursing_profiles')->insert([
                        'user_id' => $user->id,
                        'home_sample_collection' => (bool) ($validated['home_sample_collection'] ?? false),
                        'service_radius_km' => $validated['service_radius_km'] ?? null,
                        'tax_id' => $validated['tax_id'] ?? null,
                        'medical_license' => $validated['medical_license'] ?? null,
                        'certificates' => isset($validated['certificates']) ? json_encode(array_values($validated['certificates'])) : null,
                        'images' => isset($validated['images']) ? json_encode(array_values($validated['images'])) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    break;

                case 'patient':
                    DB::table('patient_profiles')->insert([
                        'user_id' => $user->id,
                        'first_name' => $validated['first_name'],
                        'last_name' => $validated['last_name'],
                        'phone_number' => $validated['phone_number'],
                        'governorate' => $validated['governorate'] ?? null,
                        'area' => $validated['area'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    break;
            }

            return $user;
        });

        $token = $user->createToken('auth-token')->plainTextToken;

        $otp = $this->issueOtp($user->phone_number, 'register');

        $response = [
            'message' => 'Registration successful. OTP sent to phone.',
        ];

        if (app()->environment(['local', 'testing'])) {
            $response['otp'] = $otp;
        }

        return response()->json($response, 201);
    }

    private function issueOtp(string $phone, string $purpose): string
    {
        $otpData = $this->createOtpRecord($phone, $purpose);

        return $otpData['otp'];
    }

    private function createOtpRecord(string $phone, string $purpose): array
    {
        DB::table('auth_otp_codes')
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update([
                'consumed_at' => now(),
                'updated_at' => now(),
            ]);

        $otp = '11111';

        DB::table('auth_otp_codes')->insert([
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => Hash::make($otp),
            'code_expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['otp' => $otp];
    }

    private function findUserByPhone(string $phone): ?User
    {
        $user = User::query()
            ->where('phone_number', $phone)
            ->first();

        if ($user) {
            return $user;
        }

        if (Schema::hasColumn('users', 'mobile')) {
            return User::query()->where('mobile', $phone)->first();
        }

        return null;
    }

    private function resolveProfileData(string $role, string $userId): ?array
    {
        $profile = match ($role) {
            'doctor' => DB::table('doctor_profiles')->where('user_id', $userId)->first(),
            'hospital' => DB::table('hospital_profiles')->where('user_id', $userId)->first(),
            'laboratory' => DB::table('laboratory_profiles')->where('user_id', $userId)->first(),
            'radiology' => DB::table('radiology_profiles')->where('user_id', $userId)->first(),
            'nursing' => DB::table('nursing_profiles')->where('user_id', $userId)->first(),
            'patient' => DB::table('patient_profiles')->where('user_id', $userId)->first(),
            default => null,
        };

        if (! $profile) {
            return null;
        }

        $profile = (array) $profile;

        // Include user_profiles data for non-patient roles
        if ($role !== 'patient') {
            $userProfile = DB::table('user_profiles')->where('user_id', $userId)->first();
            if ($userProfile) {
                $profile = array_merge($profile, (array) $userProfile);
            }
        }

        if ($role === 'doctor') {
            $profile['clinics'] = $this->resolveDoctorClinics($userId);
        }

        // Decode JSON fields for hospital
        if ($role === 'hospital') {
            if (isset($profile['certificates']) && is_string($profile['certificates'])) {
                $profile['certificates'] = json_decode($profile['certificates'], true) ?? [];
            }
            if (isset($profile['images']) && is_string($profile['images'])) {
                $profile['images'] = json_decode($profile['images'], true) ?? [];
            }
        }

        if ($role === 'laboratory') {
            if (isset($profile['certificates']) && is_string($profile['certificates'])) {
                $profile['certificates'] = json_decode($profile['certificates'], true) ?? [];
            }
            if (isset($profile['images']) && is_string($profile['images'])) {
                $profile['images'] = json_decode($profile['images'], true) ?? [];
            }

            $profile['working_hours'] = $this->resolveLaboratoryWorkingHours($userId);
        }

        if ($role === 'radiology' || $role === 'nursing') {
            if (isset($profile['certificates']) && is_string($profile['certificates'])) {
                $profile['certificates'] = json_decode($profile['certificates'], true) ?? [];
            }
            if (isset($profile['images']) && is_string($profile['images'])) {
                $profile['images'] = json_decode($profile['images'], true) ?? [];
            }
        }

        return $profile;
    }

    private function resolveDoctorClinics(string $userId): array
    {
        return DB::table('clinics')
            ->where('doctor_id', $userId)
            ->orderBy('id')
            ->get()
            ->map(function ($clinic) use ($userId) {
                $clinicId = $clinic->id;

                $services = DB::table('clinic_services')
                    ->where('clinic_id', $clinicId)
                    ->orderBy('id')
                    ->get()
                    ->map(fn ($service) => (array) $service)
                    ->all();

                $workingHours = DB::table('working_hours')
                    ->where('user_id', $userId)
                    ->where('context_type', 'clinic:' . $clinicId)
                    ->orderBy('id')
                    ->get()
                    ->map(fn ($hour) => (array) $hour)
                    ->all();

                return [
                    'id' => $clinicId,
                    'clinic_type' => property_exists($clinic, 'clinic_type') ? $clinic->clinic_type : null,
                    'name' => $clinic->name,
                    'phone' => $clinic->phone,
                    'consultation_time' => property_exists($clinic, 'consultation_time') ? $clinic->consultation_time : null,
                    'governorate' => $clinic->governorate,
                    'city' => $clinic->city,
                    'full_address' => $clinic->address,
                    'photos' => $this->decodeJsonField(property_exists($clinic, 'photos') ? $clinic->photos : null),
                    'services' => $services,
                    'working_hours' => $workingHours,
                ];
            })
            ->all();
    }

    private function resolveLaboratoryWorkingHours(string $userId): array
    {
        return DB::table('working_hours')
            ->where('user_id', $userId)
            ->where('context_type', 'laboratory')
            ->orderBy('id')
            ->get()
            ->map(fn ($hour) => (array) $hour)
            ->all();
    }

    private function normalizeTime(?string $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function decodeJsonField(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

   public function profile(Request $request)
{
    $user = $request->user();

    if (! $user) {
        return response()->json([
            'message' => 'Unauthenticated',
        ], 401);
    }

    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'profile' => $this->resolveProfileData($user->role, $user->id),
        ],
    ]);
} 



}
