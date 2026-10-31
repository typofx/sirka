<?php 
$currentDir = __DIR__;
while ($currentDir !== dirname($currentDir) && !file_exists($currentDir . '/%/config.php')) {
    $currentDir = dirname($currentDir);
}
require_once $currentDir . '/%/config.php';
?>

<?php

// Reset Action: Clean verification state and go back to Sign In
if (isset($_GET['reset']) || (isset($_POST['action']) && $_POST['action'] === 'reset')) {
    unset($_SESSION['temp_email']);
    unset($_SESSION['verification_code']);
    unset($_SESSION['2fa_method']);
    unset($_SESSION['email_verified']);
    redirect('auth/signin');
}

// Security Check: Only allow access if IP is verified via CAPTCHA
if (!is_ip_verified()) {
    redirect('auth/signin');
}

// Presence Check: Must have a temporary email stored from home.php/signup.php
$email = $_SESSION['temp_email'] ?? '';
if (!$email) {
    redirect('auth/signin');
}

// Redirect to the unified authenticator to handle the dispatch and UI
redirect('auth/authenticator');
