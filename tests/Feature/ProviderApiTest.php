<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MockDataSeeder;
use Database\Seeders\ProviderDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProviderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MockDataSeeder::class);
        $this->seed(ProviderDataSeeder::class);
    }

    public function test_doctor_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/v1/doctor/dashboard')->assertStatus(401)
            ->assertJsonPath('error', 'UNAUTHORIZED');
    }

    public function test_doctor_dashboard_returns_data_for_doctor(): void
    {
        $doctor = User::where('role', 'doctor')->first();
        Sanctum::actingAs($doctor);

        $this->getJson('/api/v1/doctor/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'todayAppointments',
                    'pendingRequests',
                    'completedAppointments',
                    'newRequests',
                    'rating',
                    'totalEarnings',
                ],
            ]);
    }

    public function test_patient_cannot_access_doctor_routes(): void
    {
        $patient = User::where('role', 'patient')->first();
        Sanctum::actingAs($patient);

        $this->getJson('/api/v1/doctor/dashboard')
            ->assertStatus(403)
            ->assertJsonPath('error', 'FORBIDDEN');
    }

    public function test_financial_summary_for_doctor(): void
    {
        Sanctum::actingAs(User::where('role', 'doctor')->first());

        $this->getJson('/api/v1/financial/summary')
            ->assertOk()
            ->assertJsonStructure(['data' => ['totalIncome', 'completedAppointments', 'commission', 'netIncome']]);
    }

    public function test_bookings_list_for_laboratory(): void
    {
        Sanctum::actingAs(User::where('role', 'laboratory')->first());

        $this->getJson('/api/v1/bookings?userType=laboratory')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['page', 'limit', 'total']]);
    }

    public function test_booking_status_update_validation(): void
    {
        Sanctum::actingAs(User::where('role', 'laboratory')->first());

        $this->patchJson('/api/v1/bookings/1/status', [])
            ->assertStatus(400)
            ->assertJsonPath('error', 'VALIDATION_ERROR');
    }

    public function test_hospital_dashboard(): void
    {
        Sanctum::actingAs(User::where('role', 'hospital')->first());

        $this->getJson('/api/v1/hospital/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['doctors', 'todaysIncome', 'appointments']]);
    }

    public function test_lab_dashboard(): void
    {
        Sanctum::actingAs(User::where('role', 'laboratory')->first());

        $this->getJson('/api/v1/lab/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['todayBookings', 'pendingRequests', 'homeVisits']]);
    }

    public function test_radiology_can_access_lab_routes(): void
    {
        Sanctum::actingAs(User::where('role', 'radiology')->first());

        $this->getJson('/api/v1/lab/profile')->assertOk();
    }

    public function test_nursing_dashboard(): void
    {
        Sanctum::actingAs(User::where('role', 'nursing')->first());

        $this->getJson('/api/v1/nursing/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['todaysBookings', 'activeNurses', 'totalEarnings']]);
    }

    public function test_doctor_appointments_list(): void
    {
        Sanctum::actingAs(User::where('role', 'doctor')->first());

        $this->getJson('/api/v1/doctor/appointments')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_doctor_settings_update(): void
    {
        Sanctum::actingAs(User::where('role', 'doctor')->first());

        $this->putJson('/api/v1/doctor/settings', ['marketingEmails' => true])
            ->assertOk()
            ->assertJsonPath('message', 'Settings updated successfully');
    }
}
