<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';
require_once __DIR__ . '/../../../../auth/notification.php';

require_login(['super admin']);
start_auth_session();

function resolve_foreign_key_column(mysqli $connection, string $tableName, array $candidates): string
{
    $escapedTableName = $connection->real_escape_string($tableName);
    $columnsResult = $connection->query("SHOW COLUMNS FROM `{$escapedTableName}`");
    if (!$columnsResult) {
        throw new Exception('Unable to inspect related CSAE table columns.');
    }

    $availableColumns = [];
    while ($column = $columnsResult->fetch_assoc()) {
        $field = trim((string) ($column['Field'] ?? ''));
        if ($field !== '') {
            $availableColumns[$field] = true;
        }
    }
    $columnsResult->free();

    foreach ($candidates as $candidate) {
        if (isset($availableColumns[$candidate])) {
            return $candidate;
        }
    }

    throw new Exception('Unable to resolve related CSAE foreign key column.');
}

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
    'first_name', 'last_name', 'sex', 'birth_date', 'age', 'birth_place', 'present_address', 'permanent_address', 'barangay',
    'education', 'school_last_attended', 'school_address', 'contact_number', 'is_4ps', 'philhealth', 'offense_type', 'incident_date',
    'perpetrator_name', 'referral_date', 'referring_party', 'case_status', 'criminal_case_no', 'info_source', 'complainant',
    'presenting_problem', 'background_child', 'background_family', 'background_community'
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
$recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$userContext = resolve_logged_in_user_notification_context($connection, $username, 'RPSD', 'CSAE');
$userDivision = $userContext['division'];
$userProgram = $userContext['program'];

try {
    $firstName = trim((string) $_POST['first_name']);
    $middleName = trim((string) ($_POST['middle_name'] ?? ''));
    $lastName = trim((string) $_POST['last_name']);
    $suffix = trim((string) ($_POST['suffix'] ?? ''));
    $nickname = trim((string) ($_POST['nickname'] ?? ''));
    $sex = trim((string) $_POST['sex']);
    $birthDate = trim((string) $_POST['birth_date']);
    $age = trim((string) $_POST['age']);
    $division = 'RPSD';
    $program = 'CSAE';
    $birthPlace = trim((string) $_POST['birth_place']);
    $presentAddress = trim((string) $_POST['present_address']);
    $permanentAddress = trim((string) $_POST['permanent_address']);
    $barangay = trim((string) $_POST['barangay']);
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
    $served = $fullname;
    $familyForeignKey = resolve_foreign_key_column($connection, 'csae_family_information_table', ['case_id', 'csae_id']);
    $treatmentForeignKey = resolve_foreign_key_column($connection, 'csae_treatment_plan_table', ['case_id', 'csae_id']);

    if ($recordId !== false && $recordId !== null) {
        execute_statement(
            $connection,
            'UPDATE `csae_table` SET
                first_name = ?, middle_name = ?, last_name = ?, suffix = ?, nickname = ?, sex = ?, birth_date = ?, age = ?, division = ?, program = ?, birth_place = ?,
                present_address = ?, permanent_address = ?, barangay = ?, education = ?, school_last_attended = ?, school_address = ?, health_condition = ?, contact_number = ?,
                is_4ps = ?, philhealth = ?, offense_type = ?, incident_date = ?, perpetrator_name = ?, referral_date = ?, referring_party = ?, case_status = ?, criminal_case_no = ?,
                info_source = ?, complainant = ?, presenting_problem = ?, background_child = ?, background_family = ?, background_community = ?, assessment = ?, recommendation = ?, served_by = ?
             WHERE key_id = ?',
            [$firstName, $middleName, $lastName, $suffix, $nickname, $sex, $birthDate, $age, $division, $program, $birthPlace, $presentAddress, $permanentAddress, $barangay, $education, $schoolLastAttended, $schoolAddress, $healthCondition, $contactNumber, $is4ps, $philhealth, $offenseType, $incidentDate, $perpetratorName, $referralDate, $referringParty, $caseStatus, $criminalCaseNo, $infoSource, $complainant, $presentingProblem, $backgroundChild, $backgroundFamily, $backgroundCommunity, $assessment, $recommendation, $served, (string) $recordId]
        )->close();
        insert_client_update_notification($connection, $fullname, $userRole, $userProgram, $userDivision);
        $csaeId = (string) $recordId;
        execute_statement($connection, sprintf('DELETE FROM `csae_family_information_table` WHERE `%s` = ?', $familyForeignKey), [$csaeId])->close();
        execute_statement($connection, sprintf('DELETE FROM `csae_treatment_plan_table` WHERE `%s` = ?', $treatmentForeignKey), [$csaeId])->close();
    } else {
        $rowCountResult = $connection->query('SELECT COUNT(*) AS row_count FROM `csae_table`');
        if (!$rowCountResult) { throw new Exception('Failed to get existing CSAE row count.'); }
        $rowCountData = $rowCountResult->fetch_assoc();
        $rowCountResult->free();
        $rowCount = (int) ($rowCountData['row_count'] ?? 0);
        $currentYear = date('Y');
        $currentMonth = date('m');
        $sequelNumber = $rowCount;
        do {
            $controlNumber = sprintf('CSWD-CSAE-%s-%s-%d', $currentYear, $currentMonth, $sequelNumber);
            $check = execute_statement($connection, 'SELECT 1 FROM `csae_table` WHERE control_number = ? LIMIT 1', [$controlNumber]);
            $check->store_result();
            $controlExists = $check->num_rows > 0;
            $check->close();
            if ($controlExists) { $sequelNumber++; }
        } while ($controlExists);

        execute_statement(
            $connection,
            'INSERT INTO `csae_table` (
                control_number, first_name, middle_name, last_name, suffix, nickname, sex, birth_date, age, division, program, birth_place,
                present_address, permanent_address, barangay, education, school_last_attended, school_address, health_condition, contact_number, is_4ps, philhealth,
                offense_type, incident_date, perpetrator_name, referral_date, referring_party, case_status, criminal_case_no, info_source, complainant,
                presenting_problem, background_child, background_family, background_community, assessment, recommendation, served_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$controlNumber, $firstName, $middleName, $lastName, $suffix, $nickname, $sex, $birthDate, $age, $division, $program, $birthPlace, $presentAddress, $permanentAddress, $barangay, $education, $schoolLastAttended, $schoolAddress, $healthCondition, $contactNumber, $is4ps, $philhealth, $offenseType, $incidentDate, $perpetratorName, $referralDate, $referringParty, $caseStatus, $criminalCaseNo, $infoSource, $complainant, $presentingProblem, $backgroundChild, $backgroundFamily, $backgroundCommunity, $assessment, $recommendation, $served]
        )->close();
        $csaeId = (string) $connection->insert_id;

        insert_client_creation_notification($connection, $fullname, $userRole, $userProgram, $userDivision);
    }

    foreach ($familyMembers as $member) {
        execute_statement(
            $connection,
            sprintf('INSERT INTO `csae_family_information_table` (%s, member_name, member_age, relationship, education_attainment, occupation, monthly_income) VALUES (?, ?, ?, ?, ?, ?, ?)', $familyForeignKey),
            [$csaeId, trim((string) $member['name']), trim((string) $member['age']), trim((string) $member['relationship']), trim((string) $member['education']), trim((string) $member['occupation']), trim((string) $member['income'])]
        )->close();
    }

    foreach ($treatmentPlans as $plan) {
        execute_statement(
            $connection,
            sprintf('INSERT INTO `csae_treatment_plan_table` (%s, objectives, activities, timeframe, responsible_person, remarks) VALUES (?, ?, ?, ?, ?, ?)', $treatmentForeignKey),
            [$csaeId, trim((string) ($plan['objectives'] ?? '')), trim((string) ($plan['activities'] ?? '')), trim((string) ($plan['timeframe'] ?? '')), trim((string) ($plan['responsible'] ?? '')), trim((string) ($plan['remarks'] ?? ''))]
        )->close();
    }

    $connection->commit();
    echo json_encode(['success' => true, 'message' => $recordId ? 'CSAE record updated successfully.' : 'CSAE record saved successfully.', 'record_id' => $csaeId]);
} catch (Throwable $exception) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} finally {
    $connection->close();
}
