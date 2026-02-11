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
    'income_data',
    'place_of_birth',
    'nationality',
    'age',
    'address',
    'religion',
    'sex',
    'in_school_youth',
    'out_of_school_youth',
    'other_disadvantage_youth',
    'occupation',
    'civil_status',
    'contact_number',
    'school_last_attended',
    'school_last_attended_month_year',
    'trainings_attended',
    'skills_interest_hobbies'
];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Missing required field: {$field}"]);
        exit;
    }
}

if (!isset($_POST['assistance_received']) || !is_array($_POST['assistance_received']) || count($_POST['assistance_received']) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'At least one assistance & intervention option is required.']);
    exit;
}

if (!isset($_POST['intervention_needed']) || !is_array($_POST['intervention_needed']) || count($_POST['intervention_needed']) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'At least one intervention needed option is required.']);
    exit;
}

$familyPayload = trim((string) ($_POST['family_compositions'] ?? ''));
if ($familyPayload === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Family composition is required.']);
    exit;
}

$familyCompositions = json_decode($familyPayload, true);
if (!is_array($familyCompositions) || count($familyCompositions) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid family composition payload.']);
    exit;
}

$familyRequired = ['name', 'relationship', 'gender', 'age', 'grade_year', 'isy', 'osy', 'work_labor_experience', 'income'];
foreach ($familyCompositions as $member) {
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

$membershipPayload = trim((string) ($_POST['memberships'] ?? ''));
$memberships = [];

if ($membershipPayload !== '') {
    $decodedMemberships = json_decode($membershipPayload, true);

    if (!is_array($decodedMemberships)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid membership payload.']);
        exit;
    }

    foreach ($decodedMemberships as $membership) {
        if (!is_array($membership)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid membership entry.']);
            exit;
        }

        $organizationName = trim((string) ($membership['organization_name'] ?? ''));
        $positionHeld = trim((string) ($membership['position_held'] ?? ''));
        $year = trim((string) ($membership['year'] ?? ''));

        if ($organizationName === '' && $positionHeld === '' && $year === '') {
            continue;
        }

        $memberships[] = [
            'organization_name' => $organizationName,
            'position_held' => $positionHeld,
            'year' => $year,
        ];
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
    $insertPyap = $connection->prepare(
        'INSERT INTO pyap_table (
            first_name, middle_name, last_name, suffix, income_data, place_of_birth, nationality, age,
            address, religion, sex, in_school_youth, out_of_school_youth, other_disadvantage_youth, ips,
            occupation, civil_status, contact_number, school_last_attended, school_last_attended_month_year,
            trainings_attended, skills_interest_hobbies, assistance_received, assistance_specify,
            intervention_needed, intervention_specify
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$insertPyap) {
        throw new Exception('Failed to prepare PYAP insert statement.');
    }

    $firstName = trim((string) $_POST['first_name']);
    $middleName = trim((string) ($_POST['middle_name'] ?? ''));
    $lastName = trim((string) $_POST['last_name']);
    $suffix = trim((string) ($_POST['suffix'] ?? ''));
    $dateOfBirth = trim((string) $_POST['income_data']);
    $placeOfBirth = trim((string) $_POST['place_of_birth']);
    $nationality = trim((string) $_POST['nationality']);
    $age = (int) $_POST['age'];
    $address = trim((string) $_POST['address']);
    $religion = trim((string) $_POST['religion']);
    $sex = trim((string) $_POST['sex']);
    $inSchoolYouth = trim((string) $_POST['in_school_youth']);
    $outOfSchoolYouth = trim((string) $_POST['out_of_school_youth']);
    $otherDisadvantageYouth = trim((string) $_POST['other_disadvantage_youth']);
    $ips = trim((string) ($_POST['ips'] ?? ''));
    $occupation = trim((string) $_POST['occupation']);
    $civilStatus = trim((string) $_POST['civil_status']);
    $contactNumber = trim((string) $_POST['contact_number']);
    $schoolLastAttended = trim((string) $_POST['school_last_attended']);
    $schoolLastAttendedMonthYear = trim((string) $_POST['school_last_attended_month_year']);
    $trainingsAttended = trim((string) $_POST['trainings_attended']);
    $skillsInterestHobbies = trim((string) $_POST['skills_interest_hobbies']);
    $assistanceReceived = implode(', ', array_map(static fn ($value) => trim((string) $value), $_POST['assistance_received']));
    $assistanceSpecify = trim((string) $_POST['assistance_specify']);
    $interventionNeeded = implode(', ', array_map(static fn ($value) => trim((string) $value), $_POST['intervention_needed']));
    $interventionSpecify = trim((string) $_POST['intervention_specify']);

    $insertPyap->bind_param(
        'sssssssissssssssssssssssss',
        $firstName,
        $middleName,
        $lastName,
        $suffix,
        $dateOfBirth,
        $placeOfBirth,
        $nationality,
        $age,
        $address,
        $religion,
        $sex,
        $inSchoolYouth,
        $outOfSchoolYouth,
        $otherDisadvantageYouth,
        $ips,
        $occupation,
        $civilStatus,
        $contactNumber,
        $schoolLastAttended,
        $schoolLastAttendedMonthYear,
        $trainingsAttended,
        $skillsInterestHobbies,
        $assistanceReceived,
        $assistanceSpecify,
        $interventionNeeded,
        $interventionSpecify
    );

    if (!$insertPyap->execute()) {
        throw new Exception('Failed to insert PYAP record: ' . $insertPyap->error);
    }

    $pyapId = $connection->insert_id;

    $insertFamily = $connection->prepare(
        'INSERT INTO pyap_family_composition_table (
            pyap_id, member_name, relationship, gender, age, grade_year, isy, osy, work_labor_experience, income
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$insertFamily) {
        throw new Exception('Failed to prepare family composition insert statement.');
    }

    foreach ($familyCompositions as $member) {
        $memberName = trim((string) $member['name']);
        $relationship = trim((string) $member['relationship']);
        $gender = trim((string) $member['gender']);
        $memberAge = (int) $member['age'];
        $gradeYear = trim((string) $member['grade_year']);
        $isy = trim((string) $member['isy']);
        $osy = trim((string) $member['osy']);
        $workLaborExperience = trim((string) $member['work_labor_experience']);
        $income = trim((string) $member['income']);

        $insertFamily->bind_param(
            'isssisssss',
            $pyapId,
            $memberName,
            $relationship,
            $gender,
            $memberAge,
            $gradeYear,
            $isy,
            $osy,
            $workLaborExperience,
            $income
        );

        if (!$insertFamily->execute()) {
            throw new Exception('Failed to insert family composition record: ' . $insertFamily->error);
        }
    }

    $insertMembership = $connection->prepare(
        'INSERT INTO pyap_membership_table (
            pyap_id, organization_name, position_held, year
        ) VALUES (?, ?, ?, ?)'
    );

    if (!$insertMembership) {
        throw new Exception('Failed to prepare membership insert statement.');
    }

    foreach ($memberships as $membership) {
        $organizationName = trim((string) $membership['organization_name']);
        $positionHeld = trim((string) $membership['position_held']);
        $year = trim((string) $membership['year']);

        $insertMembership->bind_param('isss', $pyapId, $organizationName, $positionHeld, $year);

        if (!$insertMembership->execute()) {
            throw new Exception('Failed to insert membership record: ' . $insertMembership->error);
        }
    }

    $connection->commit();

    echo json_encode([
        'success' => true,
        'message' => 'PYAP information saved successfully.',
        'pyap_id' => $pyapId
    ]);
} catch (Exception $exception) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} finally {
    $connection->close();
}
