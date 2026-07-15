<?php

namespace App\Support;

use App\Models\WorkingHour;
use App\Models\WorkingHourOverride;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkingHoursFormatter
{
    public static function toLabResponse(string $userId): array
    {
        $hours = WorkingHour::where('user_id', $userId)->get();
        $overrides = WorkingHourOverride::where('user_id', $userId)
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->get();

        return [
            'standardWeek' => $hours->map(fn ($h) => [
                'dayName' => $h->day,
                'isClosed' => (bool) $h->is_closed,
                'openingTime' => TimeFormatter::toDisplay($h->from_time),
                'closingTime' => TimeFormatter::toDisplay($h->to_time),
            ])->values(),
            'upcomingOverrides' => $overrides->map(fn ($o) => [
                'id' => 'so' . $o->id,
                'dateStr' => $o->date?->format('Y-m-d'),
                'eventName' => $o->event_name,
                'isClosed' => (bool) $o->is_closed,
                'openingTime' => $o->opening_time,
                'closingTime' => $o->closing_time,
            ])->values(),
        ];
    }

    public static function syncLabHours(string $userId, array $standardWeek, ?array $upcomingOverrides = null): void
    {
        WorkingHour::where('user_id', $userId)->delete();

        foreach ($standardWeek as $day) {
            WorkingHour::create([
                'user_id' => $userId,
                'day' => $day['dayName'],
                'is_closed' => $day['isClosed'] ?? false,
                'from_time' => TimeFormatter::toDatabase($day['openingTime'] ?? null),
                'to_time' => TimeFormatter::toDatabase($day['closingTime'] ?? null),
            ]);
        }

        if ($upcomingOverrides !== null) {
            WorkingHourOverride::where('user_id', $userId)->delete();
            foreach ($upcomingOverrides as $override) {
                WorkingHourOverride::create([
                    'user_id' => $userId,
                    'date' => $override['dateStr'],
                    'event_name' => $override['eventName'],
                    'is_closed' => $override['isClosed'] ?? false,
                    'opening_time' => TimeFormatter::toDatabase($override['openingTime'] ?? null),
                    'closing_time' => TimeFormatter::toDatabase($override['closingTime'] ?? null),
                ]);
            }
        }
    }

    public static function parseClinicWorkingHours(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : null;
        }

        return is_array($value) ? $value : null;
    }
}
