<?php
declare(strict_types=1);

require_once 'c:\xampp\htdocs\cswd_project\cswd\cswd_butuan\auth\db.php';
$connection = get_database_connection();

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'application_type' => 'New Applicant',
    'date_issued' => '2026-01-01',
    'first_name' => 'John',
    'middle_name' => '',
    'last_name' => 'Doe',
    'suffix' => '',
    'birth_date' => '2000-01-01',
    'civil_status' => 'Single',
    'pwd_number' => '12345',
    'age' => '26',
    'disability_types_primary' => ['Visual Disability'],
    'disability_causes_left' => [],
    'disability_causes_right' => ['Acquired', 'Chronic Illness'],
    'house_no_street' => '',
    'barangay' => 'Maon',
    'city' => 'Butuan',
    'province' => 'Agusan',
    'region' => '13',
    'landline_no' => '',
    'mobile_no' => '09123456789',
    'email_address' => '',
    'educational_attainment' => 'College',
    'status_of_employment' => 'Employed',
    'category_of_employment' => 'Private',
    'types_of_employment' => 'Permanent/Regular',
    'occupation' => 'Professionals',
    'occupation_others' => '',
    'skills' => '',
    'organization_affiliated' => '',
    'organization_contact_person' => '',
    'office_address' => '',
    'telephone_nos' => '',
    'sss_no' => '',
    'gsis_no' => '',
    'philhealth_no' => '',
    'pagibig_no' => '',
    'psn_no' => '',
    'father_last_name' => '',
    'father_first_name' => '',
    'father_middle_name' => '',
    'mother_last_name' => '',
    'mother_first_name' => '',
    'mother_middle_name' => '',
    'guardian_name_contact_person' => '',
    'guardian_contact_no' => '',
    'accomplished_by' => '',
    'accomplished_last_name' => '',
    'accomplished_first_name' => '',
    'accomplished_middle_name' => '',
    'certifying_physician_name' => '',
    'physician_license_no' => '',
    'processing_officer' => '',
    'approving_officer' => '',
    'encoder' => '',
    'reporting_unit' => '',
    'control_no' => ''
];

$fields = array_keys($_POST);
function pwd2_value(string $field): string {
    $value = $_POST[$field] ?? '';
    return is_array($value) ? implode(', ', array_map('trim', $value)) : trim((string) $value);
}

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

$names=implode(', ', array_map(fn($f)=>"`$f`", $dbFields)); $marks=implode(', ', array_fill(0,count($dbFields),'?'));
$stmt=$connection->prepare("INSERT INTO pwd_table2 ($names) VALUES ($marks)");
if (!$stmt) {
    die("Prepare failed: " . $connection->error);
}
$params=array_map(fn($f)=>$values[$f], $dbFields); $types=str_repeat('s',count($dbFields));
$stmt->bind_param($types, ...$params); 
if ($stmt->execute()) {
    echo "Success: " . $connection->insert_id;
} else {
    echo "Error: " . $stmt->error;
}

