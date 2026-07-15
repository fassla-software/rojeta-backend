<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Booking;
use App\Models\FinancialTransaction;
use App\Models\HospitalLabTest;
use App\Models\HospitalProfile;
use App\Models\HospitalRadiologyService;
use App\Models\HospitalService;
use App\Models\HospitalSpecialty;
use App\Models\HospitalStaff;
use App\Models\IcuRoom;
use App\Models\Incubator;
use App\Models\LabBranch;
use App\Models\LabHomeVisitConfig;
use App\Models\LabResult;
use App\Models\LabService;
use App\Models\LabServiceArea;
use App\Models\LabTimeSlot;
use App\Models\LaboratoryProfile;
use App\Models\MarketingPackage;
use App\Models\NursingProfile;
use App\Models\NursingService;
use App\Models\NursingStaff;
use App\Models\Notification;
use App\Models\ProviderSetting;
use App\Models\RadiologyProfile;
use App\Models\VisitRecord;
use App\Models\WorkingHour;
use App\Models\WorkingHourOverride;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProviderDataSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = User::firstOrCreate(
            ['email' => 'hospital@example.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'City General Hospital',
                'phone_number' => '0220000000',
                'password' => Hash::make('password123'),
                'role' => 'hospital',
            ]
        );

        HospitalProfile::firstOrCreate(
            ['user_id' => $hospital->id],
            [
                'hospital_name' => 'City General Hospital',
                'email' => 'hospital@example.com',
                'phone_number' => '0220000000',
                'description' => 'Full-service hospital',
                'settings' => [
                    'pushNotificationsEnabled' => true,
                    'languageCode' => 'en',
                    'newBookingsEnabled' => true,
                    'appointmentRemindersEnabled' => true,
                    'emergencyRequestsEnabled' => true,
                    'paymentNotificationsEnabled' => false,
                ],
            ]
        );

        $lab = User::firstOrCreate(
            ['email' => 'lab@example.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'City Lab',
                'phone_number' => '0221111111',
                'password' => Hash::make('password123'),
                'role' => 'laboratory',
            ]
        );

        LaboratoryProfile::firstOrCreate(
            ['user_id' => $lab->id],
            [
                'lab_name' => 'City Lab',
                'license_number' => 'LAB-2023-994821',
                'primary_contact_email' => 'info@citylab.com',
                'phone_number' => '01234567890',
                'description' => 'Full-service diagnostic laboratory',
                'is_verified' => true,
                'verification_year' => '2023',
                'icon_url' => 'https://cdn.rojeta.com/labs/citylab.png',
                'home_sample_collection' => true,
                'collection_fee' => 50,
                'service_radius_km' => 15,
            ]
        )->update([
            'special_offer_title' => 'Summer Health Checkup',
            'special_offer_description' => '20% off on all blood tests this month',
            'special_offer_discount' => '20%',
        ]);

        $radiology = User::firstOrCreate(
            ['email' => 'radiology@example.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'City Radiology Center',
                'phone_number' => '0222222222',
                'password' => Hash::make('password123'),
                'role' => 'radiology',
            ]
        );

        RadiologyProfile::firstOrCreate(
            ['user_id' => $radiology->id],
            [
                'lab_name' => 'City Radiology Center',
                'license_number' => 'RAD-2023-123456',
                'primary_contact_email' => 'info@cityrad.com',
                'phone_number' => '01234567891',
                'description' => 'Advanced radiology services',
                'is_verified' => true,
                'verification_year' => '2023',
                'icon_url' => 'https://cdn.rojeta.com/rad/cityrad.png',
            ]
        )->update([
            'special_offer_title' => 'MRI Package Deal',
            'special_offer_description' => '15% off on MRI scans',
            'special_offer_discount' => '15%',
        ]);

        $nursing = User::firstOrCreate(
            ['email' => 'nursing@example.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'City Nursing Office',
                'phone_number' => '0223333333',
                'password' => Hash::make('password123'),
                'role' => 'nursing',
            ]
        );

        NursingProfile::firstOrCreate(
            ['user_id' => $nursing->id],
            [
                'office_name' => 'City Nursing Office',
                'license_number' => 'NRS-2024-001234',
                'email' => 'info@citynursing.com',
                'phone_number' => '01234567890',
                'description' => 'Professional home nursing services',
                'verification_status' => 'verified',
                'avatar_url' => 'https://cdn.rojeta.com/nursing/cityoffice.png',
            ]
        );

        $patient = User::where('role', 'patient')->first();
        $doctor = User::where('email', 'doctor@example.com')->first();

        if ($patient && $doctor) {
            Appointment::firstOrCreate(
                [
                    'hospital_id' => $hospital->id,
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'date' => now()->addDay()->toDateString(),
                ],
                [
                    'time' => '11:00:00',
                    'status' => 'confirmed',
                    'visit_type' => 'clinic_visit',
                    'consultation_fee' => 300,
                    'is_online' => false,
                ]
            );

            VisitRecord::firstOrCreate(
                ['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'date' => '2026-01-15'],
                [
                    'type' => 'Clinic Visit',
                    'diagnosis' => 'Hypertension',
                    'prescription' => 'Amlodipine 5mg',
                    'notes' => 'Blood pressure controlled',
                    'follow_up_date' => '2026-02-15',
                ]
            );

            LabResult::firstOrCreate(
                ['patient_id' => $patient->id, 'date' => '2026-01-10', 'tests_performed' => 'CBC, Lipid Profile'],
                [
                    'results' => 'Cholesterol: 220 mg/dL',
                    'notes' => 'Slightly elevated cholesterol',
                    'is_normal' => false,
                ]
            );
        }

        foreach ([$lab, $radiology, $nursing] as $provider) {
            $type = match ($provider->role) {
                'laboratory' => 'laboratory',
                'radiology' => 'radiologyCenter',
                'nursing' => 'nursingOffice',
                default => $provider->role,
            };

            if ($patient) {
                Booking::firstOrCreate(
                    [
                        'provider_id' => $provider->id,
                        'patient_id' => $patient->id,
                        'date' => now()->addDay()->toDateString(),
                        'service_name' => 'PCR',
                    ],
                    [
                        'provider_type' => $type,
                        'type' => 'branchVisit',
                        'patient_name' => $patient->name,
                        'patient_phone' => $patient->phone_number,
                        'location_name' => 'New cairo',
                        'time' => '10:00:00',
                        'status' => 'pending',
                        'payment_status' => 'paid',
                        'fee' => 300,
                    ]
                );

                if ($provider->role === 'nursing') {
                    Booking::firstOrCreate(
                        [
                            'provider_id' => $provider->id,
                            'patient_id' => $patient->id,
                            'date' => now()->toDateString(),
                            'service_name' => 'Home Nursing Care',
                        ],
                        [
                            'provider_type' => $type,
                            'type' => 'homeVisit',
                            'patient_name' => $patient->name,
                            'patient_phone' => $patient->phone_number,
                            'location_name' => 'Nasr City',
                            'time' => '09:00:00',
                            'status' => 'completed',
                            'payment_status' => 'paid',
                            'fee' => 250,
                        ]
                    );

                    Booking::firstOrCreate(
                        [
                            'provider_id' => $provider->id,
                            'patient_id' => $patient->id,
                            'date' => now()->subDay()->toDateString(),
                            'service_name' => 'Wound Care',
                        ],
                        [
                            'provider_type' => $type,
                            'type' => 'homeVisit',
                            'patient_name' => $patient->name,
                            'patient_phone' => $patient->phone_number,
                            'location_name' => 'Heliopolis',
                            'time' => '14:00:00',
                            'status' => 'completed',
                            'payment_status' => 'paid',
                            'fee' => 200,
                        ]
                    );
                }
            }

            FinancialTransaction::firstOrCreate(
                [
                    'provider_id' => $provider->id,
                    'patient_name' => 'Marwan Ali',
                    'transaction_date' => '2024-05-24',
                ],
                [
                    'provider_role' => $provider->role,
                    'visit_type' => 'Clinic Visit',
                    'status' => 'completed',
                    'transaction_time' => '10:30:00',
                    'service_fee' => 500,
                    'platform_commission' => 50,
                    'earning' => 450,
                ]
            );
        }

        if ($doctor && $patient) {
            FinancialTransaction::firstOrCreate(
                [
                    'provider_id' => $doctor->id,
                    'patient_name' => $patient->name,
                    'transaction_date' => now()->toDateString(),
                ],
                [
                    'provider_role' => 'doctor',
                    'visit_type' => 'Clinic Visit',
                    'status' => 'completed',
                    'transaction_time' => '10:00:00',
                    'service_fee' => 300,
                    'platform_commission' => 30,
                    'earning' => 270,
                ]
            );

            Notification::firstOrCreate(
                ['user_id' => $doctor->id, 'title' => 'New Appointment Request'],
                [
                    'body' => 'Mohamed Ali requested an appointment for tomorrow at 10:00 AM',
                    'type' => 'appointment_request',
                    'priority' => 'High',
                    'is_read' => false,
                ]
            );

            ProviderSetting::firstOrCreate(
                ['user_id' => $doctor->id],
                [
                    'role' => 'doctor',
                    'settings' => [
                        'emailNotifications' => true,
                        'smsNotifications' => true,
                        'pushNotifications' => true,
                        'appointmentReminders' => true,
                        'marketingEmails' => false,
                    ],
                ]
            );
        }

        MarketingPackage::firstOrCreate(
            ['id' => 'professional'],
            [
                'name_key' => 'professional',
                'posts_per_month' => '12',
                'monthly_price' => 5000,
                'yearly_price' => 50000,
                'is_most_popular' => true,
                'platforms' => ['Facebook', 'Instagram', 'Twitter'],
                'features' => ['designQuality', 'analytics', 'priority', 'support'],
            ]
        );

        MarketingPackage::firstOrCreate(
            ['id' => 'basic'],
            [
                'name_key' => 'basic',
                'posts_per_month' => '4',
                'monthly_price' => 2000,
                'yearly_price' => 20000,
                'is_most_popular' => false,
                'platforms' => ['Facebook'],
                'features' => ['designQuality'],
            ]
        );

        HospitalService::firstOrCreate(
            ['hospital_id' => $hospital->id, 'title_key' => 'serviceLaboratory'],
            [
                'description_key' => 'serviceLaboratoryDesc',
                'is_enabled' => true,
                'icon_path' => 'assets/icons/laboratory.png',
            ]
        );

        $specialty = HospitalSpecialty::firstOrCreate(
            ['hospital_id' => $hospital->id, 'name' => 'Orthopedic Clinic'],
            ['description' => 'Bones and joints', 'has_emergency' => false]
        );

        HospitalStaff::firstOrCreate(
            ['specialty_id' => $specialty->id, 'name' => 'Dr. Jane Smith'],
            [
                'role' => 'Orthopedic Surgeon',
                'price' => '150EGP',
                'availability' => 'Mon-Fri',
                'image_path' => 'https://cdn.rojeta.com/staff/jane.png',
            ]
        );

        IcuRoom::firstOrCreate(
            ['hospital_id' => $hospital->id, 'room_number' => 'ICU-101'],
            [
                'bed_id' => 'BED-A',
                'floor_wing' => '2nd Floor - West',
                'equipment_list' => ['Ventilator', 'Heart Monitor', 'IV Pump'],
                'status' => 'available',
            ]
        );

        Incubator::firstOrCreate(
            ['hospital_id' => $hospital->id, 'unit_id' => 'NICU-001'],
            [
                'model' => 'Dräger Isolette',
                'wing_section' => 'NICU - East Wing',
                'monitoring_type' => ['Heart Rate', 'SpO2', 'Temperature'],
                'status' => 'ready',
                'temperature' => 36.5,
                'humidity' => 65,
            ]
        );

        HospitalLabTest::firstOrCreate(
            ['hospital_id' => $hospital->id, 'name' => 'CBC (Complete Blood Count)'],
            [
                'price' => 150,
                'category' => 'Hematology',
                'home_collection_available' => true,
                'preparation_instructions' => 'Fasting for 8 hours',
                'estimated_result_time' => '24 hours',
            ]
        );

        HospitalRadiologyService::firstOrCreate(
            ['hospital_id' => $hospital->id, 'name' => 'MRI Brain'],
            [
                'price' => 2500,
                'category' => 'MRI',
                'contrast_agent_required' => true,
                'preparation_instructions' => 'Remove all metallic items',
                'estimated_duration' => '45 minutes',
            ]
        );

        foreach ([$lab, $radiology] as $provider) {
            LabService::firstOrCreate(
                ['provider_id' => $provider->id, 'name' => 'CBC (Complete Blood Count)'],
                [
                    'provider_role' => $provider->role,
                    'price' => 120,
                    'is_home_collection_available' => true,
                    'turnaround_time' => '24 hours',
                    'category' => 'Hematology',
                ]
            );

            LabBranch::firstOrCreate(
                ['provider_id' => $provider->id, 'name' => 'New Cairo Branch'],
                [
                    'provider_role' => $provider->role,
                    'address' => '5th Settlement, Street 90',
                    'phone' => '01234567890',
                    'working_hours' => 'Sat-Thu: 8 AM - 10 PM',
                    'is_home_collection_available' => true,
                    'image_url' => 'https://cdn.rojeta.com/branches/newcairo.jpg',
                ]
            );

            $config = LabHomeVisitConfig::firstOrCreate(
                ['provider_id' => $provider->id],
                [
                    'provider_role' => $provider->role,
                    'is_service_visible' => true,
                    'collection_fee' => 50,
                    'minimum_booking_amount' => 200,
                ]
            );

            LabServiceArea::firstOrCreate(
                ['config_id' => $config->id, 'name' => 'New Cairo'],
                ['radius_km' => 15, 'techs_available' => 4]
            );

            LabTimeSlot::firstOrCreate(
                ['config_id' => $config->id, 'slot_type' => 'weekday', 'time' => '08:00'],
                ['am_pm' => 'AM', 'status' => 'active']
            );

            foreach (['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'] as $day) {
                WorkingHour::firstOrCreate(
                    ['user_id' => $provider->id, 'day' => $day],
                    [
                        'is_closed' => false,
                        'from_time' => '08:00:00',
                        'to_time' => '22:00:00',
                    ]
                );
            }

            WorkingHour::firstOrCreate(
                ['user_id' => $provider->id, 'day' => 'Friday'],
                ['is_closed' => true, 'from_time' => null, 'to_time' => null]
            );

            WorkingHourOverride::firstOrCreate(
                ['user_id' => $provider->id, 'date' => now()->addDays(10)->toDateString()],
                [
                    'event_name' => 'Eid Holiday',
                    'is_closed' => true,
                    'opening_time' => null,
                    'closing_time' => null,
                ]
            );
        }

        NursingService::firstOrCreate(
            ['nursing_office_id' => $nursing->id, 'title' => 'Home Nursing Care'],
            [
                'subtitle' => 'Professional in-home nursing assistance',
                'duration_tag' => '12 Hours',
                'bullet_points' => ['Medication management', 'Vital signs monitoring', 'Wound care'],
            ]
        );

        NursingStaff::firstOrCreate(
            ['nursing_office_id' => $nursing->id, 'name' => 'Nurse Fatma'],
            [
                'image_url' => 'https://cdn.rojeta.com/staff/fatma.png',
                'gender' => 'Female',
                'status' => 'available',
                'skills' => ['Wound Care', 'IV Therapy', 'Vital Signs'],
                'years_of_experience' => 5,
            ]
        );
    }
}
