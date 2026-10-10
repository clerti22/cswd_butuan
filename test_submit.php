<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'first_name' => 'Test',
    'last_name' => 'User',
    'birth_date' => '2000-01-01',
    'age' => '26',
    'barangay' => 'Maon',
    'disability_causes_right' => ['Acquired', 'Injury']
];
require 'c:\xampp\htdocs\cswd_project\cswd\cswd_butuan\super admin\client management\pssd forms\pwd2\pwd2_submit.php';

