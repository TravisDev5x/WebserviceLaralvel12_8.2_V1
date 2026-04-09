<?php

declare(strict_types=1);

namespace App\Legacy;

/**
 * LEGACY v1: replaced by imconnector architecture.
 * Kept for backward compatibility with IntegrationProbeService test leads.
 *
 * Aplica valores por defecto de Bitrix24 al payload de crm.lead.add/update.
 */
class BitrixLeadDefaults
{
    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public static function merge(array $fields): array
    {
        $sourceId = trim((string) config_dynamic('bitrix24.default_source_id', ''));
        if ($sourceId !== '' && ! array_key_exists('SOURCE_ID', $fields)) {
            $fields['SOURCE_ID'] = $sourceId;
        }

        $assigned = trim((string) config_dynamic('bitrix24.default_assigned_by_id', ''));
        if ($assigned !== '' && ! array_key_exists('ASSIGNED_BY_ID', $fields)) {
            $fields['ASSIGNED_BY_ID'] = $assigned;
        }

        $status = trim((string) config_dynamic('bitrix24.default_status_id', ''));
        if ($status !== '' && ! array_key_exists('STATUS_ID', $fields)) {
            $fields['STATUS_ID'] = $status;
        }

        return $fields;
    }
}
