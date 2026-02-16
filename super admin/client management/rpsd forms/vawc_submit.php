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
    'birth_place',
    'birth_date',
    'age',
    'education',
    'religion',
    'address',
    'barangay',
    'occupation',
    'monthly_income',
    'contact_number',
    'civil_status',
    'is_4ps',
    'is_uct',
    'is_nhts',
    'presenting_problem',
    'family_environment',
    'community',
    'family_history'
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

$familyRequired = ['name', 'age', 'relationship', 'education', 'occupation', 'income'];
foreach ($familyMembers as $member) {
    if (!is_array($member)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid family entry format.']);
        exit;
    }

    foreach ($familyRequired as $field) {
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

$treatmentRequired = ['objectives', 'activities', 'responsible', 'resources', 'output'];
foreach ($treatmentPlans as $plan) {
    if (!is_array($plan)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid treatment plan entry.']);
        exit;
    }

    foreach ($treatmentRequired as $field) {
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

try {
    $rowCountResult = $connection->query('SELECT COUNT(*) AS row_count FROM vawc_table');
    if (!$rowCountResult) {
        throw new Exception('Failed to get existing VAWC row count.');
    }

    $rowCountData = $rowCountResult->fetch_assoc();
    $rowCountResult->free();

    $rowCount = (int) ($rowCountData['row_count'] ?? 0);
    $currentYear = date('Y');
    $currentMonth = date('m');
    $sequelNumber = $rowCount;

    do {
        $controlNumber = sprintf('CSWD-VAWC-%s-%s-%d', $currentYear, $currentMonth, $sequelNumber);
        $controlCheck = $connection->prepare('SELECT 1 FROM vawc_table WHERE control_number = ? LIMIT 1');
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

    $insertMain = $connection->prepare(
        'INSERT INTO vawc_table (
            control_number, first_name, middle_name, last_name, suffix, birth_place, birth_date, age,
            education, religion, address, barangay, occupation, monthly_income, contact_number,
            civil_status, is_4ps, is_uct, is_nhts,
            presenting_problem, family_environment, community, family_history, recommendation
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$insertMain) {
        throw new Exception('Failed to prepare VAWC insert statement.');
    }

    $firstName = trim((string) $_POST['first_name']);
    $middleName = trim((string) ($_POST['middle_name'] ?? ''));
    $lastName = trim((string) $_POST['last_name']);
    $suffix = trim((string) ($_POST['suffix'] ?? ''));
    $birthPlace = trim((string) $_POST['birth_place']);
    $birthDate = trim((string) $_POST['birth_date']);
    $age = (int) $_POST['age'];
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

    $insertMain->bind_param(
        'sssssssissssssssssssssss',
        $controlNumber,
        $firstName,
        $middleName,
        $lastName,
        $suffix,
        $birthPlace,
        $birthDate,
        $age,
        $education,
        $religion,
        $address,
        $barangay,
        $occupation,
        $monthlyIncome,
        $contactNumber,
        $civilStatus,
        $is4ps,
        $isUct,
        $isNhts,
        $presentingProblem,
        $familyEnvironment,
        $community,
        $familyHistory,
        $recommendation
    );

    if (!$insertMain->execute()) {
        throw new Exception('Unable to save VAWC record.');
    }

    $vawcId = $connection->insert_id;

    $insertFamily = $connection->prepare(
        'INSERT INTO vawc_family_information_table (
            vawc_id, member_name, member_age, relationship, education_attainment, occupation, monthly_income
        ) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$insertFamily) {
        throw new Exception('Failed to prepare family information statement.');
    }

    foreach ($familyMembers as $member) {
        $name = trim((string) $member['name']);
        $memberAge = (int) $member['age'];
        $relationship = trim((string) $member['relationship']);
        $memberEducation = trim((string) $member['education']);
        $memberOccupation = trim((string) $member['occupation']);
        $memberIncome = trim((string) $member['income']);

        $insertFamily->bind_param(
            'isissss',
            $vawcId,
            $name,
            $memberAge,
            $relationship,
            $memberEducation,
            $memberOccupation,
            $memberIncome
        );

        if (!$insertFamily->execute()) {
            throw new Exception('Unable to save family information entry.');
        }
    }

    if (!empty($treatmentPlans)) {
        $insertTreatment = $connection->prepare(
            'INSERT INTO vawc_initial_treatment_plan_table (
                vawc_id, objectives, activities, responsible_person, resources_needed, expected_output
            ) VALUES (?, ?, ?, ?, ?, ?)'
        );

        if (!$insertTreatment) {
            throw new Exception('Failed to prepare initial treatment statement.');
        }

        foreach ($treatmentPlans as $plan) {
            $objectives = trim((string) $plan['objectives']);
            $activities = trim((string) $plan['activities']);
            $responsible = trim((string) $plan['responsible']);
            $resources = trim((string) $plan['resources']);
            $output = trim((string) $plan['output']);

            $insertTreatment->bind_param(
                'isssss',
                $vawcId,
                $objectives,
                $activities,
                $responsible,
                $resources,
                $output
            );

            if (!$insertTreatment->execute()) {
                throw new Exception('Unable to save initial treatment entry.');
            }
        }
    }

    $connection->commit();

    echo json_encode([
        'success' => true,
        'message' => 'VAWC record saved successfully.',
        'vawc_id' => $vawcId
    ]);
} catch (Exception $exception) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} finally {
    $connection->close();
}
