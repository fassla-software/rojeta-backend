<?php

namespace Modules\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PatientProfile;
use App\Models\Document;

class ProfileController extends Controller
{
    /**
     * Get Profile
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        $profile = PatientProfile::firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            "first_name" => $profile->first_name ?? $user->name,
            "last_name" => $profile->last_name ?? '',
            "phone" => $profile->phone_number ?? $user->phone_number,
            "email" => $user->email,
            "date_of_birth" => $profile->date_of_birth,
            "gender" => $profile->gender,
            "address" => $profile->area . ', ' . $profile->governorate,
            "city" => $profile->governorate,
            "points" => $profile->points ?? 0,
            "image" => $profile->image
        ]);
    }

    /**
     * Update Profile
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        $user = $request->user();
        
        if (isset($validated['email'])) {
            $user->update(['email' => $validated['email']]);
        }

        $profile = PatientProfile::firstOrCreate(['user_id' => $user->id]);
        
        $profile->update([
            'first_name' => $validated['first_name'] ?? $profile->first_name,
            'last_name' => $validated['last_name'] ?? $profile->last_name,
            'phone_number' => $validated['phone'] ?? $profile->phone_number,
            'governorate' => $validated['city'] ?? $profile->governorate,
            'area' => $validated['address'] ?? $profile->area,
            'date_of_birth' => $validated['date_of_birth'] ?? $profile->date_of_birth,
            'gender' => $validated['gender'] ?? $profile->gender,
            'image' => $validated['image'] ?? $profile->image,
        ]);

        return response()->json([
            "first_name" => $profile->first_name,
            "last_name" => $profile->last_name,
            "phone" => $profile->phone_number,
            "email" => $user->email,
            "date_of_birth" => $profile->date_of_birth,
            "gender" => $profile->gender,
            "address" => $profile->area . ', ' . $profile->governorate,
            "city" => $profile->governorate,
            "points" => $profile->points ?? 0,
            "image" => $profile->image
        ]);
    }

    /**
     * Upload Medical Files
     */
    public function uploadMedicalFiles(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'file_type' => 'required|string'
        ]);

        $path = $request->file('file')->store('medical_files', 'public');

        Document::create([
            'user_id' => $request->user()->id,
            'type' => $request->file_type,
            'file_path' => $path
        ]);

        return response()->json([
            "success" => true,
            "message" => "File uploaded successfully",
            "file_url" => asset('storage/' . $path)
        ]);
    }

    /**
     * Help and Support
     */
    public function helpSupport(Request $request)
    {
        $request->validate([
            'subject' => 'required|string',
            'message' => 'required|string'
        ]);

        // Help & Support doesn't have a dedicated DB table yet
        return response()->json([
            "success" => true,
            "message" => "Support ticket created successfully",
            "ticket_id" => "TKT-" . time()
        ]);
    }
}
