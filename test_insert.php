<?php
require_once 'c:\xampp\htdocs\cswd_project\cswd\cswd_butuan\auth\db.php';
$connection = get_database_connection();

$stmt = $connection->prepare("INSERT INTO pwd_table2 (first_name, last_name, birth_date, age, barangay, disability_causes_right) VALUES (?, ?, ?, ?, ?, ?)");
$fn = 'Test';
$ln = 'User';
$bd = '2000-01-01';
$ag = '26';
$br = 'Maon';
$dc = 'Acquired';
$stmt->bind_param('ssssss', $fn, $ln, $bd, $ag, $br, $dc);
if ($stmt->execute()) {
    echo "Success: " . $connection->insert_id;
} else {
    echo "Error: " . $stmt->error;
}

