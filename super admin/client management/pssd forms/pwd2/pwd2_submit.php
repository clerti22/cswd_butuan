<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';
require_once __DIR__ . '/../../../../auth/notification.php';
$isEncoderArea = strpos(__DIR__, DIRECTORY_SEPARATOR . 'encoders' . DIRECTORY_SEPARATOR) !== false;
if ($isEncoderArea) {
    require_once __DIR__ . '/../../shared/form_access.php';
    require_encoder_program_access('PWD-2');
} else {
    require_login(['super admin', 'admin']);
}
start_auth_session();
header('Content-Type: application/json; charset=utf-8');
$fields = ['application_type', 'date_issued', 'first_name', 'middle_name', 'last_name', 'suffix', 'birth_date', 'civil_status', 'pwd_number', 'age', 'disability_types_primary', 'disability_causes_left', 'disability_causes_right', 'house_no_street', 'barangay', 'city', 'province', 'region', 'landline_no', 'mobile_no', 'email_address', 'educational_attainment', 'status_of_employment', 'category_of_employment', 'types_of_employment', 'occupation', 'occupation_others', 'skills', 'organization_affiliated', 'organization_contact_person', 'office_address', 'telephone_nos', 'sss_no', 'gsis_no', 'philhealth_no', 'pagibig_no', 'psn_no', 'father_last_name', 'father_first_name', 'father_middle_name', 'mother_last_name', 'mother_first_name', 'mother_middle_name', 'guardian_name_contact_person', 'guardian_contact_no', 'accomplished_by', 'accomplished_last_name', 'accomplished_first_name', 'accomplished_middle_name', 'certifying_physician_name', 'physician_license_no', 'processing_officer', 'approving_officer', 'encoder', 'reporting_unit', 'control_no'];
$connection = get_database_connection();
function pwd2_value(string $field): string {
    $value = $_POST[$field] ?? '';
    return is_array($value) ? implode(', ', array_map('trim', $value)) : trim((string) $value);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) { http_response_code(422); echo json_encode(['message'=>'Invalid record id.']); exit; }
    $stmt=$connection->prepare('SELECT * FROM pwd_table2 WHERE key_id = ? LIMIT 1');
    $stmt->bind_param('i',$id); $stmt->execute(); $record=$stmt->get_result()->fetch_assoc();
    if (!$record) { http_response_code(404); echo json_encode(['message'=>'PWD-2 record not found.']); exit; }
    echo json_encode(['record'=>$record]); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['message'=>'Method not allowed.']); exit; }
foreach (['first_name','last_name','birth_date','age','barangay'] as $required) {
    if (pwd2_value($required)==='') { http_response_code(422); echo json_encode(['message'=>'Missing required field: '.$required]); exit; }
}
$recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$values=[]; foreach($fields as $field) { $values[$field]=pwd2_value($field); }
$values['division']='PSSD'; $values['program']='PWD-2';
$values['contact_number']=$values['mobile_no'] !== '' ? $values['mobile_no'] : $values['landline_no'];
$values['sex']='';
$values['date_of_birth']=$values['birth_date'];
if ($values['control_no']==='') {
    $count=(int)($connection->query('SELECT COUNT(*) AS c FROM pwd_table2')->fetch_assoc()['c'] ?? 0);
    do { $control=sprintf('CSWD-PWD2-%s-%s-%d',date('Y'),date('m'),$count++); $check=$connection->prepare('SELECT 1 FROM pwd_table2 WHERE control_number=? LIMIT 1'); $check->bind_param('s',$control); $check->execute(); $exists=$check->get_result()->num_rows>0; $check->close(); } while($exists);
    $values['control_no']=$control;
}
$values['control_number']=$values['control_no'];
$dbFields=array_merge($fields,['division','program','contact_number','sex','date_of_birth','control_number']);
if ($recordId) {
    $sets=implode(', ', array_map(fn($f)=>"`$f` = ?", $dbFields));
    $stmt=$connection->prepare("UPDATE pwd_table2 SET $sets WHERE key_id = ?");
    $params=array_map(fn($f)=>$values[$f], $dbFields); $params[]=$recordId; $types=str_repeat('s',count($dbFields)).'i';
    $stmt->bind_param($types, ...$params); $stmt->execute(); $id=$recordId;
} else {
    $names=implode(', ', array_map(fn($f)=>"`$f`", $dbFields)); $marks=implode(', ', array_fill(0,count($dbFields),'?'));
    $stmt=$connection->prepare("INSERT INTO pwd_table2 ($names) VALUES ($marks)");
    $params=array_map(fn($f)=>$values[$f], $dbFields); $types=str_repeat('s',count($dbFields));
    $stmt->bind_param($types, ...$params); $stmt->execute(); $id=$connection->insert_id;
}
echo json_encode(['success'=>true,'id'=>$id,'message'=>'PWD-2 record saved successfully.']);
