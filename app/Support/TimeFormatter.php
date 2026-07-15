<?php

namespace App\Support;

use Carbon\Carbon;

class TimeFormatter
{
    public static function toDisplay(?string $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        return Carbon::parse($time)->format('g:i A');
    }

    public static function humanDiff(?\DateTimeInterface $dateTime): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        return Carbon::parse($dateTime)->diffForHumans();
    }

    public static function toDatabase(?string $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        return Carbon::parse($time)->format('H:i:s');
    }
}
