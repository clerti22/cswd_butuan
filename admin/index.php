<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_login(['admin']);

readfile(__DIR__ . '/dashboard.html');
