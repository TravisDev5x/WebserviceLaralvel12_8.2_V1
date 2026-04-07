<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Correo interno cuando el usuario no proporciona uno (dominio reservado @ecd.local).
 */
final class UserRegistrationEmail
{
    public static function resolve(?string $email, string $employeeNumber): string
    {
        $trimmed = $email !== null ? trim($email) : '';
        if ($trimmed !== '') {
            return Str::lower($trimmed);
        }

        return self::syntheticFromEmployeeNumber($employeeNumber);
    }

    public static function syntheticFromEmployeeNumber(string $employeeNumber): string
    {
        $emp = trim($employeeNumber);
        $local = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $emp);
        $local = strtolower(trim((string) $local, '-'));
        if ($local === '') {
            $local = 'u-'.substr(sha1($emp), 0, 10);
        }

        return $local.'@ecd.local';
    }

    public static function isSynthetic(?string $email): bool
    {
        if ($email === null || $email === '') {
            return false;
        }

        return str_ends_with(strtolower($email), '@ecd.local');
    }
}
