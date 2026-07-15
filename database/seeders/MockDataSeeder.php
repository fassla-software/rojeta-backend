<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\DoctorProfile;
use App\Models\PatientProfile;
use App\Models\Appointment;
use App\Models\Notification;
use App\Models\Clinic;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class MockDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Patient Users
        $patient1 = User::firstOrCreate(
            ['email' => 'patient@example.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Ali Abdelaziz',
                'phone_number' => '01111111111',
                'password' => Hash::make('password123'),
                'role' => 'patient',
            ]
        );

        PatientProfile::firstOrCreate(
            ['user_id' => $patient1->id],
            [
                'first_name' => 'Ali',
                'last_name' => 'Abdelaziz',
                'phone_number' => '01111111111',
                'governorate' => 'Cairo',
                'area' => 'Nasr City',
                'date_of_birth' => '1995-10-15',
                'gender' => 'Male',
                'points' => 2800,
                'image' => 'https://i.pravatar.cc/150?u=patient1',
                'allergies' => ['Penicillin', 'Aspirin'],
                'chronic_conditions' => ['Hypertension', 'Diabetes Type 2'],
            ]
        );

        $patient2 = User::firstOrCreate(
            ['email' => 'sara@example.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Sara Mahmoud',
                'phone_number' => '01000000000',
                'password' => Hash::make('password123'),
                'role' => 'patient',
            ]
        );

        PatientProfile::firstOrCreate(
            ['user_id' => $patient2->id],
            [
                'first_name' => 'Sara',
                'last_name' => 'Mahmoud',
                'phone_number' => '01000000000',
                'governorate' => 'Giza',
                'area' => 'Dokki',
                'date_of_birth' => '1998-05-20',
                'gender' => 'Female',
                'points' => 1500,
                'image' => 'https://i.pravatar.cc/150?u=sara',
            ]
        );

        // 2. Create Doctor Users
        // Doctor 1
        $doctor1 = User::firstOrCreate(
            ['email' => 'doctor@example.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Dr. Ahmed Hassan',
                'phone_number' => '01222222222',
                'password' => Hash::make('password123'),
                'role' => 'doctor',
            ]
        );

        DoctorProfile::firstOrCreate(
            ['user_id' => $doctor1->id],
            [
                'specialty' => 'Cardiology',
                'title' => 'Consultant',
                'experience_years' => 15,
                'home_visit_price' => 300,
                'rating' => 4.80,
                'reviews_count' => 267,
                'languages' => 'Arabic,English',
                'image_url' => 'https://i.pravatar.cc/150?u=doc1',
                'is_ad' => true,
            ]
        );

        $clinic1 = Clinic::firstOrCreate(
            ['doctor_id' => $doctor1->id, 'name' => 'Heart Care Clinic'],
            [
                'city' => 'Cairo',
                'address' => 'Nasr City, Abbas El Akkad',
                'phone' => '022222222',
            ]
        );

        $clinic1->update([
            'working_hours' => [
                'Monday' => ['start' => '09:00 AM', 'end' => '05:00 PM'],
                'Wednesday' => ['start' => '09:00 AM', 'end' => '05:00 PM'],
            ],
        ]);

        // Doctor 2
        $doctor2 = User::firstOrCreate(
            ['email' => 'doc.mona@example.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Dr. Mona Ibrahim',
                'phone_number' => '01011111111',
                'password' => Hash::make('password123'),
                'role' => 'doctor',
            ]
        );

        DoctorProfile::firstOrCreate(
            ['user_id' => $doctor2->id],
            [
                'specialty' => 'Dermatology',
                'title' => 'Specialist',
                'experience_years' => 8,
                'home_visit_price' => 200,
                'rating' => 4.50,
                'reviews_count' => 120,
                'languages' => 'Arabic,French',
                'image_url' => 'https://i.pravatar.cc/150?u=doc2',
                'is_ad' => false,
            ]
        );

        $clinic2 = Clinic::firstOrCreate(
            ['doctor_id' => $doctor2->id, 'name' => 'Skin Glow Clinic'],
            [
                'city' => 'Giza',
                'address' => 'Mohandeseen, Batal Ahmed Abdelaziz',
                'phone' => '023333333',
            ]
        );

        // Doctor 3
        $doctor3 = User::firstOrCreate(
            ['email' => 'doc.youssef@example.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Dr. Youssef Ali',
                'phone_number' => '01555555555',
                'password' => Hash::make('password123'),
                'role' => 'doctor',
            ]
        );

        DoctorProfile::firstOrCreate(
            ['user_id' => $doctor3->id],
            [
                'specialty' => 'Pediatrics',
                'title' => 'Consultant',
                'experience_years' => 20,
                'home_visit_price' => 400,
                'rating' => 4.95,
                'reviews_count' => 500,
                'languages' => 'Arabic,English',
                'image_url' => 'https://i.pravatar.cc/150?u=doc3',
                'is_ad' => true,
            ]
        );

        $clinic3 = Clinic::firstOrCreate(
            ['doctor_id' => $doctor3->id, 'name' => 'Happy Kids Clinic'],
            [
                'city' => 'Alexandria',
                'address' => 'Smouha',
                'phone' => '03444444',
            ]
        );

        // 3. Create Appointments
        Appointment::firstOrCreate(
            ['patient_id' => $patient1->id, 'doctor_id' => $doctor1->id, 'clinic_id' => $clinic1->id, 'date' => date('Y-m-d', strtotime('+1 day'))],
            [
                'time' => '10:00:00',
                'status' => 'confirmed',
                'visit_type' => 'clinic_visit',
                'consultation_fee' => 250,
                'important_notes' => 'Please bring previous medical records',
            ]
        );

        Appointment::firstOrCreate(
            ['patient_id' => $patient1->id, 'doctor_id' => $doctor2->id, 'clinic_id' => null, 'date' => date('Y-m-d', strtotime('+2 days'))],
            [
                'time' => '15:30:00',
                'status' => 'pending',
                'visit_type' => 'home_visit',
                'consultation_fee' => 200,
                'important_notes' => 'Home address is Nasr city...',
            ]
        );

        Appointment::firstOrCreate(
            ['patient_id' => $patient2->id, 'doctor_id' => $doctor3->id, 'clinic_id' => $clinic3->id, 'date' => date('Y-m-d', strtotime('+5 days'))],
            [
                'time' => '18:00:00',
                'status' => 'confirmed',
                'visit_type' => 'clinic_visit',
                'consultation_fee' => 300,
                'important_notes' => 'Vaccination follow up',
            ]
        );

        // 4. Create Notifications
        Notification::firstOrCreate(
            ['user_id' => $patient1->id, 'title' => 'Special Offer - 20% Off'],
            [
                'body' => 'Get 20% discount on all cardiology consultations this week!',
                'type' => 'offer',
                'is_read' => false,
                'is_pinned' => true,
            ]
        );

        Notification::firstOrCreate(
            ['user_id' => $patient1->id, 'title' => 'Appointment Confirmed'],
            [
                'body' => 'Your appointment with Dr. Ahmed Hassan is confirmed.',
                'type' => 'info',
                'is_read' => true,
                'is_pinned' => false,
            ]
        );

        Notification::firstOrCreate(
            ['user_id' => $patient2->id, 'title' => 'Welcome to Rojeta!'],
            [
                'body' => 'Book your first appointment now and get 500 bonus points.',
                'type' => 'offer',
                'is_read' => false,
                'is_pinned' => true,
            ]
        );
    }
}
