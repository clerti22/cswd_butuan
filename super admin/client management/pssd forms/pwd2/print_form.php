<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';
$isEncoderArea = strpos(__DIR__, DIRECTORY_SEPARATOR . 'encoders' . DIRECTORY_SEPARATOR) !== false;
if ($isEncoderArea) {
    require_once __DIR__ . '/../../shared/form_access.php';
    require_encoder_program_access('PWD-2');
} else {
    require_login(['super admin', 'admin']);
}
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(422); exit('Invalid record id.'); }
$conn = get_database_connection();
$stmt = $conn->prepare('SELECT * FROM pwd_table2 WHERE key_id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
if (!$record) { http_response_code(404); exit('PWD-2 record not found.'); }
function e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>PWD-2 Print</title><link rel="stylesheet" href="pwd2.css"><style>body{background:#fff}.container{display:block}.pwd2-card{box-shadow:none;max-width:1268px}.topbar,.sidebar,.actions{display:none}@media print{.pwd2-card{padding:0}}</style></head><body><main class="pwd2-card"><header class="form-heading"><img src="../../../images-logo/butuanseal.png" alt=""><div><strong>Department of Health</strong><p>Philippine Registry for Persons with Disabilities Version 4.0</p><h2>Application Form for PWD</h2></div><img src="../../../images-logo/cswd-logo.png" alt=""></header><h3>PWD-2 Record</h3><div class="grid three"><?php foreach ($record as $key => $value): if (in_array($key, ['created_at','updated_at'], true)) continue; ?><label><?php echo e(ucwords(str_replace('_',' ', $key))); ?><input value="<?php echo e($value); ?>" readonly></label><?php endforeach; ?></div></main><script>window.print()</script></body></html>
