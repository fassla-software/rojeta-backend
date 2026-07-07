<?php

namespace Modules\Appointment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;

class AppointmentController extends Controller
{
    /**
     * Get user appointments
     */
    public function index(Request $request)
    {
        $appointments = Appointment::where('patient_id', $request->user()->id)
            ->with(['doctor.doctorProfile', 'clinic'])
            ->get()
            ->map(function($apt) {
                return [
                    "id" => $apt->id,
                    "doctor_name" => $apt->doctor ? $apt->doctor->name : 'Unknown',
                    "doctor_specialty" => ($apt->doctor && $apt->doctor->doctorProfile) ? $apt->doctor->doctorProfile->specialty : 'General',
                    "doctor_image_url" => ($apt->doctor && $apt->doctor->doctorProfile) ? $apt->doctor->doctorProfile->image_url : null,
                    "status" => $apt->status, 
                    "visit_type" => $apt->visit_type, 
                    "date" => $apt->date,
                    "time" => $apt->time,
                    "location_name" => $apt->clinic ? $apt->clinic->name : 'Home Visit',
                    "location_address" => $apt->clinic ? $apt->clinic->address : '',
                    "contact_phone" => $apt->clinic ? $apt->clinic->phone : '',
                    "consultation_fee" => (float) $apt->consultation_fee,
                    "payment_status" => $apt->payment_status, 
                    "important_notes" => $apt->important_notes,
                    "cancellation_reasons" => $apt->cancellation_reason ? [$apt->cancellation_reason] : []
                ];
            });

        return response()->json($appointments);
    }

    /**
     * Cancel appointment
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'reason' => 'required|string'
        ]);

        $appointment = Appointment::where('id', $request->id)
            ->where('patient_id', $request->user()->id)
            ->firstOrFail();

        $appointment->update([
            'status' => 'cancelled', 
            'cancellation_reason' => $request->reason
        ]);

        return response()->json([
            'id' => $appointment->id,
            'status' => 'cancelled',
            'reason' => $appointment->cancellation_reason
        ]);
    }

    /**
     * Reschedule appointment
     */
    public function reschedule(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i'
        ]);

        $appointment = Appointment::where('id', $request->id)
            ->where('patient_id', $request->user()->id)
            ->firstOrFail();

        $appointment->update([
            'date' => $request->date,
            'time' => $request->time,
            'status' => 'confirmed'
        ]);

        return response()->json([
            'id' => $appointment->id,
            'date' => $appointment->date,
            'time' => $appointment->time,
            'status' => $appointment->status
        ]);
    }

    /**
     * Book again
     */
    public function bookAgain(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|integer'
        ]);

        $oldApt = Appointment::where('id', $request->appointment_id)
            ->where('patient_id', $request->user()->id)
            ->firstOrFail();

        $newApt = Appointment::create([
            'patient_id' => $oldApt->patient_id,
            'doctor_id' => $oldApt->doctor_id,
            'clinic_id' => $oldApt->clinic_id,
            'date' => date('Y-m-d', strtotime('+1 day')),
            'time' => $oldApt->time,
            'status' => 'pending',
            'visit_type' => $oldApt->visit_type,
            'payment_status' => 'pending',
            'consultation_fee' => $oldApt->consultation_fee
        ]);

        return response()->json([
            'id' => $newApt->id,
            'original_appointment_id' => $oldApt->id,
            'status' => $newApt->status,
            'payment_status' => $newApt->payment_status
        ]);
    }
}
