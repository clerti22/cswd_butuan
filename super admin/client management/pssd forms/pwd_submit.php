<?php
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

try {
    $insertPwud = $connection->prepare(
        'INSERT INTO pwd_table (
            first_name, middle_name, last_name, suffix, date_of_birth, place_of_birth, age,
            address,barangay,religion, sex, educational_attainment, occupation, monthly_income,
            civil_status, contact_number, services_needed
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$insertPwud) {
        throw new Exception('Failed to prepare PWUD insert statement.');
    }

    $firstName = trim((string) $_POST['first_name']);
    $middleName = trim((string) ($_POST['middle_name'] ?? ''));
    $lastName = trim((string) $_POST['last_name']);
    $suffix = trim((string) ($_POST['suffix'] ?? ''));
    $dateOfBirth = trim((string) $_POST['date_of_birth']);
    $placeOfBirth = trim((string) $_POST['place_of_birth']);
    $age = (int) $_POST['age'];
    $address = trim((string) $_POST['address']);
    $brgy = trim((string) $_POST['barangay']);
    $religion = trim((string) $_POST['religion']);
    $sex = trim((string) $_POST['sex']);
    $educationalAttainment = trim((string) $_POST['educational_attainment']);
    $occupation = trim((string) $_POST['occupation']);
    $monthlyIncome = (float) $_POST['monthly_income'];
    $civilStatus = trim((string) $_POST['civil_status']);
    $contactNumber = trim((string) $_POST['contact_number']);
    $servicesNeeded = trim((string) $_POST['services_needed']);

    $insertPwud->bind_param(
        'sssssssisssssdsss',
        $firstName,
        $middleName,
        $lastName,
        $suffix,
        $dateOfBirth,
        $placeOfBirth,
        $age,
        $address,
        $brgy,
        $religion,
        $sex,
        $educationalAttainment,
        $occupation,
        $monthlyIncome,
        $civilStatus,
        $contactNumber,
        $servicesNeeded
    );

    if (!$insertPwud->execute()) {
        throw new Exception('Failed to insert PWUD record.');
    }

    $pwudId = $connection->insert_id;

    $insertHousehold = $connection->prepare(
        'INSERT INTO pwd_household_table (
            pwd_key_id, member_name, member_age, member_gender, relationship_to_pwd, member_occupation
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
        'message' => 'PWD record and household members saved successfully.',
        'key_id' => $pwudId
    ]);
} catch (Exception $exception) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} finally {
    $connection->close();
}
