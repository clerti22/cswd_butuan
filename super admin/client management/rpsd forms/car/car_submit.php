<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';
require_once __DIR__ . '/../../../../auth/notification.php';
require_once __DIR__ . '/../../shared/client-duplicate-check.php';

require_login(['super admin', 'admin', 'encoder']);
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

function delete_related_rows(mysqli $connection, string $tableName, string $foreignKey, string $recordId): void
{
    $statement = execute_statement(
        $connection,
        sprintf('DELETE FROM `%s` WHERE `%s` = ?', $tableName, $foreignKey),
        [$recordId]
    );
    $statement->close();
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

$familyRequired = ['member_name', 'member_age', 'member_gender', 'member_status', 'member_relation', 'member_work', 'member_income'];
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
$recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$userContext = resolve_logged_in_user_notification_context($connection, $username, 'RPSD', 'CAR');
$userDivision = $userContext['division'];
$userProgram = $userContext['program'];

try {
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
    $age = trim((string) $_POST['age']);
    $division = 'RPSD';
    $programName = 'CAR';
    $sex = trim((string) $_POST['sex']);
    $education = trim((string) $_POST['education']);
    $presentAddress = trim((string) $_POST['present_address']);
    $permanentAddress = trim((string) $_POST['permanent_address']);
    $barangay = trim((string) $_POST['barangay']);
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
    $classificationList = implode(', ', array_map(static fn ($value) => trim((string) $value), $classificationTags));
    $classificationOther = trim((string) ($_POST['classification_other'] ?? ''));
    $abuseList = implode(', ', array_map(static fn ($value) => trim((string) $value), $abuseTags));
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
    $served = $fullname;

    enforce_unique_client_identity(
        $connection,
        'car_table',
        'key_id',
        'birth_date',
        $firstName,
        $lastName,
        $birthDate,
        $barangay,
        $recordId !== false ? $recordId : null
    );

    if ($recordId !== false && $recordId !== null) {
        $updateMain = execute_statement(
            $connection,
            'UPDATE `car_table` SET
                interview_date = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?, nickname = ?, birth_date = ?, birth_place = ?,
                religion = ?, civil_status = ?, age = ?, division = ?, program = ?, sex = ?, education = ?, present_address = ?, permanent_address = ?, barangay = ?,
                spouse_first_name = ?, spouse_middle_name = ?, spouse_last_name = ?, spouse_suffix = ?, monthly_income_estimate = ?, income_source = ?, assistance_status = ?,
                food_expense = ?, house_lot_expense = ?, education_expense = ?, transport_expense = ?, misc_expense = ?, total_expenses = ?, total_income = ?, net_income = ?,
                classification_tags = ?, classification_other = ?, abuse_tags = ?, abuse_other = ?, type_of_offense = ?, date_of_apprehension = ?, by_whom = ?, date_of_offense = ?,
                source_of_information = ?, complainant = ?, presented_problem = ?, family_environment = ?, community_description = ?, family_history_background = ?, recommendation = ?, served_by = ?
             WHERE key_id = ?',
            [
                $interviewDate, $firstName, $middleName, $lastName, $suffix, $nickname, $birthDate, $birthPlace,
                $religion, $civilStatus, $age, $division, $programName, $sex, $education, $presentAddress, $permanentAddress, $barangay,
                $spouseFirstName, $spouseMiddleName, $spouseLastName, $spouseSuffix, $monthlyIncomeEstimate, $incomeSource, $assistanceStatus,
                $foodExpense, $houseLotExpense, $educationExpense, $transportExpense, $miscExpense, $totalExpenses, $totalIncome, $netIncome,
                $classificationList, $classificationOther, $abuseList, $abuseOther, $typeOfOffense, $dateOfApprehension, $byWhom, $dateOfOffense,
                $sourceOfInformation, $complainant, $presentedProblem, $familyEnvironment, $communityDescription, $familyHistoryBackground, $recommendation, $served,
                (string) $recordId
            ]
        );
        $updateMain->close();
        insert_client_update_notification($connection, $fullname, $userRole, $userProgram, $userDivision);
        $mainId = (string) $recordId;

        delete_related_rows($connection, 'car_family_composition_table', 'car_id', $mainId);
        delete_related_rows($connection, 'car_initial_treatment_plan_table', 'car_id', $mainId);
    } else {
        $rowCountResult = $connection->query('SELECT COUNT(*) AS row_count FROM `car_table`');
        if (!$rowCountResult) {
            throw new Exception('Failed to get existing CAR row count.');
        }

        $rowCountData = $rowCountResult->fetch_assoc();
        $rowCountResult->free();

        $rowCount = (int) ($rowCountData['row_count'] ?? 0);
        $currentYear = date('Y');
        $currentMonth = date('m');
        $sequelNumber = $rowCount;

        do {
            $controlNumber = sprintf('CSWD-CAR-%s-%s-%d', $currentYear, $currentMonth, $sequelNumber);
            $controlCheck = execute_statement($connection, 'SELECT 1 FROM `car_table` WHERE control_number = ? LIMIT 1', [$controlNumber]);
            $controlCheck->store_result();
            $controlExists = $controlCheck->num_rows > 0;
            $controlCheck->close();

            if ($controlExists) {
                $sequelNumber++;
            }
        } while ($controlExists);

        $insertMain = execute_statement(
            $connection,
            'INSERT INTO `car_table` (
                interview_date, control_number, first_name, middle_name, last_name, suffix, nickname, birth_date, birth_place,
                religion, civil_status, age, division, program, sex, education, present_address, permanent_address, barangay,
                spouse_first_name, spouse_middle_name, spouse_last_name, spouse_suffix,
                monthly_income_estimate, income_source, assistance_status,
                food_expense, house_lot_expense, education_expense, transport_expense, misc_expense,
                total_expenses, total_income, net_income,
                classification_tags, classification_other, abuse_tags, abuse_other,
                type_of_offense, date_of_apprehension, by_whom, date_of_offense,
                source_of_information, complainant, presented_problem,
                family_environment, community_description, family_history_background, recommendation, served_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $interviewDate, $controlNumber, $firstName, $middleName, $lastName, $suffix, $nickname, $birthDate, $birthPlace,
                $religion, $civilStatus, $age, $division, $programName, $sex, $education, $presentAddress, $permanentAddress, $barangay,
                $spouseFirstName, $spouseMiddleName, $spouseLastName, $spouseSuffix, $monthlyIncomeEstimate, $incomeSource, $assistanceStatus,
                $foodExpense, $houseLotExpense, $educationExpense, $transportExpense, $miscExpense, $totalExpenses, $totalIncome, $netIncome,
                $classificationList, $classificationOther, $abuseList, $abuseOther, $typeOfOffense, $dateOfApprehension, $byWhom, $dateOfOffense,
                $sourceOfInformation, $complainant, $presentedProblem, $familyEnvironment, $communityDescription, $familyHistoryBackground, $recommendation, $served
            ]
        );
        $mainId = (string) $connection->insert_id;

        insert_client_creation_notification($connection, $fullname, $userRole, $userProgram, $userDivision);
        $insertMain->close();
    }

    foreach ($familyMembers as $member) {
        $insertFamily = execute_statement(
            $connection,
            'INSERT INTO `car_family_composition_table` (
                `car_id`, member_name, member_age, member_gender, member_status, member_relation, member_work, member_income, member_remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $mainId,
                trim((string) $member['member_name']),
                trim((string) $member['member_age']),
                trim((string) $member['member_gender']),
                trim((string) $member['member_status']),
                trim((string) $member['member_relation']),
                trim((string) $member['member_work']),
                trim((string) $member['member_income']),
                trim((string) ($member['member_remarks'] ?? ''))
            ]
        );
        $insertFamily->close();
    }

    foreach ($treatmentRows as $row) {
        $insertTreatment = execute_statement(
            $connection,
            'INSERT INTO `car_initial_treatment_plan_table` (
                `car_id`, objectives, activities, responsible_person, resources_needed, expected_output
            ) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $mainId,
                trim((string) $row['objectives']),
                trim((string) $row['activities']),
                trim((string) $row['responsible_person']),
                trim((string) $row['resources_needed']),
                trim((string) $row['expected_output'])
            ]
        );
        $insertTreatment->close();
    }

    $connection->commit();

    echo json_encode([
        'success' => true,
        'message' => $recordId ? 'CAR record updated successfully.' : 'CAR record saved successfully.',
        'record_id' => $mainId
    ]);
} catch (DuplicateClientException $exception) {
    $connection->rollback();
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} catch (Throwable $exception) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} finally {
    $connection->close();
}
