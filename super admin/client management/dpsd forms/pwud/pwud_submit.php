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


$requiredFields = [
    'first_name',
    'last_name',
    'date_of_birth',
    'place_of_birth',
    'age',
    'address',
    'barangay',
    'religion',
    'sex',
    'educational_attainment',
    'occupation',
    'monthly_income',
    'civil_status',
    'contact_number',
    'services_needed'
];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Missing required field: {$field}"]);
        exit;
    }
}

$householdMembers = [];
if (isset($_POST['household_members']) && trim((string) $_POST['household_members']) !== '') {
    $decoded = json_decode((string) $_POST['household_members'], true);
    if (!is_array($decoded)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid household members payload.']);
        exit;
    }

    foreach ($decoded as $member) {
        $memberRequired = ['household_name', 'household_age', 'household_gender', 'household_relationship', 'household_occupation'];
        foreach ($memberRequired as $memberField) {
            if (!isset($member[$memberField]) || trim((string) $member[$memberField]) === '') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Incomplete household member data.']);
                exit;
            }
        }
    }

    $householdMembers = $decoded;
}

if (count($householdMembers) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'At least one household member is required.']);
    exit;
}

$connection = new mysqli('localhost', 'root', '', 'cswd_db');

if ($connection->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $connection->connect_error]);
    exit;
}

$connection->begin_transaction();
$recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$userContext = resolve_logged_in_user_notification_context($connection, $username, 'DPSD', 'PWUD');
$userDivision = $userContext['division'];
$userProgram = $userContext['program'];

try {
    $firstName = trim((string) $_POST['first_name']);
    $middleName = trim((string) ($_POST['middle_name'] ?? ''));
    $lastName = trim((string) $_POST['last_name']);
    $suffix = trim((string) ($_POST['suffix'] ?? ''));
    $dateOfBirth = trim((string) $_POST['date_of_birth']);
    $placeOfBirth = trim((string) $_POST['place_of_birth']);
    $age = (int) $_POST['age'];
    $address = trim((string) $_POST['address']);
    $brgy = trim((string) $_POST['barangay']);
    $division = "DPSD";
    $program = "PWUD";
    $religion = trim((string) $_POST['religion']);
    $sex = trim((string) $_POST['sex']);
    $educationalAttainment = trim((string) $_POST['educational_attainment']);
    $occupation = trim((string) $_POST['occupation']);
    $monthlyIncome = (float) $_POST['monthly_income'];
    $civilStatus = trim((string) $_POST['civil_status']);
    $contactNumber = trim((string) $_POST['contact_number']);
    $servicesNeeded = trim((string) $_POST['services_needed']);
    $served_by = $fullname;

    if ($recordId !== false && $recordId !== null) {
        $updatePwud = $connection->prepare(
            'UPDATE pwud_table
             SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, date_of_birth = ?, place_of_birth = ?, age = ?,
                 address = ?, barangay = ?, division = ?, program = ?, religion = ?, sex = ?, educational_attainment = ?,
                 occupation = ?, monthly_income = ?, civil_status = ?, contact_number = ?, services_needed = ?, served_by = ?
             WHERE key_id = ?'
        );

        if (!$updatePwud) {
            throw new Exception('Failed to prepare PWUD update statement.');
        }

        $updatePwud->bind_param(
            'ssssssissssssssdssssi',
            $firstName,
            $middleName,
            $lastName,
            $suffix,
            $dateOfBirth,
            $placeOfBirth,
            $age,
            $address,
            $brgy,
            $division,
            $program,
            $religion,
            $sex,
            $educationalAttainment,
            $occupation,
            $monthlyIncome,
            $civilStatus,
            $contactNumber,
            $servicesNeeded,
            $served_by,
            $recordId
        );

        if (!$updatePwud->execute()) {
            throw new Exception('Failed to update PWUD record.');
        }

        $updatePwud->close();

        insert_client_update_notification($connection, $fullname, $userRole, $userProgram, $userDivision);

        $pwudId = $recordId;

        $deleteHousehold = $connection->prepare('DELETE FROM pwud_household_table WHERE pwud_key_id = ?');
        if (!$deleteHousehold) {
            throw new Exception('Failed to prepare household delete statement.');
        }

        $deleteHousehold->bind_param('i', $pwudId);
        if (!$deleteHousehold->execute()) {
            throw new Exception('Failed to delete existing household members.');
        }
        $deleteHousehold->close();
    } else {
        $rowCountResult = $connection->query('SELECT COUNT(*) AS row_count FROM pwud_table');
        if (!$rowCountResult) {
            throw new Exception('Failed to get existing PWUD row count.');
        }

        $rowCountData = $rowCountResult->fetch_assoc();
        $rowCountResult->free();

        $rowCount = (int) ($rowCountData['row_count'] ?? 0);
        $currentYear = date('Y');
        $currentMonth = date('m');
        $sequelNumber = $rowCount;

        do {
            $controlNumber = sprintf('CSWD-PWUD-%s-%s-%d', $currentYear, $currentMonth, $sequelNumber);
            $controlCheck = $connection->prepare('SELECT 1 FROM pwud_table WHERE control_number = ? LIMIT 1');
            if (!$controlCheck) {
                throw new Exception('Failed to prepare control number uniqueness check.');
            }

            $controlCheck->bind_param('s', $controlNumber);
            if (!$controlCheck->execute()) {
                $controlCheck->close();
                throw new Exception('Failed to check control number uniqueness.');
            }

            $controlCheck->store_result();
            $controlExists = $controlCheck->num_rows > 0;
            $controlCheck->close();

            if ($controlExists) {
                $sequelNumber++;
            }
        } while ($controlExists);

        $insertPwud = $connection->prepare(
            'INSERT INTO pwud_table (
                control_number, first_name, middle_name, last_name, suffix, date_of_birth, place_of_birth, age,
                address,barangay,division,program, religion, sex, educational_attainment, occupation, monthly_income,
                civil_status, contact_number, services_needed, served_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if (!$insertPwud) {
            throw new Exception('Failed to prepare PWUD insert statement.');
        }

        $insertPwud->bind_param(
            'sssssssissssssssdssss',
            $controlNumber,
            $firstName,
            $middleName,
            $lastName,
            $suffix,
            $dateOfBirth,
            $placeOfBirth,
            $age,
            $address,
            $brgy,
            $division,
            $program,
            $religion,
            $sex,
            $educationalAttainment,
            $occupation,
            $monthlyIncome,
            $civilStatus,
            $contactNumber,
            $servicesNeeded,
            $served_by
        );

        if (!$insertPwud->execute()) {
            throw new Exception('Failed to insert PWUD record.');
        }

        $pwudId = $connection->insert_id;

        insert_client_creation_notification($connection, $fullname, $userRole, $userProgram, $userDivision);
        $insertPwud->close();

    }

    $insertHousehold = $connection->prepare(
        'INSERT INTO pwud_household_table (
            pwud_key_id, member_name, member_age, member_gender, relationship_to_pwud, member_occupation
        ) VALUES (?, ?, ?, ?, ?, ?)'
    );

    if (!$insertHousehold) {
        throw new Exception('Failed to prepare household insert statement.');
    }

    foreach ($householdMembers as $member) {
        $memberName = trim((string) $member['household_name']);
        $memberAge = (int) $member['household_age'];
        $memberGender = trim((string) $member['household_gender']);
        $memberRelationship = trim((string) $member['household_relationship']);
        $memberOccupation = trim((string) $member['household_occupation']);

        $insertHousehold->bind_param(
            'isisss',
            $pwudId,
            $memberName,
            $memberAge,
            $memberGender,
            $memberRelationship,
            $memberOccupation
        );

        if (!$insertHousehold->execute()) {
            throw new Exception('Failed to insert household member record.');
        }
    }

    $connection->commit();

    echo json_encode([
        'success' => true,
        'message' => $recordId ? 'PWUD record updated successfully.' : 'PWUD record and household members saved successfully.',
        'key_id' => $pwudId
    ]);
} catch (Exception $exception) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} finally {
    $connection->close();
}
