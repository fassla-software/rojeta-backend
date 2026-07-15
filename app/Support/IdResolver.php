<?php

namespace App\Support;

class IdResolver
{
    public static function stripPrefix(string $id, string $prefix): string
    {
        if (str_starts_with($id, $prefix)) {
            return substr($id, strlen($prefix));
        }

        return $id;
    }

    public static function patientId(string $patientId): string
    {
        return self::stripPrefix($patientId, 'p');
    }
}
