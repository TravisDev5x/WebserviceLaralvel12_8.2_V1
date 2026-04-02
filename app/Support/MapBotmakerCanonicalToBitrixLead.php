<?php

declare(strict_types=1);

namespace App\Support;

use App\Jobs\ProcessBotmakerPayload;

/**
 * Convierte datos “canónicos” (como los que llegan de Botmaker parseados) en campos de crm.lead.add/update.
 * Misma lógica que el mapeo por defecto de {@see ProcessBotmakerPayload::mapLeadData} sin mapeos dinámicos de BD.
 */
final class MapBotmakerCanonicalToBitrixLead
{
    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    public static function fromParsed(array $parsed): array
    {
        $firstName = trim((string) ($parsed['first_name'] ?? ''));
        $lastName = trim((string) ($parsed['last_name'] ?? ''));
        $middleLastName = trim((string) ($parsed['middle_last_name'] ?? ''));
        $fullName = trim($firstName.' '.$lastName.' '.$middleLastName);
        $phone = trim((string) ($parsed['phone'] ?? ''));
        $message = trim((string) ($parsed['message'] ?? ''));
        $event = (string) ($parsed['event'] ?? 'Lead desde Botmaker');
        $currency = (string) config_dynamic('botmaker.salary_currency', config('integrations.botmaker_to_bitrix.currency', 'MXN'));
        $bitrixFields = config_dynamic('botmaker.bitrix_fields', config('integrations.botmaker_to_bitrix.bitrix_fields', []));

        if (! is_array($bitrixFields)) {
            return [];
        }

        if (! array_key_exists('email', $bitrixFields)) {
            $bitrixFields['email'] = 'EMAIL';
        }

        $canonical = [
            'title' => $fullName !== '' ? "Lead Botmaker - {$fullName}" : $event,
            'comments' => $message,
            'phone' => $phone,
            'email' => trim((string) ($parsed['email'] ?? '')),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_last_name' => $middleLastName,
            'birth_date' => (string) ($parsed['birth_date'] ?? ''),
            'weeks_quoted' => (string) ($parsed['weeks_quoted'] ?? ''),
            'employment_status' => (string) ($parsed['employment_status'] ?? ''),
            'last_salary' => (string) ($parsed['last_salary'] ?? ''),
            'state' => (string) ($parsed['state'] ?? ''),
        ];

        $lead = [];

        foreach ($canonical as $sourceKey => $rawValue) {
            $targets = self::getTargetsFor($bitrixFields, $sourceKey);
            if ($targets === []) {
                continue;
            }

            $value = $rawValue;

            if (in_array($sourceKey, ['weeks_quoted', 'employment_status', 'state'], true)) {
                $value = self::mapEnumValue($sourceKey, (string) $rawValue);
            }

            if ($sourceKey === 'birth_date') {
                $value = self::normalizeDate((string) $rawValue);
            }

            if ($sourceKey === 'last_salary') {
                $value = self::normalizeMoney((string) $rawValue, $currency);
            }

            if ($sourceKey === 'phone') {
                $value = self::normalizePhoneField((string) $rawValue);
            }

            if ($sourceKey === 'email') {
                $value = self::normalizeEmailField((string) $rawValue);
            }

            if ($value === null || $value === '') {
                continue;
            }

            foreach ($targets as $targetKey) {
                $lead[$targetKey] = $value;
            }
        }

        return $lead;
    }

    private static function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date instanceof \DateTimeImmutable) {
                return $date->format('Y-m-d');
            }
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private static function normalizeMoney(string $value, string $currency): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $normalized = str_replace([',', '$', 'MXN', 'mxn', ' '], '', $value);
        $numeric = preg_replace('/[^\d.]/', '', $normalized) ?? '';
        if ($numeric === '') {
            return null;
        }

        return $numeric.'|'.strtoupper(trim($currency));
    }

    /**
     * @param  array<string, mixed>  $bitrixFields
     * @return array<int, string>
     */
    private static function getTargetsFor(array $bitrixFields, string $sourceKey): array
    {
        $value = $bitrixFields[$sourceKey] ?? null;
        if (is_string($value) && trim($value) !== '') {
            return [trim($value)];
        }

        if (is_array($value)) {
            return array_values(array_filter(
                array_map(static fn (mixed $item): string => is_string($item) ? trim($item) : '', $value),
                static fn (string $item): bool => $item !== '',
            ));
        }

        return [];
    }

    private static function mapEnumValue(string $sourceKey, string $value): ?string
    {
        $normalized = self::normalizeText($value);
        if ($normalized === '') {
            return null;
        }

        $enumMaps = config_dynamic('botmaker.enum_maps', config('integrations.botmaker_to_bitrix.enum_maps', []));
        $map = is_array($enumMaps) ? ($enumMaps[$sourceKey] ?? []) : [];
        if (! is_array($map)) {
            return null;
        }

        foreach ($map as $label => $id) {
            if (! is_string($label)) {
                continue;
            }

            if (self::normalizeText($label) === $normalized) {
                return is_string($id) || is_numeric($id) ? (string) $id : null;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{VALUE: string, VALUE_TYPE: string}>|null
     */
    private static function normalizePhoneField(string $value): ?array
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') {
            return null;
        }

        return [[
            'VALUE' => $digits,
            'VALUE_TYPE' => 'WORK',
        ]];
    }

    /**
     * @return array<int, array{VALUE: string, VALUE_TYPE: string}>|null
     */
    private static function normalizeEmailField(string $value): ?array
    {
        $email = strtolower(trim($value));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return [[
            'VALUE' => $email,
            'VALUE_TYPE' => 'WORK',
        ]];
    }

    private static function normalizeText(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $base = $ascii !== false ? $ascii : $value;
        $upper = strtoupper($base);

        return preg_replace('/\s+/', ' ', $upper) ?? $upper;
    }
}
