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
    'sex',
    'birth_date',
    'age',
    'birth_place',
    'present_address',
    'permanent_address',
    'education',
    'school_last_attended',
    'school_address',
    'contact_number',
    'is_4ps',
    'philhealth',
    'offense_type',
    'incident_date',
    'perpetrator_name',
    'referral_date',
    'referring_party',
    'case_status',
    'criminal_case_no',
    'info_source',
    'complainant',
    'presenting_problem',
    'background_child',
    'background_family',
    'background_community'
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

$treatmentPayload = trim((string) ($_POST['treatment_plan'] ?? ''));
$treatmentPlans = [];

if ($treatmentPayload !== '') {
    $decodedTreatment = json_decode($treatmentPayload, true);
    if ($decodedTreatment === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid treatment plan payload.']);
        exit;
    }

    if (is_array($decodedTreatment)) {
        $treatmentPlans = $decodedTreatment;
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
    $insertMain = $connection->prepare(
        'INSERT INTO csae_table (
            first_name, middle_name, last_name, suffix, nickname, sex, birth_date, age, birth_place,
            present_address, permanent_address, education, school_last_attended, school_address,
            health_condition, contact_number, is_4ps, philhealth,
            offense_type, incident_date, perpetrator_name, referral_date, referring_party,
            case_status, criminal_case_no, info_source, complainant,
            presenting_problem, background_child, background_family, background_community,
            assessment, recommendation
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$insertMain) {
        throw new Exception('Failed to prepare CSAE insert statement.');
    }

    $firstName = trim((string) $_POST['first_name']);
    $middleName = trim((string) ($_POST['middle_name'] ?? ''));
    $lastName = trim((string) $_POST['last_name']);
    $suffix = trim((string) ($_POST['suffix'] ?? ''));
    $nickname = trim((string) ($_POST['nickname'] ?? ''));
    $sex = trim((string) $_POST['sex']);
    $birthDate = trim((string) $_POST['birth_date']);
    $age = (int) $_POST['age'];
    $birthPlace = trim((string) $_POST['birth_place']);
    $presentAddress = trim((string) $_POST['present_address']);
    $permanentAddress = trim((string) $_POST['permanent_address']);
    $education = trim((string) $_POST['education']);
    $schoolLastAttended = trim((string) $_POST['school_last_attended']);
    $schoolAddress = trim((string) $_POST['school_address']);
    $healthCondition = trim((string) ($_POST['health_condition'] ?? ''));
    $contactNumber = trim((string) $_POST['contact_number']);
    $is4ps = trim((string) $_POST['is_4ps']);
    $philhealth = trim((string) $_POST['philhealth']);
    $offenseType = trim((string) $_POST['offense_type']);
    $incidentDate = trim((string) $_POST['incident_date']);
    $perpetratorName = trim((string) $_POST['perpetrator_name']);
    $referralDate = trim((string) $_POST['referral_date']);
    $referringParty = trim((string) $_POST['referring_party']);
    $caseStatus = trim((string) $_POST['case_status']);
    $criminalCaseNo = trim((string) $_POST['criminal_case_no']);
    $infoSource = trim((string) $_POST['info_source']);
    $complainant = trim((string) $_POST['complainant']);
    $presentingProblem = trim((string) $_POST['presenting_problem']);
    $backgroundChild = trim((string) $_POST['background_child']);
    $backgroundFamily = trim((string) $_POST['background_family']);
    $backgroundCommunity = trim((string) $_POST['background_community']);
    $assessment = trim((string) ($_POST['assessment'] ?? ''));
    $recommendation = trim((string) ($_POST['recommendation'] ?? ''));

    $insertMain->bind_param(
        'sssssssisssssssssssssssssssssssss',
        $firstName,
        $middleName,
        $lastName,
        $suffix,
        $nickname,
        $sex,
        $birthDate,
        $age,
        $birthPlace,
        $presentAddress,
        $permanentAddress,
        $education,
        $schoolLastAttended,
        $schoolAddress,
        $healthCondition,
        $contactNumber,
        $is4ps,
        $philhealth,
        $offenseType,
        $incidentDate,
        $perpetratorName,
        $referralDate,
        $referringParty,
        $caseStatus,
        $criminalCaseNo,
        $infoSource,
        $complainant,
        $presentingProblem,
        $backgroundChild,
        $backgroundFamily,
        $backgroundCommunity,
        $assessment,
        $recommendation
    );

    if (!$insertMain->execute()) {
        throw new Exception('Unable to save CSAE record.');
    }

    $csaeId = $connection->insert_id;

    $insertFamily = $connection->prepare(
        'INSERT INTO csae_family_information_table (
            csae_id, member_name, member_age, relationship, education_attainment, occupation, monthly_income
        ) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$insertFamily) {
        throw new Exception('Failed to prepare family insert statement.');
    }

    foreach ($familyMembers as $member) {
        $memberName = trim((string) $member['name']);
        $memberAge = (int) $member['age'];
        $relationship = trim((string) $member['relationship']);
        $memberEducation = trim((string) $member['education']);
        $memberOccupation = trim((string) $member['occupation']);
        $memberIncome = trim((string) $member['income']);

        $insertFamily->bind_param(
            'isissss',
            $csaeId,
            $memberName,
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
            'INSERT INTO csae_treatment_plan_table (
                csae_id, objectives, activities, timeframe, responsible_person, remarks
            ) VALUES (?, ?, ?, ?, ?, ?)'
        );

        if (!$insertTreatment) {
            throw new Exception('Failed to prepare treatment plan insert statement.');
        }

        foreach ($treatmentPlans as $plan) {
            if (!is_array($plan)) {
                continue;
            }

            $objectives = trim((string) ($plan['objectives'] ?? ''));
            $activities = trim((string) ($plan['activities'] ?? ''));
            $timeframe = trim((string) ($plan['timeframe'] ?? ''));
            $responsible = trim((string) ($plan['responsible'] ?? ''));
            $remarks = trim((string) ($plan['remarks'] ?? ''));

            $insertTreatment->bind_param(
                'isssss',
                $csaeId,
                $objectives,
                $activities,
                $timeframe,
                $responsible,
                $remarks
            );

            if (!$insertTreatment->execute()) {
                throw new Exception('Unable to save treatment plan entry.');
            }
        }
    }

    $connection->commit();

    echo json_encode([
        'success' => true,
        'message' => 'CSAE record saved successfully.',
        'csae_id' => $csaeId
    ]);
} catch (Exception $exception) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} finally {
    $connection->close();
}
