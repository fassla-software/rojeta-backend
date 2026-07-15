<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable,HasApiTokens;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'role',
         'phone_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function doctorProfile()
    {
        return $this->hasOne(DoctorProfile::class);
    }

    public function patientProfile()
    {
        return $this->hasOne(PatientProfile::class);
    }

    public function appointmentsAsPatient()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function appointmentsAsDoctor()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function clinics()
    {
        return $this->hasMany(Clinic::class, 'doctor_id');
    }

    public function hospitalProfile()
    {
        return $this->hasOne(HospitalProfile::class);
    }

    public function laboratoryProfile()
    {
        return $this->hasOne(LaboratoryProfile::class);
    }

    public function radiologyProfile()
    {
        return $this->hasOne(RadiologyProfile::class);
    }

    public function nursingProfile()
    {
        return $this->hasOne(NursingProfile::class);
    }

    public function providerSettings()
    {
        return $this->hasOne(ProviderSetting::class);
    }

    public function workingHours()
    {
        return $this->hasMany(WorkingHour::class);
    }

    public function vacations()
    {
        return $this->hasMany(DoctorVacation::class, 'doctor_id');
    }

    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class, 'provider_id');
    }

    public function bookingsAsProvider()
    {
        return $this->hasMany(Booking::class, 'provider_id');
    }

    public function workingHourOverrides()
    {
        return $this->hasMany(WorkingHourOverride::class);
    }
}
