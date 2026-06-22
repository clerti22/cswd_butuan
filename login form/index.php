<?php

declare(strict_types=1);

$error = $_GET['error'] ?? null;
$message = $_GET['message'] ?? null;

$errorMessages = [
    'missing_credentials' => 'Please enter your username and password.',
    'user_not_found' => 'Username does not exist.',
    'invalid_password' => 'Incorrect password. Please try again.',
    'invalid_role' => 'Your account role is not configured for dashboard access.',
    'server_error' => 'Unable to login right now. Please try again later.',
    'unauthorized' => 'Please login first to continue.',
    'session_expired' => 'Your session has expired. Please login again.',
];

$infoMessages = [
    'logged_out' => 'You have been logged out successfully.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSWDD Login</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <main class="login-page">
        <section class="login-card" aria-labelledby="login-title">
            <img class="login-logo" src="../super admin/images-logo/cswd-logo.png" alt="Butuan City seal">
            <h1 id="login-title">CSWDD Login</h1>
            <?php if (isset($errorMessages[$error])): ?>
                <p style="color:#c62828; margin: 0 0 12px 0;"><?= htmlspecialchars($errorMessages[$error], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <?php if (isset($infoMessages[$message])): ?>
                <p style="color:#2e7d32; margin: 0 0 12px 0;"><?= htmlspecialchars($infoMessages[$message], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <form class="login-form" method="POST" action="../auth/login.php">
                <label class="input-group">
                    <span class="input-label">Username:</span>
                    <input type="text" name="username" autocomplete="username" required>
                </label>
                <label class="input-group">
                    <span class="input-label">Password:</span>
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>
                <button class="login-button" type="submit">Login</button>
            </form>
            
        </section>
        <footer style="margin-top: 10rem; color: gray;">© 2026 SMCC College of Computing and Information Sciences. Developed by Dizon et al.</footer>
    </main>
</body>
</html>
