<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// start_auth_session() updates $_SESSION['last_activity'] automatically
start_auth_session();

echo json_encode(['status' => 'success', 'last_activity' => $_SESSION['last_activity']]);

