<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';

require_login(['encoder']);

header('Location: /cswd_project/cswd/cswd_butuan/admin/index.php');
exit;
