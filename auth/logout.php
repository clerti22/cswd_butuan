<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

logout_user();
header('Location: /cswd_project/cswd/cswd_butuan/login form/index.php?message=logged_out');
exit;
