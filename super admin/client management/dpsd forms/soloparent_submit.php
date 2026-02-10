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
    'religion',
    'sex',
    'educational_attainment',
    'occupation',
    'company_agency',
    'civil_status',
    'employment_status',
    'contact_number',
    'monthly_income',
    'philsys_card_number',
    'pantawid_beneficiary',
    'indigenous_person',
    'lgbtq_plus',
    'pwd_status',
    'solo_classification',
    'solo_needs',
    'household_members'
];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Missing required field: {$field}"]);
        exit;
    }
}

$pantawidBeneficiary = trim((string) $_POST['pantawid_beneficiary']);
$indigenousPerson = trim((string) $_POST['indigenous_person']);

if ($pantawidBeneficiary === 'yes' && trim((string) ($_POST['pantawid_household_id'] ?? '')) === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Pantawid household ID is required when pantawid beneficiary is yes.']);
    exit;
}

if ($indigenousPerson === 'yes' && trim((string) ($_POST['indigenous_affiliation_name'] ?? '')) === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Indigenous affiliation name is required when indigenous person is yes.']);
    exit;
}

$decodedHousehold = json_decode((string) $_POST['household_members'], true);
if (!is_array($decodedHousehold) || count($decodedHousehold) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'At least one household member is required.']);
    exit;
}

$householdRequiredFields = [
    'household_name',
    'household_sex',
    'household_relationship',
    'household_age',
    'household_birthdate',
    'household_civil_status',
    'household_educational',
    'household_occupation',
    'household_monthly_income'
];

foreach ($decodedHousehold as $member) {
    foreach ($householdRequiredFields as $memberField) {
        if (!isset($member[$memberField]) || trim((string) $member[$memberField]) === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Incomplete household member data.']);
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
    $insertSoloParent = $connection->prepare(
        'INSERT INTO soloparent_info_table (
            first_name, middle_name, last_name, suffix, date_of_birth, place_of_birth, age,
            address, religion, sex, educational_attainment, occupation, company_agency,
            civil_status, employment_status, contact_number, monthly_income, philsys_card_number,
            email_address, pantawid_beneficiary, pantawid_household_id, indigenous_person,
            indigenous_affiliation_name, lgbtq_plus, pwd_status, solo_classification, solo_needs
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$insertSoloParent) {
        throw new Exception('Failed to prepare solo parent insert statement.');
    }

    $firstName = trim((string) $_POST['first_name']);
    $middleName = trim((string) ($_POST['middle_name'] ?? ''));
    $lastName = trim((string) $_POST['last_name']);
    $suffix = trim((string) ($_POST['suffix'] ?? ''));
    $dateOfBirth = trim((string) $_POST['date_of_birth']);
    $placeOfBirth = trim((string) $_POST['place_of_birth']);
    $age = (int) $_POST['age'];
    $address = trim((string) $_POST['address']);
    $religion = trim((string) $_POST['religion']);
    $sex = trim((string) $_POST['sex']);
    $educationalAttainment = trim((string) $_POST['educational_attainment']);
    $occupation = trim((string) $_POST['occupation']);
    $companyAgency = trim((string) $_POST['company_agency']);
    $civilStatus = trim((string) $_POST['civil_status']);
    $employmentStatus = trim((string) $_POST['employment_status']);
    $contactNumber = trim((string) $_POST['contact_number']);
    $monthlyIncome = (float) $_POST['monthly_income'];
    $philsysCardNumber = trim((string) $_POST['philsys_card_number']);
    $emailAddress = trim((string) ($_POST['email_address'] ?? ''));
    $pantawidHouseholdId = trim((string) ($_POST['pantawid_household_id'] ?? ''));
    $indigenousAffiliationName = trim((string) ($_POST['indigenous_affiliation_name'] ?? ''));
    $lgbtqPlus = trim((string) $_POST['lgbtq_plus']);
    $pwdStatus = trim((string) $_POST['pwd_status']);
    $soloClassification = trim((string) $_POST['solo_classification']);
    $soloNeeds = trim((string) $_POST['solo_needs']);

    $insertSoloParent->bind_param(
        'ssssssisssssssssdssssssssss',
        $firstName,
        $middleName,
        $lastName,
        $suffix,
        $dateOfBirth,
        $placeOfBirth,
        $age,
        $address,
        $religion,
        $sex,
        $educationalAttainment,
        $occupation,
        $companyAgency,
        $civilStatus,
        $employmentStatus,
        $contactNumber,
        $monthlyIncome,
        $philsysCardNumber,
        $emailAddress,
        $pantawidBeneficiary,
        $pantawidHouseholdId,
        $indigenousPerson,
        $indigenousAffiliationName,
        $lgbtqPlus,
        $pwdStatus,
        $soloClassification,
        $soloNeeds
    );

    if (!$insertSoloParent->execute()) {
        throw new Exception('Failed to insert solo parent record.');
    }

    $soloParentId = $connection->insert_id;

    $insertHousehold = $connection->prepare(
        'INSERT INTO household_members_soloparent (
            solo_parent_id, household_name, household_sex, household_relationship,
            household_age, household_birthdate, household_civil_status,
            household_educational, household_occupation, household_monthly_income
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$insertHousehold) {
        throw new Exception('Failed to prepare household member insert statement.');
    }

    foreach ($decodedHousehold as $member) {
        $householdName = trim((string) $member['household_name']);
        $householdSex = trim((string) $member['household_sex']);
        $householdRelationship = trim((string) $member['household_relationship']);
        $householdAge = (int) $member['household_age'];
        $householdBirthdate = trim((string) $member['household_birthdate']);
        $householdCivilStatus = trim((string) $member['household_civil_status']);
        $householdEducational = trim((string) $member['household_educational']);
        $householdOccupation = trim((string) $member['household_occupation']);
        $householdMonthlyIncome = (float) $member['household_monthly_income'];

        $insertHousehold->bind_param(
            'isssissssd',
            $soloParentId,
            $householdName,
            $householdSex,
            $householdRelationship,
            $householdAge,
            $householdBirthdate,
            $householdCivilStatus,
            $householdEducational,
            $householdOccupation,
            $householdMonthlyIncome
        );

        if (!$insertHousehold->execute()) {
            throw new Exception('Failed to insert household member record.');
        }
    }

    $connection->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Solo parent record and household members saved successfully.',
        'key_id' => $soloParentId
    ]);
} catch (Exception $exception) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} finally {
    $connection->close();
}
