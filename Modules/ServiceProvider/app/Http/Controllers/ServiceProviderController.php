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
                "is_sponsered" => $profile ? (bool) $profile->is_ad : false,
                "service_provider_type" => $doctor->role,
                "availability_day" => null,
                "medical_service" => null,
                "doctor_type" => null
            ];
        });

        return response()->json($doctors);
    }

    public function availability(Request $request, $id)
    {
        $provider = User::find($id);

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'Service Provider not found.'
            ], 404);
        }

        // Mock availability
        $startDate = $request->query('start_date', now()->format('Y-m-d'));
        $endDate = $request->query('end_date', now()->addDays(7)->format('Y-m-d'));

        $data = [
            [
                'date' => $startDate,
                'day_name' => now()->parse($startDate)->format('l'),
                'is_available' => true,
                'slots' => [
                    ['time' => '16:00', 'formatted_time' => '04:00 PM', 'is_booked' => false],
                    ['time' => '16:30', 'formatted_time' => '04:30 PM', 'is_booked' => false],
                    ['time' => '17:00', 'formatted_time' => '05:00 PM', 'is_booked' => true],
                ]
            ],
            [
                'date' => now()->parse($startDate)->addDay()->format('Y-m-d'),
                'day_name' => now()->parse($startDate)->addDay()->format('l'),
                'is_available' => false,
                'slots' => []
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function services(Request $request, $id)
    {
        $provider = User::find($id);

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'Service Provider not found.'
            ], 404);
        }

        // Mock services
        return response()->json([
            'success' => true,
            'meta' => [
                'total_services' => 2,
                'current_page' => 1,
                'total_pages' => 1
            ],
            'data' => [
                [
                    'id' => 'srv_887',
                    'name' => 'Clinic Consultation',
                    'description' => 'In-person consultation check-up for pediatric general conditions.',
                    'price' => 360.00,
                    'original_price' => 450.00,
                    'discount_percentage' => 20,
                    'points' => 50,
                    'duration_minutes' => 30
                ],
                [
                    'id' => 'srv_888',
                    'name' => 'Home Visit Medical Examination',
                    'description' => 'Full checkup at the comfort of your home.',
                    'price' => 800.00,
                    'original_price' => 800.00,
                    'discount_percentage' => 0,
                    'points' => 100,
                    'duration_minutes' => 60
                ]
            ]
        ]);
    }

    public function reviews(Request $request, $id)
    {
        $provider = User::find($id);

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'Service Provider not found.'
            ], 404);
        }

        // Mock reviews
        return response()->json([
            'success' => true,
            'meta' => [
                'total_reviews' => 2,
                'average_rating' => 4.8,
                'current_page' => 1,
                'total_pages' => 1
            ],
            'data' => [
                [
                    'review_id' => 'rev_001',
                    'patient_name' => 'Ahmed Hassan',
                    'rating' => 5.0,
                    'comment' => 'Very polite and attentive doctor. Highly recommended!',
                    'created_at' => '2026-07-10T12:00:00Z'
                ],
                [
                    'review_id' => 'rev_002',
                    'patient_name' => 'Sarah Ibrahim',
                    'rating' => 4.5,
                    'comment' => 'Good listener but wait time at the clinic was a bit long.',
                    'created_at' => '2026-07-08T09:30:00Z'
                ]
            ]
        ]);
    }
}
