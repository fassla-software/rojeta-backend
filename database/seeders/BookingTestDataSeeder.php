<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\DoctorProfile;
use App\Models\Clinic;
use App\Models\Booking;

class BookingTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create a Patient
        $patient = User::firstOrCreate(
            ['email' => 'patient@test.com'],
            [
                'name' => 'Test Patient',
                'role' => 'patient',
                'phone_number' => '01000000001',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Create a Doctor (Service Provider)
        $doctor = User::firstOrCreate(
            ['email' => 'doctor@test.com'],
            [
                'name' => 'Dr. Test Doctor',
                'role' => 'doctor',
                'phone_number' => '01000000002',
                'password' => Hash::make('password'),
            ]
        );

        // 3. Create Doctor Profile
        DoctorProfile::firstOrCreate(
            ['user_id' => $doctor->id],
            [
                'specialty' => 'Pediatrics',
                'title' => 'Consultant',
                'rating' => 4.8,
                'reviews_count' => 120,
                'experience_years' => 10,
                'home_visit_price' => 500.00,
                'languages' => 'Arabic,English',
                'is_ad' => false,
            ]
        );

        // 4. Create Clinic
        Clinic::firstOrCreate(
            ['doctor_id' => $doctor->id],
            [
                'name' => 'Test Clinic',
                'city' => 'Cairo',
                'governorate' => 'Cairo',
                'address' => '90th St, New Cairo',
                'phone' => '01000000003',
            ]
        );

        // 5. Create Bookings
        
        // Pending booking
        Booking::firstOrCreate(
            [
                'provider_id' => $doctor->id,
                'patient_id' => $patient->id,
                'status' => 'pending',
            ],
            [
                'provider_type' => 'doctor',
                'type' => 'clinic',
                'patient_name' => $patient->name,
                'patient_phone' => $patient->phone_number,
                'service_id' => 'srv_887',
                'service_name' => 'Clinic Consultation',
                'date' => now()->addDays(2)->format('Y-m-d'),
                'time' => '16:30:00',
                'payment_status' => 'pending',
                'payment_type' => 'cash',
                'fee' => 360.00,
                'subtotal' => 450.00,
                'discount' => 90.00,
                'total_price' => 360.00,
                'points_earned' => 50,
            ]
        );

        // Completed booking
        Booking::firstOrCreate(
            [
                'provider_id' => $doctor->id,
                'patient_id' => $patient->id,
                'status' => 'completed',
            ],
            [
                'provider_type' => 'doctor',
                'type' => 'home_visit',
                'patient_name' => $patient->name,
                'patient_phone' => $patient->phone_number,
                'service_id' => 'srv_888',
                'service_name' => 'Home Visit Medical Examination',
                'date' => now()->subDays(5)->format('Y-m-d'),
                'time' => '14:00:00',
                'payment_status' => 'paid',
                'payment_type' => 'credit_card',
                'fee' => 800.00,
                'subtotal' => 800.00,
                'discount' => 0.00,
                'total_price' => 800.00,
                'points_earned' => 100,
                'address' => [
                    'street' => '90th Street',
                    'building' => '12A',
                    'floor' => 3,
                    'apartment' => 6,
                    'city' => 'Cairo',
                    'area' => 'New Cairo',
                ],
            ]
        );
        
        // Cancelled booking
        Booking::firstOrCreate(
            [
                'provider_id' => $doctor->id,
                'patient_id' => $patient->id,
                'status' => 'cancelled',
            ],
            [
                'provider_type' => 'doctor',
                'type' => 'video_call',
                'patient_name' => $patient->name,
                'patient_phone' => $patient->phone_number,
                'service_id' => 'srv_889',
                'service_name' => 'Online Consultation',
                'date' => now()->subDays(1)->format('Y-m-d'),
                'time' => '10:00:00',
                'payment_status' => 'refunded',
                'payment_type' => 'wallet',
                'fee' => 200.00,
                'subtotal' => 200.00,
                'discount' => 0.00,
                'total_price' => 200.00,
                'points_earned' => 0,
            ]
        );
    }
}
