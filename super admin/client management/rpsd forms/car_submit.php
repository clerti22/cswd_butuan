<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$requiredFields = [
    'interview_date',
    'first_name',
    'last_name',
    'nickname',
    'birth_date',
    'birth_place',
    'religion',
    'civil_status',
    'age',
    'sex',
    'education',
    'present_address',
    'permanent_address',
    'monthly_income_estimate',
    'income_source',
    'assistance_status',
    'food_expense',
    'house_lot_expense',
    'education_expense',
    'transport_expense',
    'misc_expense',
    'total_expenses',
    'total_income',
    'net_income',
    'type_of_offense',
    'date_of_apprehension',
    'by_whom',
    'date_of_offense',
    'source_of_information',
    'complainant',
    'presented_problem',
    'family_environment',
    'community_description',
    'family_history_background'
];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Missing required field: {$field}"]);
        exit;
    }
}

$classificationPayload = trim((string) ($_POST['classification_tags'] ?? ''));
$classificationTags = json_decode($classificationPayload, true);
if (!is_array($classificationTags) || count($classificationTags) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please provide at least one child classification option.']);
    exit;
}

$abusePayload = trim((string) ($_POST['abuse_tags'] ?? ''));
$abuseTags = json_decode($abusePayload, true);
if (!is_array($abuseTags) || count($abuseTags) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please provide at least one abuse classification option.']);
    exit;
}

$familyPayload = trim((string) ($_POST['family_composition'] ?? ''));
$familyMembers = json_decode($familyPayload, true);
if (!is_array($familyMembers) || count($familyMembers) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'At least one family composition row is required.']);
    exit;
}

$familyRequired = [
    'member_name',
    'member_age',
    'member_gender',
    'member_status',
    'member_relation',
    'member_work',
    'member_income'
];

foreach ($familyMembers as $member) {
    if (!is_array($member)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid family composition entry.']);
        exit;
    }

    foreach ($familyRequired as $field) {
        if (!isset($member[$field]) || trim((string) $member[$field]) === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Incomplete family composition data.']);
            exit;
        }
    }
}

$treatmentPayload = trim((string) ($_POST['initial_treatment_plan'] ?? ''));
$treatmentRows = [];
if ($treatmentPayload !== '') {
    $decodedTreatment = json_decode($treatmentPayload, true);
    if ($decodedTreatment === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid initial treatment payload.']);
        exit;
    }

    if (is_array($decodedTreatment)) {
        $treatmentRows = $decodedTreatment;
    }
}

$treatmentRequired = ['objectives', 'activities', 'responsible_person', 'resources_needed', 'expected_output'];
foreach ($treatmentRows as $row) {
    if (!is_array($row)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid initial treatment entry.']);
        exit;
    }

    foreach ($treatmentRequired as $field) {
        if (!isset($row[$field]) || trim((string) $row[$field]) === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Incomplete initial treatment row found.']);
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
    $insertCar = $connection->prepare(
        'INSERT INTO car_table (
            interview_date, first_name, middle_name, last_name, suffix, nickname, birth_date, birth_place,
            religion, civil_status, age, sex, education, present_address, permanent_address,
            spouse_first_name, spouse_middle_name, spouse_last_name, spouse_suffix,
            monthly_income_estimate, income_source, assistance_status,
            food_expense, house_lot_expense, education_expense, transport_expense, misc_expense,
            total_expenses, total_income, net_income,
            classification_tags, classification_other, abuse_tags, abuse_other,
            type_of_offense, date_of_apprehension, by_whom, date_of_offense,
            source_of_information, complainant, presented_problem,
            family_environment, community_description, family_history_background, recommendation
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$insertCar) {
        throw new Exception('Failed to prepare CAR insert statement.');
    }

    $interviewDate = trim((string) $_POST['interview_date']);
    $firstName = trim((string) $_POST['first_name']);
    $middleName = trim((string) ($_POST['middle_name'] ?? ''));
    $lastName = trim((string) $_POST['last_name']);
    $suffix = trim((string) ($_POST['suffix'] ?? ''));
    $nickname = trim((string) $_POST['nickname']);
    $birthDate = trim((string) $_POST['birth_date']);
    $birthPlace = trim((string) $_POST['birth_place']);
    $religion = trim((string) $_POST['religion']);
    $civilStatus = trim((string) $_POST['civil_status']);
    $age = (int) $_POST['age'];
    $sex = trim((string) $_POST['sex']);
    $education = trim((string) $_POST['education']);
    $presentAddress = trim((string) $_POST['present_address']);
    $permanentAddress = trim((string) $_POST['permanent_address']);
    $spouseFirstName = trim((string) ($_POST['spouse_first_name'] ?? ''));
    $spouseMiddleName = trim((string) ($_POST['spouse_middle_name'] ?? ''));
    $spouseLastName = trim((string) ($_POST['spouse_last_name'] ?? ''));
    $spouseSuffix = trim((string) ($_POST['spouse_suffix'] ?? ''));
    $monthlyIncomeEstimate = trim((string) $_POST['monthly_income_estimate']);
    $incomeSource = trim((string) $_POST['income_source']);
    $assistanceStatus = trim((string) $_POST['assistance_status']);
    $foodExpense = trim((string) $_POST['food_expense']);
    $houseLotExpense = trim((string) $_POST['house_lot_expense']);
    $educationExpense = trim((string) $_POST['education_expense']);
    $transportExpense = trim((string) $_POST['transport_expense']);
    $miscExpense = trim((string) $_POST['misc_expense']);
    $totalExpenses = trim((string) $_POST['total_expenses']);
    $totalIncome = trim((string) $_POST['total_income']);
    $netIncome = trim((string) $_POST['net_income']);
    $classificationList = implode(', ', array_map(static fn ($v) => trim((string) $v), $classificationTags));
    $classificationOther = trim((string) ($_POST['classification_other'] ?? ''));
    $abuseList = implode(', ', array_map(static fn ($v) => trim((string) $v), $abuseTags));
    $abuseOther = trim((string) ($_POST['abuse_other'] ?? ''));
    $typeOfOffense = trim((string) $_POST['type_of_offense']);
    $dateOfApprehension = trim((string) $_POST['date_of_apprehension']);
    $byWhom = trim((string) $_POST['by_whom']);
    $dateOfOffense = trim((string) $_POST['date_of_offense']);
    $sourceOfInformation = trim((string) $_POST['source_of_information']);
    $complainant = trim((string) $_POST['complainant']);
    $presentedProblem = trim((string) $_POST['presented_problem']);
    $familyEnvironment = trim((string) $_POST['family_environment']);
    $communityDescription = trim((string) $_POST['community_description']);
    $familyHistoryBackground = trim((string) $_POST['family_history_background']);
    $recommendation = trim((string) ($_POST['recommendation'] ?? ''));

    $insertCar->bind_param(
        'ssssssssssissssssssssssssssssssssssssssssssss',
        $interviewDate,
        $firstName,
        $middleName,
        $lastName,
        $suffix,
        $nickname,
        $birthDate,
        $birthPlace,
        $religion,
        $civilStatus,
        $age,
        $sex,
        $education,
        $presentAddress,
        $permanentAddress,
        $spouseFirstName,
        $spouseMiddleName,
        $spouseLastName,
        $spouseSuffix,
        $monthlyIncomeEstimate,
        $incomeSource,
        $assistanceStatus,
        $foodExpense,
        $houseLotExpense,
        $educationExpense,
        $transportExpense,
        $miscExpense,
        $totalExpenses,
        $totalIncome,
        $netIncome,
        $classificationList,
        $classificationOther,
        $abuseList,
        $abuseOther,
        $typeOfOffense,
        $dateOfApprehension,
        $byWhom,
        $dateOfOffense,
        $sourceOfInformation,
        $complainant,
        $presentedProblem,
        $familyEnvironment,
        $communityDescription,
        $familyHistoryBackground,
        $recommendation
    );

    if (!$insertCar->execute()) {
        throw new Exception('Failed to insert CAR record: ' . $insertCar->error);
    }

    $carId = $connection->insert_id;

    $insertFamily = $connection->prepare(
        'INSERT INTO car_family_composition_table (
            car_id, member_name, member_age, member_gender, member_status,
            member_relation, member_work, member_income, member_remarks
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$insertFamily) {
        throw new Exception('Failed to prepare family composition insert statement.');
    }

    foreach ($familyMembers as $member) {
        $memberName = trim((string) $member['member_name']);
        $memberAge = (int) $member['member_age'];
        $memberGender = trim((string) $member['member_gender']);
        $memberStatus = trim((string) $member['member_status']);
        $memberRelation = trim((string) $member['member_relation']);
        $memberWork = trim((string) $member['member_work']);
        $memberIncome = trim((string) $member['member_income']);
        $memberRemarks = trim((string) ($member['member_remarks'] ?? ''));

        $insertFamily->bind_param(
            'isissssss',
            $carId,
            $memberName,
            $memberAge,
            $memberGender,
            $memberStatus,
            $memberRelation,
            $memberWork,
            $memberIncome,
            $memberRemarks
        );

        if (!$insertFamily->execute()) {
            throw new Exception('Failed to insert family composition record: ' . $insertFamily->error);
        }
    }

    if (count($treatmentRows) > 0) {
        $insertTreatment = $connection->prepare(
            'INSERT INTO car_initial_treatment_plan_table (
                car_id, objectives, activities, responsible_person, resources_needed, expected_output
            ) VALUES (?, ?, ?, ?, ?, ?)'
        );

        if (!$insertTreatment) {
            throw new Exception('Failed to prepare treatment plan insert statement.');
        }

        foreach ($treatmentRows as $row) {
            $objectives = trim((string) $row['objectives']);
            $activities = trim((string) $row['activities']);
            $responsiblePerson = trim((string) $row['responsible_person']);
            $resourcesNeeded = trim((string) $row['resources_needed']);
            $expectedOutput = trim((string) $row['expected_output']);

            $insertTreatment->bind_param(
                'isssss',
                $carId,
                $objectives,
                $activities,
                $responsiblePerson,
                $resourcesNeeded,
                $expectedOutput
            );

            if (!$insertTreatment->execute()) {
                throw new Exception('Failed to insert treatment plan row: ' . $insertTreatment->error);
            }
        }

        $insertTreatment->close();
    }

    $insertFamily->close();
    $insertCar->close();

    $connection->commit();

    echo json_encode([
        'success' => true,
        'message' => 'CAR record saved successfully.',
        'car_id' => $carId
    ]);
} catch (Throwable $exception) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} finally {
    $connection->close();
}
