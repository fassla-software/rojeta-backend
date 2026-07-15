<?php

namespace Modules\Doctor\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\DoctorVacation;
use App\Models\WorkingHour;
use App\Support\TimeFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorScheduleController extends Controller
{
    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public function show(Request $request)
    {
        $doctorId = $request->user()->id;
        $hours = WorkingHour::where('user_id', $doctorId)->get()->keyBy('day');
        $vacations = DoctorVacation::where('doctor_id', $doctorId)->get();

        $workingDays = collect(self::DAYS)->map(function ($day) use ($hours) {
            $record = $hours->get($day);
            $slots = $record?->time_slots ?? [];

            if (empty($slots) && $record && $record->from_time && $record->to_time) {
                $slots = [[
                    'start_time' => TimeFormatter::toDisplay($record->from_time),
                    'end_time' => TimeFormatter::toDisplay($record->to_time),
                ]];
            }

            return [
                'day_name' => $day,
                'is_active' => $record ? (bool) $record->is_active : false,
                'time_slots' => $slots,
            ];
        });

        return ApiResponse::data([
            'working_days' => $workingDays,
            'vacations' => $vacations->map(fn ($v) => [
                'id' => (string) $v->id,
                'start_date' => $v->start_date?->format('Y-m-d'),
                'end_date' => $v->end_date?->format('Y-m-d'),
            ]),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'working_days' => 'required|array',
            'working_days.*.day_name' => 'required|string',
            'working_days.*.is_active' => 'required|boolean',
            'working_days.*.time_slots' => 'nullable|array',
            'vacations' => 'nullable|array',
            'vacations.*.start_date' => 'required_with:vacations|date',
            'vacations.*.end_date' => 'required_with:vacations|date',
        ]);

        $doctorId = $request->user()->id;

        DB::transaction(function () use ($validated, $doctorId) {
            WorkingHour::where('user_id', $doctorId)->delete();
            DoctorVacation::where('doctor_id', $doctorId)->delete();

            foreach ($validated['working_days'] as $day) {
                WorkingHour::create([
                    'user_id' => $doctorId,
                    'day' => $day['day_name'],
                    'is_active' => $day['is_active'],
                    'is_closed' => !$day['is_active'],
                    'time_slots' => $day['time_slots'] ?? [],
                ]);
            }

            foreach ($validated['vacations'] ?? [] as $vacation) {
                DoctorVacation::create([
                    'doctor_id' => $doctorId,
                    'start_date' => $vacation['start_date'],
                    'end_date' => $vacation['end_date'],
                ]);
            }
        });

        return ApiResponse::message('Schedule updated successfully');
    }
}
