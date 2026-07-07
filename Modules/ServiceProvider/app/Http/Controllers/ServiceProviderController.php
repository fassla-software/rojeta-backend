<?php

namespace Modules\ServiceProvider\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class ServiceProviderController extends Controller
{
    /**
     * Search Doctors / Service Providers
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'doctor')->with(['doctorProfile', 'clinics']);

        // Filter by keyword
        if ($request->filled('query')) {
            $keyword = $request->query('query');
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhereHas('doctorProfile', function($q) use ($keyword) {
                      $q->where('specialty', 'like', "%{$keyword}%");
                  });
            });
        }

        if ($request->filled('specialty')) {
            $specialty = $request->query('specialty');
            $query->whereHas('doctorProfile', function($q) use ($specialty) {
                $q->where('specialty', $specialty);
            });
        }

        $doctors = $query->get()->map(function($doctor) {
            $profile = $doctor->doctorProfile;
            $clinics = $doctor->clinics;
            return [
                "id" => $doctor->id,
                "name" => $doctor->name,
                "title" => $profile->title ?? null,
                "specialty" => $profile->specialty ?? null,
                "rating" => $profile ? (float) $profile->rating : 0,
                "reviewsCount" => $profile ? $profile->reviews_count : 0,
                "experienceYears" => $profile ? $profile->experience_years : 0,
                "service_areas" => $clinics->pluck('city')->filter()->values()->toArray(),
                "clinic_visits" => "0",
                "languages" => $profile && $profile->languages ? explode(',', $profile->languages) : [],
                "availability_time" => null,
                "price" => $profile ? (float) $profile->home_visit_price : 0,
                "original_price" => $profile ? (float) $profile->home_visit_price : 0,
                "discount_percentage" => 0,
                "points" => 0,
                "image_url" => $profile->image_url ?? null,
                "is_ad" => $profile ? (bool) $profile->is_ad : false,
                "availability_day" => null,
                "medical_service" => null,
                "doctor_type" => null
            ];
        });

        return response()->json($doctors);
    }
}
