<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';
require_once __DIR__ . '/../../../../auth/notification.php';
require_once __DIR__ . '/../../shared/client-duplicate-check.php';

require_login(['super admin']);
start_auth_session();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$required = ['region','province','district','city_municipality','barangay','evacuation_site','first_name','last_name','birth_date','birth_place','age','sex','civil_status','contact_number','mother_maiden_name','religion','occupation','monthly_income','id_card_presented','id_card_number','is_4ps','is_ip','ethnicity_type','full_address','vulnerable_count','older_persons','pregnant_women','lactating_women','pwds_medical','house_ownership','shelter_damage'];
foreach ($required as $field) {
    if (!isset($_POST[$field]) || trim((string)$_POST[$field]) === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Please complete all the required fields.']);
        exit;
    }
}

$contact = trim((string)$_POST['contact_number']);
if (!preg_match('/^\d{11}$/', $contact)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please make it 11 digits.']);
    exit;
}

$family = json_decode((string)($_POST['family_information'] ?? '[]'), true);
if (!is_array($family) || count($family) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please complete all the required fields.']);
    exit;
}

$connection = get_database_connection();
$connection->begin_transaction();

$fullname = (string)($_SESSION['full_name'] ?? '');
$username = (string)($_SESSION['username'] ?? '');
$userRole = (string)($_SESSION['role'] ?? '');
$userContext = resolve_logged_in_user_notification_context($connection, $username, 'COD', 'FACED');

try {
    $controlNumber = trim((string)($_POST['control_number'] ?? ''));

    if ($controlNumber === '') {
        $countResult = $connection->query('SELECT COUNT(*) AS row_count FROM faced_table');
        $rowCount = (int)(($countResult ? $countResult->fetch_assoc()['row_count'] : 0) ?? 0);
        if ($countResult) { $countResult->free(); }
        $suffix = $rowCount;
        do {
            $controlNumber = sprintf('CSWD-FACED-%s-%s-%d', date('Y'), date('m'), $suffix);
            $checkStmt = $connection->prepare('SELECT 1 FROM faced_table WHERE control_number = ? LIMIT 1');
            $checkStmt->bind_param('s', $controlNumber);
            $checkStmt->execute();
            $exists = (bool)$checkStmt->get_result()?->fetch_assoc();
            $checkStmt->close();
            if ($exists) { $suffix++; }
        } while ($exists);
    }

    $fields = ['region','province','district','city_municipality','barangay','evacuation_site','first_name','middle_name','last_name','suffix','birth_date','birth_place','age','sex','civil_status','contact_number','mother_maiden_name','religion','occupation','monthly_income','id_card_presented','id_card_number','is_4ps','is_ip','ethnicity_type','full_address','vulnerable_count','older_persons','pregnant_women','lactating_women','pwds_medical','house_ownership','shelter_damage'];
    $values = [];
    foreach ($fields as $f) { $values[] = trim((string)($_POST[$f] ?? '')); }

    $existsStmt = $connection->prepare('SELECT control_number FROM faced_table WHERE control_number = ? LIMIT 1');
    $existsStmt->bind_param('s', $controlNumber);
    $existsStmt->execute();
    $isUpdate = (bool)$existsStmt->get_result()?->fetch_assoc();
    $existsStmt->close();

    enforce_unique_client_identity(
        $connection,
        'faced_table',
        'control_number',
        'birth_date',
        $values[6],
        $values[8],
        $values[10],
        $values[4],
        $isUpdate ? $controlNumber : null
    );

    if ($isUpdate) {
        $sql = 'UPDATE faced_table SET region=?,province=?,district=?,city_municipality=?,barangay=?,evacuation_site=?,first_name=?,middle_name=?,last_name=?,suffix=?,birth_date=?,birth_place=?,age=?,sex=?,civil_status=?,contact_number=?,mother_maiden_name=?,religion=?,occupation=?,monthly_income=?,id_card_presented=?,id_card_number=?,is_4ps=?,is_ip=?,ethnicity_type=?,full_address=?,vulnerable_count=?,older_persons=?,pregnant_women=?,lactating_women=?,pwds_medical=?,house_ownership=?,shelter_damage=?,division="COD",program="FACED",served_by=? WHERE control_number=?';
        $stmt = $connection->prepare($sql);
        $servedBy = $fullname;
        $bindValues = array_merge($values, [$servedBy, $controlNumber]);
        $stmt->bind_param(str_repeat('s', count($bindValues)), ...$bindValues);
        $stmt->execute();
        $stmt->close();

        $deleteStmt = $connection->prepare('DELETE FROM faced_family_information_table WHERE control_number = ?');
        $deleteStmt->bind_param('s', $controlNumber);
        $deleteStmt->execute();
        $deleteStmt->close();

        insert_client_update_notification($connection, $fullname, $userRole, $userContext['program'], $userContext['division']);
    } else {
        $sql = 'INSERT INTO faced_table (control_number,region,province,district,city_municipality,barangay,evacuation_site,first_name,middle_name,last_name,suffix,birth_date,birth_place,age,sex,civil_status,contact_number,mother_maiden_name,religion,occupation,monthly_income,id_card_presented,id_card_number,is_4ps,is_ip,ethnicity_type,full_address,vulnerable_count,older_persons,pregnant_women,lactating_women,pwds_medical,house_ownership,shelter_damage,division,program,served_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $stmt = $connection->prepare($sql);
        $servedBy = $fullname;
        $division='COD';$program='FACED';
        $bindValues = array_merge([$controlNumber], $values, [$division, $program, $servedBy]);
        $stmt->bind_param(str_repeat('s', count($bindValues)), ...$bindValues);
        $stmt->execute();
        $stmt->close();

        insert_client_creation_notification($connection, $fullname, $userRole, $userContext['program'], $userContext['division']);
    }

    $insertFamily = $connection->prepare('INSERT INTO faced_family_information_table (control_number,family_member,relation_to_head,birthdate,age,sex,highest_educational_attainment,occupation,remarks) VALUES (?,?,?,?,?,?,?,?,?)');
    foreach ($family as $row) {
        $familyMember = trim((string)($row['family_member'] ?? ''));
        $relation = trim((string)($row['relation'] ?? ''));
        $birthdate = trim((string)($row['birthdate'] ?? ''));
        $age = trim((string)($row['age'] ?? ''));
        $sex = trim((string)($row['sex'] ?? ''));
        $education = trim((string)($row['education'] ?? ''));
        $occupation = trim((string)($row['occupation'] ?? ''));
        $remarks = trim((string)($row['remarks'] ?? ''));
        if ($familyMember === '' || $relation === '' || $birthdate === '' || $age === '' || $sex === '' || $education === '' || $occupation === '' || $remarks === '') {
            throw new RuntimeException('Please complete all the required fields.');
        }
        $insertFamily->bind_param('sssssssss', $controlNumber, $familyMember, $relation, $birthdate, $age, $sex, $education, $occupation, $remarks);
        $insertFamily->execute();
    }
    $insertFamily->close();

    $connection->commit();
    echo json_encode(['success' => true, 'control_number' => $controlNumber]);
} catch (DuplicateClientException $e) {
    $connection->rollback();
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $connection->close();
}
