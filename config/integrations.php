<?php

declare(strict_types=1);

$decodeJson = static function (string $value, array $fallback): array {
    if ($value === '') {
        return $fallback;
    }

    $decoded = json_decode($value, true);

    return is_array($decoded) ? $decoded : $fallback;
};

$defaultSourceAliases = [
    'first_name' => ['firstName', 'first_name', 'nombre', 'NOMBRE', 'name'],
    'last_name' => ['lastName', 'last_name', 'apellidoPaterno', 'apellido_paterno', 'APELLIDO PATERNO', 'APELLIDO_PATERNO'],
    'middle_last_name' => ['motherLastName', 'apellidoMaterno', 'apellido_materno', 'APELLIDO MATERNO', 'APELLIDO_MATERNO'],
    'birth_date' => ['birthDate', 'fechaNacimiento', 'fecha_nacimiento', 'FECHA NACIMIENTO', 'FECHA_NACIMIENTO'],
    'weeks_quoted' => ['weeksQuoted', 'semanasCotizadas', 'semanas_cotizadas', 'SEMANAS COTIZADAS', 'SEMANAS_COTIZADAS'],
    'employment_status' => ['employmentStatus', 'estatusLaboral', 'estatus_laboral', 'ESTATUS LABORAL', 'ESTATUS_LABORAL'],
    'last_salary' => ['lastSalary', 'ultimoSalario', 'ultimo_salario', 'ULTIMO SALARIO', 'ULTIMO_SALARIO'],
    'state' => ['state', 'estado', 'ESTADO'],
];

$defaultBitrixFields = [
    'title' => 'TITLE',
    'comments' => 'COMMENTS',
    'phone' => 'PHONE',
    'first_name' => ['NAME', 'UF_CRM_1774547362498'],
    'last_name' => ['LAST_NAME', 'UF_CRM_1774547381695'],
    'middle_last_name' => 'UF_CRM_1774547397576',
    'birth_date' => 'UF_CRM_1774547480227',
    'weeks_quoted' => 'UF_CRM_1774547548895',
    'employment_status' => 'UF_CRM_1774547594861',
    'last_salary' => 'UF_CRM_1774547607411',
    'state' => 'UF_CRM_1774547663862',
];

$defaultEnumMaps = [
    'weeks_quoted' => [
        'MENOS DE 500' => '1731',
        'ENTRE 500 Y 1000' => '1733',
        'MAS DE 1000' => '1735',
    ],
    'employment_status' => [
        'ACTIVO' => '1737',
        'NO ACTIVO' => '1739',
    ],
    'state' => [
        'AGUASCALIENTES' => '1743',
        'BAJA CALIFORNIA' => '1745',
        'BAJA CALIFORNIA SUR' => '1747',
        'CAMPECHE' => '1749',
        'CHIAPAS' => '1751',
        'CHIHUAHUA' => '1753',
        'CIUDAD DE MEXICO' => '1755',
        'COAHUILA' => '1757',
        'COLIMA' => '1759',
        'DURANGO' => '1761',
        'ESTADO DE MEXICO' => '1763',
        'GUANAJUATO' => '1765',
        'GUERRERO' => '1767',
        'HIDALGO' => '1769',
        'JALISCO' => '1771',
        'MICHOACAN' => '1773',
        'MORELOS' => '1775',
        'NAYARIT' => '1777',
        'NUEVO LEON' => '1779',
        'OAXACA' => '1781',
        'PUEBLA' => '1783',
        'QUERETARO' => '1785',
        'QUINTANA ROO' => '1787',
        'SAN LUIS POTOSI' => '1789',
        'SINALOA' => '1791',
        'SONORA' => '1793',
        'TABASCO' => '1795',
        'TAMAULIPAS' => '1797',
        'TLAXCALA' => '1799',
        'VERACRUZ' => '1801',
        'YUCATAN' => '1803',
        'ZACATECAS' => '1805',
    ],
];

return [
    'botmaker_to_bitrix' => [
        'currency' => env('BOTMAKER_SALARY_CURRENCY', 'MXN'),
        'source_aliases' => $decodeJson((string) env('BOTMAKER_SOURCE_ALIASES_JSON', ''), $defaultSourceAliases),
        'bitrix_fields' => $decodeJson((string) env('BOTMAKER_BITRIX_FIELDS_JSON', ''), $defaultBitrixFields),
        'enum_maps' => $decodeJson((string) env('BOTMAKER_ENUM_MAPS_JSON', ''), $defaultEnumMaps),
    ],
];
