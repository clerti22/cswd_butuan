<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../encoder_scope.php';

require_login(['encoder']);

header('Content-Type: application/json');

function respond_json(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function fetch_related_rows(mysqli $connection, string $query, string $types, array $params): array
{
    $statement = $connection->prepare($query);

    if (!$statement) {
        throw new RuntimeException('Failed to prepare related record query.');
    }

    $statement->bind_param($types, ...$params);
    $statement->execute();

    $result = $statement->get_result();
    $rows = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    $statement->close();

    return $rows;
}


function fetch_main_record_by_control_number(mysqli $connection, string $tableName, string $controlNumber): ?array
{
    $statement = $connection->prepare(sprintf('SELECT * FROM %s WHERE control_number = ? LIMIT 1', $tableName));

    if (!$statement) {
        throw new RuntimeException('Failed to prepare record query.');
    }

    $statement->bind_param('s', $controlNumber);
    $statement->execute();
    $result = $statement->get_result();
    $record = $result ? $result->fetch_assoc() : null;
    $statement->close();

    return $record ?: null;
}

function fetch_main_record(mysqli $connection, string $tableName, int $recordId): ?array
{
    $statement = $connection->prepare(sprintf('SELECT * FROM %s WHERE key_id = ? LIMIT 1', $tableName));

    if (!$statement) {
        throw new RuntimeException('Failed to prepare record query.');
    }

    $statement->bind_param('i', $recordId);
    $statement->execute();
    $result = $statement->get_result();
    $record = $result ? $result->fetch_assoc() : null;
    $statement->close();

    return $record ?: null;
}

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$program = strtolower(trim((string) ($_GET['program'] ?? '')));
$controlNumber = trim((string) ($_GET['control_number'] ?? ''));

if (($recordId === false || $recordId === null) && $controlNumber === '') {
    respond_json(400, ['success' => false, 'message' => 'Invalid or missing client id.']);
}

if ($program === '') {
    respond_json(400, ['success' => false, 'message' => 'Missing client program.']);
}

try {
    $connection = get_database_connection();
    $encoderDivision = encoder_scope_division();
    $encoderProgram = encoder_scope_program();


    $config = [
        'pwud' => [
            'table' => 'pwud_table',
            'related' => [
                'household_members' => [
                    'query' => 'SELECT member_name AS household_name, member_age AS household_age, member_gender AS household_gender, relationship_to_pwud AS household_relationship, member_occupation AS household_occupation FROM pwud_household_table WHERE pwud_key_id = ? ORDER BY household_id ASC',
                    'types' => 'i',
                ],
            ],
        ],
        'pwd' => [
            'table' => 'pwd_table',
            'related' => [
                'household_members' => [
                    'query' => 'SELECT member_name AS household_name, member_age AS household_age, member_gender AS household_gender, relationship_to_pwd AS household_relationship, member_occupation AS household_occupation FROM pwd_household_table WHERE pwd_key_id = ? ORDER BY household_id ASC',
                    'types' => 'i',
                ],
            ],
        ],
        'pwd-2' => [
            'table' => 'pwd_table2',
            'related' => [],
        ],
        'senior-citizen' => [
            'table' => 'snr_table',
            'related' => [
                'household_members' => [
                    'query' => 'SELECT member_name AS household_name, member_age AS household_age, member_gender AS household_gender, relationship_to_snr AS household_relationship, member_occupation AS household_occupation FROM snr_household_table WHERE snr_key_id = ? ORDER BY household_id ASC',
                    'types' => 'i',
                ],
            ],
        ],
        'solo-parent' => [
            'table' => 'soloparent_info_table',
            'related' => [
                'household_members' => [
                    'query' => 'SELECT household_name, household_sex, household_relationship, household_age, household_birthdate, household_civil_status, household_educational, household_occupation, household_monthly_income FROM household_members_soloparent WHERE solo_parent_id = ? ORDER BY id ASC',
                    'types' => 'i',
                ],
            ],
        ],
        'pyap' => [
            'table' => 'pyap_table',
            'related' => [
                'family_composition' => [
                    'query' => 'SELECT member_name AS name, relationship, gender, age, grade_year, isy, osy, work_labor_experience, income FROM pyap_family_composition_table WHERE pyap_id = ? ORDER BY id ASC',
                    'types' => 'i',
                ],
                'memberships' => [
                    'query' => 'SELECT organization_name, position_held, year FROM pyap_membership_table WHERE pyap_id = ? ORDER BY id ASC',
                    'types' => 'i',
                ],
            ],
        ],
        'ciu' => [
            'table' => 'ciu_table',
            'related' => [
                'family_composition' => [
                    'query' => 'SELECT member_name AS family_name, member_age AS family_age, member_gender AS family_gender, relationship_to_client AS family_relationship, education_attainment AS family_education, occupation AS family_occupation, income AS family_income FROM ciu_family_composition_table WHERE ciu_id = ? ORDER BY id ASC',
                    'types' => 'i',
                ],
            ],
        ],
        'vawc' => [
            'table' => 'vawc_table',
            'related' => [
                'family_information' => [
                    'query' => 'SELECT member_name AS name, member_age AS age, relationship, education_attainment AS education, occupation, monthly_income AS income FROM vawc_family_information_table WHERE vawc_id = ? ORDER BY id ASC',
                    'types' => 'i',
                ],
                'initial_treatment_plan' => [
                    'query' => 'SELECT objectives, activities, responsible_person AS responsible, resources_needed AS resources, expected_output AS output FROM vawc_initial_treatment_plan_table WHERE vawc_id = ? ORDER BY id ASC',
                    'types' => 'i',
                ],
            ],
        ],
        'csae' => [
            'table' => 'csae_table',
            'related' => [
                'family_information' => [
                    'query' => 'SELECT member_name AS name, member_age AS age, relationship, education_attainment AS education, occupation, monthly_income AS income FROM csae_family_information_table WHERE csae_id = ? ORDER BY id ASC',
                    'types' => 'i',
                ],
                'treatment_plan' => [
                    'query' => 'SELECT objectives, activities, timeframe, responsible_person AS responsible, remarks FROM csae_treatment_plan_table WHERE csae_id = ? ORDER BY id ASC',
                    'types' => 'i',
                ],
            ],
        ],
        'car' => [
            'table' => 'car_table',
            'related' => [
                'family_composition' => [
                    'query' => 'SELECT member_name, member_age, member_gender, member_status, member_relation, member_work, member_income, member_remarks FROM car_family_composition_table WHERE car_id = ? ORDER BY id ASC',
                    'types' => 'i',
                ],
                'initial_treatment_plan' => [
                    'query' => 'SELECT objectives, activities, responsible_person, resources_needed, expected_output FROM car_initial_treatment_plan_table WHERE car_id = ? ORDER BY id ASC',
                    'types' => 'i',
                ],
            ],
        ],
        'cicl' => [
            'table' => 'cicl_table',
            'related' => [
                'family_composition' => [
                    'query' => 'SELECT member_name, member_age, member_gender, member_status, member_relation, member_work, member_income, member_remarks FROM cicl_family_composition_table WHERE cicl_id = ? ORDER BY id ASC',
                    'types' => 'i',
                ],
                'initial_treatment_plan' => [
                    'query' => 'SELECT objectives, activities, responsible_person, resources_needed, expected_output FROM cicl_initial_treatment_plan_table WHERE cicl_id = ? ORDER BY id ASC',
                    'types' => 'i',
                ],
            ],
        ],
    ];

    if (!isset($config[$program])) {
        respond_json(400, ['success' => false, 'message' => 'Unsupported client program.']);
    }

    if ($program === 'faced') {
        if ($controlNumber === '') {
            respond_json(400, ['success' => false, 'message' => 'Missing FACED control number.']);
        }
        $record = fetch_main_record_by_control_number($connection, $config[$program]['table'], $controlNumber);
    } else {
        if ($recordId === false || $recordId === null) {
            respond_json(400, ['success' => false, 'message' => 'Invalid or missing client id.']);
        }
        $record = fetch_main_record($connection, $config[$program]['table'], $recordId);
    }

    if ($record === null) {
        respond_json(404, ['success' => false, 'message' => 'Client record not found.']);
    }

    $recordDivision = strtoupper(trim((string) ($record['division'] ?? '')));
    if ($recordDivision !== $encoderDivision) {
        respond_json(403, ['success' => false, 'message' => 'Access denied for this client record.']);
    }

    $recordProgram = strtoupper(trim((string) ($record['program'] ?? '')));
    if ($recordProgram !== $encoderProgram) {
        respond_json(403, ['success' => false, 'message' => 'Access denied for this program record.']);
    }

    $related = [];
    foreach ($config[$program]['related'] as $key => $definition) {
        $related[$key] = fetch_related_rows($connection, $definition['query'], $definition['types'], [$program === 'faced' ? $controlNumber : $recordId]);
    }

    $connection->close();

    respond_json(200, [
        'success' => true,
        'record' => $record,
        'related' => $related,
    ]);
} catch (Throwable $exception) {
    respond_json(500, ['success' => false, 'message' => 'Unable to load client record.']);
}
