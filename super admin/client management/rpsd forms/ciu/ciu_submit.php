<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';

require_login(['super admin']);
start_auth_session();

$fullname = (string) ($_SESSION['full_name'] ?? '');

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
    'first_name', 'last_name', 'birth_date', 'birth_place', 'age', 'sex', 'civil_status', 'contact_number', 'monthly_income',
    'religion', 'occupation', 'education', 'address', 'barangay', 'member_senior', 'member_solo_parent', 'member_pwd', 'member_4ps',
    'service_prescribed_medicine', 'service_certificate_minor', 'service_hospital_bill', 'service_tesda_certificate',
    'service_burial', 'service_laboratory', 'service_legal_assistance'
];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Missing required field: {$field}"]);
        exit;
    }
}

$hasMemberSelection = false;
foreach (['member_senior', 'member_solo_parent', 'member_pwd', 'member_4ps'] as $field) {
    if (trim((string) $_POST[$field]) === 'Yes') {
        $hasMemberSelection = true;
        break;
    }
}
if (!$hasMemberSelection) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Select at least one Member(s) of option.']);
    exit;
}

$hasServiceSelection = false;
foreach (['service_prescribed_medicine', 'service_certificate_minor', 'service_hospital_bill', 'service_tesda_certificate', 'service_burial', 'service_laboratory', 'service_legal_assistance'] as $field) {
    if (trim((string) $_POST[$field]) === 'Yes') {
        $hasServiceSelection = true;
        break;
    }
}
if (!$hasServiceSelection) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Select at least one Services Needed option.']);
    exit;
}

$familyPayload = trim((string) ($_POST['family_composition'] ?? ''));
$familyMembers = json_decode($familyPayload, true);
if (!is_array($familyMembers) || count($familyMembers) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please add at least one family composition row.']);
    exit;
}
foreach ($familyMembers as $member) {
    if (!is_array($member)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid family composition payload format.']);
        exit;
    }

    foreach (['family_name', 'family_age', 'family_gender', 'family_relationship', 'family_education', 'family_occupation', 'family_income'] as $field) {
        if (!isset($member[$field]) || trim((string) $member[$field]) === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Incomplete family composition row found.']);
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

try {
    $firstName = trim((string) $_POST['first_name']);
    $middleName = trim((string) ($_POST['middle_name'] ?? ''));
    $lastName = trim((string) $_POST['last_name']);
    $suffix = trim((string) ($_POST['suffix'] ?? ''));
    $birthDate = trim((string) $_POST['birth_date']);
    $birthPlace = trim((string) $_POST['birth_place']);
    $age = trim((string) $_POST['age']);
    $division = 'RPSD';
    $program = 'CIU';
    $sex = trim((string) $_POST['sex']);
    $civilStatus = trim((string) $_POST['civil_status']);
    $contactNumber = trim((string) $_POST['contact_number']);
    $monthlyIncome = trim((string) $_POST['monthly_income']);
    $religion = trim((string) $_POST['religion']);
    $occupation = trim((string) $_POST['occupation']);
    $education = trim((string) $_POST['education']);
    $address = trim((string) $_POST['address']);
    $barangay = trim((string) $_POST['barangay']);
    $memberSenior = trim((string) $_POST['member_senior']);
    $memberSoloParent = trim((string) $_POST['member_solo_parent']);
    $memberPwd = trim((string) $_POST['member_pwd']);
    $member4ps = trim((string) $_POST['member_4ps']);
    $memberOthers = trim((string) ($_POST['member_others'] ?? ''));
    $servicePrescribedMedicine = trim((string) $_POST['service_prescribed_medicine']);
    $serviceCertificateMinor = trim((string) $_POST['service_certificate_minor']);
    $serviceHospitalBill = trim((string) $_POST['service_hospital_bill']);
    $serviceTesdaCertificate = trim((string) $_POST['service_tesda_certificate']);
    $serviceBurial = trim((string) $_POST['service_burial']);
    $serviceLaboratory = trim((string) $_POST['service_laboratory']);
    $serviceLegalAssistance = trim((string) $_POST['service_legal_assistance']);
    $serviceOthers = trim((string) ($_POST['service_others'] ?? ''));
    $served = $fullname;

    if ($recordId !== false && $recordId !== null) {
        $updateMain = execute_statement(
            $connection,
            'UPDATE `ciu_table` SET
                first_name = ?, middle_name = ?, last_name = ?, suffix = ?, birth_date = ?, birth_place = ?, age = ?, division = ?, program = ?, sex = ?, civil_status = ?,
                contact_number = ?, monthly_income = ?, religion = ?, occupation = ?, education = ?, address = ?, barangay = ?, member_senior = ?, member_solo_parent = ?,
                member_pwd = ?, member_4ps = ?, member_others = ?, service_prescribed_medicine = ?, service_certificate_minor = ?, service_hospital_bill = ?,
                service_tesda_certificate = ?, service_burial = ?, service_laboratory = ?, service_legal_assistance = ?, service_others = ?, served_by = ?
             WHERE key_id = ?',
            [$firstName, $middleName, $lastName, $suffix, $birthDate, $birthPlace, $age, $division, $program, $sex, $civilStatus, $contactNumber, $monthlyIncome, $religion, $occupation, $education, $address, $barangay, $memberSenior, $memberSoloParent, $memberPwd, $member4ps, $memberOthers, $servicePrescribedMedicine, $serviceCertificateMinor, $serviceHospitalBill, $serviceTesdaCertificate, $serviceBurial, $serviceLaboratory, $serviceLegalAssistance, $serviceOthers, $served, (string) $recordId]
        );
        $updateMain->close();
        $ciuId = (string) $recordId;
        $deleteFamily = execute_statement($connection, 'DELETE FROM `ciu_family_composition_table` WHERE `ciu_id` = ?', [$ciuId]);
        $deleteFamily->close();
    } else {
        $rowCountResult = $connection->query('SELECT COUNT(*) AS row_count FROM `ciu_table`');
        if (!$rowCountResult) {
            throw new Exception('Failed to get existing CIU row count.');
        }
        $rowCountData = $rowCountResult->fetch_assoc();
        $rowCountResult->free();
        $rowCount = (int) ($rowCountData['row_count'] ?? 0);
        $currentYear = date('Y');
        $currentMonth = date('m');
        $sequelNumber = $rowCount;
        do {
            $controlNumber = sprintf('CSWD-CIU-%s-%s-%d', $currentYear, $currentMonth, $sequelNumber);
            $check = execute_statement($connection, 'SELECT 1 FROM `ciu_table` WHERE control_number = ? LIMIT 1', [$controlNumber]);
            $check->store_result();
            $controlExists = $check->num_rows > 0;
            $check->close();
            if ($controlExists) {
                $sequelNumber++;
            }
        } while ($controlExists);

        $insertMain = execute_statement(
            $connection,
            'INSERT INTO `ciu_table` (
                control_number, first_name, middle_name, last_name, suffix, birth_date, birth_place, age, division, program, sex, civil_status,
                contact_number, monthly_income, religion, occupation, education, address, barangay,
                member_senior, member_solo_parent, member_pwd, member_4ps, member_others,
                service_prescribed_medicine, service_certificate_minor, service_hospital_bill, service_tesda_certificate, service_burial, service_laboratory, service_legal_assistance, service_others, served_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$controlNumber, $firstName, $middleName, $lastName, $suffix, $birthDate, $birthPlace, $age, $division, $program, $sex, $civilStatus, $contactNumber, $monthlyIncome, $religion, $occupation, $education, $address, $barangay, $memberSenior, $memberSoloParent, $memberPwd, $member4ps, $memberOthers, $servicePrescribedMedicine, $serviceCertificateMinor, $serviceHospitalBill, $serviceTesdaCertificate, $serviceBurial, $serviceLaboratory, $serviceLegalAssistance, $serviceOthers, $served]
        );
        $ciuId = (string) $connection->insert_id;
        $insertMain->close();
    }

    foreach ($familyMembers as $member) {
        $insertFamily = execute_statement(
            $connection,
            'INSERT INTO `ciu_family_composition_table` (
                ciu_id, member_name, member_age, member_gender, relationship_to_client, education_attainment, occupation, income
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$ciuId, trim((string) $member['family_name']), trim((string) $member['family_age']), trim((string) $member['family_gender']), trim((string) $member['family_relationship']), trim((string) $member['family_education']), trim((string) $member['family_occupation']), trim((string) $member['family_income'])]
        );
        $insertFamily->close();
    }

    $connection->commit();
    echo json_encode(['success' => true, 'message' => $recordId ? 'CIU record updated successfully.' : 'CIU record saved successfully.', 'record_id' => $ciuId]);
} catch (Throwable $exception) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} finally {
    $connection->close();
}
