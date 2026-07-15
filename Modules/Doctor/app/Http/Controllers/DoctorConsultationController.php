<?php

namespace Modules\Doctor\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\VisitRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DoctorConsultationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => 'required|integer|exists:appointments,id',
            'diagnosis' => 'nullable|string',
            'medication_details' => 'nullable|string',
            'laboratory_tests' => 'nullable|string',
            'radiology_tests' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
            'prescription_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $appointment = Appointment::where('doctor_id', $request->user()->id)
            ->where('id', $validated['appointment_id'])
            ->firstOrFail();

        $prescriptionPath = null;
        if ($request->hasFile('prescription_pdf')) {
            $prescriptionPath = $request->file('prescription_pdf')->store('prescriptions', 'public');
        }

        $consultation = DB::transaction(function () use ($validated, $appointment, $request, $prescriptionPath) {
            $consultation = Consultation::create([
                'appointment_id' => $appointment->id,
                'doctor_id' => $request->user()->id,
                'patient_id' => $appointment->patient_id,
                'diagnosis' => $validated['diagnosis'] ?? null,
                'medication_details' => $validated['medication_details'] ?? null,
                'laboratory_tests' => $validated['laboratory_tests'] ?? null,
                'radiology_tests' => $validated['radiology_tests'] ?? null,
                'follow_up_date' => $validated['follow_up_date'] ?? null,
                'prescription_path' => $prescriptionPath,
            ]);

            VisitRecord::create([
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $request->user()->id,
                'appointment_id' => $appointment->id,
                'date' => now()->toDateString(),
                'type' => ucwords(str_replace('_', ' ', $appointment->visit_type)),
                'diagnosis' => $validated['diagnosis'] ?? null,
                'prescription' => $validated['medication_details'] ?? null,
                'notes' => null,
                'follow_up_date' => $validated['follow_up_date'] ?? null,
            ]);

            $appointment->update(['status' => 'completed']);

            return $consultation;
        });

        return ApiResponse::message('Consultation submitted successfully', [
            'consultationId' => 'c' . $consultation->id,
        ]);
    }
}
