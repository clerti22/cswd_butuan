<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';
require_once __DIR__ . '/../../../../auth/notification.php';

require_login(['super admin']);
start_auth_session();

$fullname = (string) ($_SESSION['full_name'] ?? '');
$username = (string) ($_SESSION['username'] ?? '');
$userRole = (string) ($_SESSION['role'] ?? '');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

function bind_all_params(mysqli_stmt $statement, array $params): void
{
    if (count($params) === 0) {
        return;
    }

    $types = str_repeat('s', count($params));
    $references = [$types];

    foreach ($params as $index => $value) {
        $references[] = &$params[$index];
    }

    call_user_func_array([$statement, 'bind_param'], $references);
}

function execute_statement(mysqli $connection, string $query, array $params = []): mysqli_stmt
{
    $statement = $connection->prepare($query);
    if (!$statement) {
        throw new Exception('Failed to prepare statement.');
    }

    bind_all_params($statement, $params);

    if (!$statement->execute()) {
        $message = $statement->error;
        $statement->close();
        throw new Exception($message !== '' ? $message : 'Failed to execute statement.');
    }

    return $statement;
}


$requiredFields = [
    'first_name', 'last_name', 'birth_place', 'birth_date', 'age', 'education', 'religion', 'address', 'barangay',
    'occupation', 'monthly_income', 'contact_number', 'civil_status', 'is_4ps', 'is_uct', 'is_nhts',
    'presenting_problem', 'family_environment', 'community', 'family_history'
];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Missing required field: {$field}"]);
        exit;
    }
}

$familyPayload = trim((string) ($_POST['family_information'] ?? ''));
$familyMembers = json_decode($familyPayload, true);
if (!is_array($familyMembers) || count($familyMembers) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please add at least one family information entry.']);
    exit;
}
foreach ($familyMembers as $member) {
    if (!is_array($member)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid family entry format.']);
        exit;
    }
    foreach (['name', 'age', 'relationship', 'education', 'occupation', 'income'] as $field) {
        if (!isset($member[$field]) || trim((string) $member[$field]) === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Incomplete family information entry found.']);
            exit;
        }
    }
}

$treatmentPayload = trim((string) ($_POST['initial_treatment_plan'] ?? ''));
$treatmentPlans = [];
if ($treatmentPayload !== '') {
    $decodedTreatment = json_decode($treatmentPayload, true);
    if ($decodedTreatment === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid initial treatment payload.']);
        exit;
    }
    if (is_array($decodedTreatment)) {
        $treatmentPlans = $decodedTreatment;
    }
}
foreach ($treatmentPlans as $plan) {
    if (!is_array($plan)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid treatment plan entry.']);
        exit;
    }
    foreach (['objectives', 'activities', 'responsible', 'resources', 'output'] as $field) {
        if (!isset($plan[$field]) || trim((string) $plan[$field]) === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Incomplete initial treatment entry found.']);
            exit;
        }
    }
}

$connection = new mysqli('localhost', 'root', '', 'cswd_db');
if ($connection->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $connection->connect_error]);
    exit;
}

$connection->begin_transaction();
$recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$userContext = resolve_logged_in_user_notification_context($connection, $username, 'RPSD', 'VAWC');
$userDivision = $userContext['division'];
$userProgram = $userContext['program'];

try {
    $firstName = trim((string) $_POST['first_name']);
    $middleName = trim((string) ($_POST['middle_name'] ?? ''));
    $lastName = trim((string) $_POST['last_name']);
    $suffix = trim((string) ($_POST['suffix'] ?? ''));
    $birthPlace = trim((string) $_POST['birth_place']);
    $birthDate = trim((string) $_POST['birth_date']);
    $age = trim((string) $_POST['age']);
    $division = 'RPSD';
    $program = 'VAWC';
    $education = trim((string) $_POST['education']);
    $religion = trim((string) $_POST['religion']);
    $address = trim((string) $_POST['address']);
    $barangay = trim((string) $_POST['barangay']);
    $occupation = trim((string) $_POST['occupation']);
    $monthlyIncome = trim((string) $_POST['monthly_income']);
    $contactNumber = trim((string) $_POST['contact_number']);
    $civilStatus = trim((string) $_POST['civil_status']);
    $is4ps = trim((string) $_POST['is_4ps']);
    $isUct = trim((string) $_POST['is_uct']);
    $isNhts = trim((string) $_POST['is_nhts']);
    $presentingProblem = trim((string) $_POST['presenting_problem']);
    $familyEnvironment = trim((string) $_POST['family_environment']);
    $community = trim((string) $_POST['community']);
    $familyHistory = trim((string) $_POST['family_history']);
    $recommendation = trim((string) ($_POST['recommendation'] ?? ''));
    $served = $fullname;

    if ($recordId !== false && $recordId !== null) {
        $updateMain = execute_statement(
            $connection,
            'UPDATE `vawc_table` SET
                first_name = ?, middle_name = ?, last_name = ?, suffix = ?, birth_place = ?, birth_date = ?, age = ?, division = ?, program = ?,
                education = ?, religion = ?, address = ?, barangay = ?, occupation = ?, monthly_income = ?, contact_number = ?,
                civil_status = ?, is_4ps = ?, is_uct = ?, is_nhts = ?, presenting_problem = ?, family_environment = ?, community = ?, family_history = ?, recommendation = ?, served_by = ?
             WHERE key_id = ?',
            [$firstName, $middleName, $lastName, $suffix, $birthPlace, $birthDate, $age, $division, $program, $education, $religion, $address, $barangay, $occupation, $monthlyIncome, $contactNumber, $civilStatus, $is4ps, $isUct, $isNhts, $presentingProblem, $familyEnvironment, $community, $familyHistory, $recommendation, $served, (string) $recordId]
        );
        $updateMain->close();
        $vawcId = (string) $recordId;
        execute_statement($connection, 'DELETE FROM `vawc_family_information_table` WHERE `vawc_id` = ?', [$vawcId])->close();
        execute_statement($connection, 'DELETE FROM `vawc_initial_treatment_plan_table` WHERE `vawc_id` = ?', [$vawcId])->close();
    } else {
        $rowCountResult = $connection->query('SELECT COUNT(*) AS row_count FROM `vawc_table`');
        if (!$rowCountResult) { throw new Exception('Failed to get existing VAWC row count.'); }
        $rowCountData = $rowCountResult->fetch_assoc();
        $rowCountResult->free();
        $rowCount = (int) ($rowCountData['row_count'] ?? 0);
        $currentYear = date('Y');
        $currentMonth = date('m');
        $sequelNumber = $rowCount;
        do {
            $controlNumber = sprintf('CSWD-VAWC-%s-%s-%d', $currentYear, $currentMonth, $sequelNumber);
            $check = execute_statement($connection, 'SELECT 1 FROM `vawc_table` WHERE control_number = ? LIMIT 1', [$controlNumber]);
            $check->store_result();
            $controlExists = $check->num_rows > 0;
            $check->close();
            if ($controlExists) { $sequelNumber++; }
        } while ($controlExists);

        $insertMain = execute_statement(
            $connection,
            'INSERT INTO `vawc_table` (
                control_number, first_name, middle_name, last_name, suffix, birth_place, birth_date, age, division, program,
                education, religion, address, barangay, occupation, monthly_income, contact_number,
                civil_status, is_4ps, is_uct, is_nhts, presenting_problem, family_environment, community, family_history, recommendation, served_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$controlNumber, $firstName, $middleName, $lastName, $suffix, $birthPlace, $birthDate, $age, $division, $program, $education, $religion, $address, $barangay, $occupation, $monthlyIncome, $contactNumber, $civilStatus, $is4ps, $isUct, $isNhts, $presentingProblem, $familyEnvironment, $community, $familyHistory, $recommendation, $served]
        );
        $vawcId = (string) $connection->insert_id;

        insert_client_creation_notification($connection, $fullname, $userRole, $userProgram, $userDivision);
        $insertMain->close();
    }

    foreach ($familyMembers as $member) {
        execute_statement(
            $connection,
            'INSERT INTO `vawc_family_information_table` (vawc_id, member_name, member_age, relationship, education_attainment, occupation, monthly_income) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$vawcId, trim((string) $member['name']), trim((string) $member['age']), trim((string) $member['relationship']), trim((string) $member['education']), trim((string) $member['occupation']), trim((string) $member['income'])]
        )->close();
    }

    foreach ($treatmentPlans as $plan) {
        execute_statement(
            $connection,
            'INSERT INTO `vawc_initial_treatment_plan_table` (vawc_id, objectives, activities, responsible_person, resources_needed, expected_output) VALUES (?, ?, ?, ?, ?, ?)',
            [$vawcId, trim((string) $plan['objectives']), trim((string) $plan['activities']), trim((string) $plan['responsible']), trim((string) $plan['resources']), trim((string) $plan['output'])]
        )->close();
    }

    $connection->commit();
    echo json_encode(['success' => true, 'message' => $recordId ? 'VAWC record updated successfully.' : 'VAWC record saved successfully.', 'record_id' => $vawcId]);
} catch (Throwable $exception) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} finally {
    $connection->close();
}
