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
    'solo_needs'
];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Missing required field: {$field}"]);
        exit;
    }
}

$enumFields = ['pantawid_beneficiary', 'indigenous_person', 'lgbtq_plus', 'pwd_status'];
foreach ($enumFields as $field) {
    $value = strtolower(trim((string) $_POST[$field]));
    if (!in_array($value, ['yes', 'no'], true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Invalid value for {$field}. Use yes or no only."]);
        exit;
    }
}

$householdPayload = trim((string) ($_POST['household_members'] ?? ''));
if ($householdPayload === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'At least one household member is required.']);
    exit;
}

$householdMembers = json_decode($householdPayload, true);
if (!is_array($householdMembers) || count($householdMembers) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid household members payload.']);
    exit;
}

$householdRequired = [
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

foreach ($householdMembers as $member) {
    if (!is_array($member)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid household member record.']);
        exit;
    }

    foreach ($householdRequired as $memberField) {
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
$recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$userContext = resolve_logged_in_user_notification_context($connection, $username, 'DPSD', 'SOLO PARENT');
$userDivision = $userContext['division'];
$userProgram = $userContext['program'];

try {
    $rowCountResult = $connection->query('SELECT COUNT(*) AS row_count FROM soloparent_info_table');
    if (!$rowCountResult) {
        throw new Exception('Failed to get existing SOLOPARENT row count.');
    }

    $rowCountData = $rowCountResult->fetch_assoc();
    $rowCountResult->free();

    $rowCount = (int) ($rowCountData['row_count'] ?? 0);
    $currentYear = date('Y');
    $currentMonth = date('m');
    $sequelNumber = $rowCount;

    do {
        $controlNumber = sprintf('CSWD-SOLOPARENT-%s-%s-%d', $currentYear, $currentMonth, $sequelNumber);
        $controlCheck = $connection->prepare('SELECT 1 FROM soloparent_info_table WHERE control_number = ? LIMIT 1');
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

    $insertSoloParent = $connection->prepare(
        'INSERT INTO soloparent_info_table (
            control_number,
            first_name,
            middle_name,
            last_name,
            suffix,
            date_of_birth,
            place_of_birth,
            age,
            program,
            division,
            address,
            barangay,
            religion,
            sex,
            educational_attainment,
            occupation,
            company_agency,
            civil_status,
            employment_status,
            contact_number,
            monthly_income,
            philsys_card_number,
            email_address,
            pantawid_beneficiary,
            pantawid_household_id,
            indigenous_person,
            indigenous_affiliation_name,
            lgbtq_plus,
            pwd_status,
            solo_classification,
            solo_needs,
            served_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
    $program = "SOLO PARENT";
    $division = "DPSD";
    $address = trim((string) $_POST['address']);
    $brgy = trim((string) $_POST['barangay']);
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
    $pantawidBeneficiary = strtolower(trim((string) $_POST['pantawid_beneficiary']));
    $pantawidHouseholdId = trim((string) ($_POST['pantawid_household_id'] ?? ''));
    $indigenousPerson = strtolower(trim((string) $_POST['indigenous_person']));
    $indigenousAffiliation = trim((string) ($_POST['indigenous_affiliation_name'] ?? ''));
    $lgbtqPlus = strtolower(trim((string) $_POST['lgbtq_plus']));
    $pwdStatus = strtolower(trim((string) $_POST['pwd_status']));
    $soloClassification = trim((string) $_POST['solo_classification']);
    $soloNeeds = trim((string) $_POST['solo_needs']);
    $served = $fullname;

    if ($pantawidBeneficiary === 'no') {
        $pantawidHouseholdId = '';
    }

    if ($indigenousPerson === 'no') {
        $indigenousAffiliation = '';
    }

    if ($recordId !== false && $recordId !== null) {
        $updateSoloParent = $connection->prepare(
            'UPDATE soloparent_info_table SET
                first_name = ?, middle_name = ?, last_name = ?, suffix = ?, date_of_birth = ?, place_of_birth = ?, age = ?,
                program = ?, division = ?, address = ?, barangay = ?, religion = ?, sex = ?, educational_attainment = ?,
                occupation = ?, company_agency = ?, civil_status = ?, employment_status = ?, contact_number = ?, monthly_income = ?,
                philsys_card_number = ?, email_address = ?, pantawid_beneficiary = ?, pantawid_household_id = ?, indigenous_person = ?,
                indigenous_affiliation_name = ?, lgbtq_plus = ?, pwd_status = ?, solo_classification = ?, solo_needs = ?, served_by = ?
             WHERE key_id = ?'
        );

        if (!$updateSoloParent) {
            throw new Exception('Failed to prepare solo parent update statement.');
        }

        $updateSoloParent->bind_param(
            'ssssssissssssssssssdsssssssssssi',
            $firstName,
            $middleName,
            $lastName,
            $suffix,
            $dateOfBirth,
            $placeOfBirth,
            $age,
            $program,
            $division,
            $address,
            $brgy,
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
            $indigenousAffiliation,
            $lgbtqPlus,
            $pwdStatus,
            $soloClassification,
            $soloNeeds,
            $served,
            $recordId
        );

        if (!$updateSoloParent->execute()) {
            throw new Exception('Failed to update solo parent record.');
        }

        $updateSoloParent->close();
        insert_client_update_notification($connection, $fullname, $userRole, $userProgram, $userDivision);
        $soloParentId = $recordId;

        $deleteHouseholdMembers = $connection->prepare('DELETE FROM household_members_soloparent WHERE solo_parent_id = ?');
        if (!$deleteHouseholdMembers) {
            throw new Exception('Failed to prepare existing household member delete statement.');
        }

        $deleteHouseholdMembers->bind_param('i', $soloParentId);
        if (!$deleteHouseholdMembers->execute()) {
            throw new Exception('Failed to delete existing household member records.');
        }
        $deleteHouseholdMembers->close();
    } else {
        $insertSoloParent->bind_param(
            'sssssssissssssssssssdsssssssssss',
            $controlNumber,
            $firstName,
            $middleName,
            $lastName,
            $suffix,
            $dateOfBirth,
            $placeOfBirth,
            $age,
            $program,
            $division,
            $address,
            $brgy,
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
            $indigenousAffiliation,
            $lgbtqPlus,
            $pwdStatus,
            $soloClassification,
            $soloNeeds,
            $served
        );

        if (!$insertSoloParent->execute()) {
            throw new Exception('Failed to insert solo parent record.');
        }

        $soloParentId = $connection->insert_id;

        insert_client_creation_notification($connection, $fullname, $userRole, $userProgram, $userDivision);
    }

    $insertHouseholdMember = $connection->prepare(
        'INSERT INTO household_members_soloparent (
            solo_parent_id,
            household_name,
            household_sex,
            household_relationship,
            household_age,
            household_birthdate,
            household_civil_status,
            household_educational,
            household_occupation,
            household_monthly_income
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$insertHouseholdMember) {
        throw new Exception('Failed to prepare household member insert statement.');
    }

    foreach ($householdMembers as $member) {
        $householdName = trim((string) $member['household_name']);
        $householdSex = trim((string) $member['household_sex']);
        $householdRelationship = trim((string) $member['household_relationship']);
        $householdAge = (int) $member['household_age'];
        $householdBirthdate = trim((string) $member['household_birthdate']);
        $householdCivilStatus = trim((string) $member['household_civil_status']);
        $householdEducational = trim((string) $member['household_educational']);
        $householdOccupation = trim((string) $member['household_occupation']);
        $householdMonthlyIncome = (float) $member['household_monthly_income'];

        $insertHouseholdMember->bind_param(
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

        if (!$insertHouseholdMember->execute()) {
            throw new Exception('Failed to insert household member record.');
        }
    }

    $connection->commit();

    echo json_encode([
        'success' => true,
        'message' => $recordId ? 'Solo parent information and household members updated successfully.' : 'Solo parent information and household members saved successfully.',
        'solo_parent_id' => $soloParentId
    ]);
} catch (Exception $exception) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} finally {
    $connection->close();
}
