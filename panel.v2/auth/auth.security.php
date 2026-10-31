<?php require_once __DIR__ . '/../%/is_logged.php'; ?>
<?php require_once 'auth.validate.php'; ?>

<?php

// Google Authenticator Logic Support
require_once (BASE_GA_PATH . 'FixedBitNotation.php');
require_once (BASE_GA_PATH . 'GoogleAuthenticatorInterface.php');
require_once (BASE_GA_PATH . 'GoogleAuthenticator.php');
require_once (BASE_GA_PATH . 'GoogleQrUrl.php');


$action = $_POST['action'] ?? $_GET['action'] ?? $_SESSION['last_security_action'] ?? 'dashboard';

if ($action === 'dashboard') {
    unset($_SESSION['last_security_action']);
    unset($_SESSION['pwd_change_new'], $_SESSION['pwd_change_email_pin']);
    unset($_SESSION['forgot_pwd_email_pin'], $_SESSION['forgot_pwd_pin_passed']);
    unset($_SESSION['disable_email_pin'], $_SESSION['disable_email_pin_sent']);
} else {
    $_SESSION['last_security_action'] = $action;
}

// Clear temporary flow variables on fresh entry
if ($action === 'change_password' && !isset($_POST['current_password'])) {
    unset($_SESSION['pwd_change_new'], $_SESSION['pwd_change_email_pin']);
} elseif ($action === 'forgot_password' && !isset($_POST['totp_token']) && !isset($_POST['email_pin'])) {
    unset($_SESSION['forgot_pwd_email_pin'], $_SESSION['forgot_pwd_pin_passed']);
}
$step = isset($_POST['step']) ? (int) $_POST['step'] : (isset($_GET['step']) ? (int) $_GET['step'] : 1);

// Flash messages from session
$success_msg = $_SESSION['flash_success'] ?? '';
$error_msg = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Helper for clean redirects with flash messages
function clean_redirect($success = null, $error = null) {
    redirect('auth/security?action=dashboard', [
        'success' => $success,
        'error'   => $error
    ]);
}

// Database fetching for current session user
$email = $_SESSION['registered_email'] ?? null;
// 1. Fetch Fresh Identity & Security Config
$user_id = $_SESSION['user_id'] ?? 0;
$user_row = getUserIdentity($pdo, $user_id);

if (!$user_row) {
    session_destroy();
    redirect('auth/signin');
}

// 2. Map config variables for backward compatibility
$user_config = $user_row; // Config is now joined in user_row
$email = $user_row['email'];
$username = $user_row['username'];
$user_level = $user_row['level'];

$is_auth_app_enabled = (int)$user_row['authenticator_app'];
$is_email_enabled = (int)$user_row['email_mfa'];
$is_mobile_enabled = (int)$user_row['mobile_phone'];
$is_telegram_enabled = (int)$user_row['telegram'];

// 3. Status logic (for dashboard display)
$active_layers = calculateActiveLayers($user_row, $_SESSION['temp_2fa_passed'] ?? []);
$is_active = ($active_layers >= 2);
$status_color = $is_active ? '#28a745' : '#dc3545';
$status_text = $is_active ? 'Account Protected' : 'Security Layer Required';

// -------------------------------------------------------------
// Security Configuration Mapping
// -------------------------------------------------------------
// We now use email_sent (1 = email 2fa) and qrcode_sent (1 = authenticator 2fa)
$user_config = [
    'biometrics' => $user_row['biometrics'] ?? 0,
    'authenticator_app' => $user_row['authenticator_app'] ?? 0,
    'email' => $user_row['email_mfa'] ?? 0,
    'mobile_phone' => $user_row['mobile_phone'] ?? 0,
    'telegram' => $user_row['telegram'] ?? 0,
    'token' => $user_row['token'] ?? 0,
    'auth_secret' => $user_row['auth_secret'] ?? null
];

$phone = '***8***3';

$mail_from = defined('MAIL_FROM') ? MAIL_FROM : 'no-reply@plata.ie';

// Helper: Send Email
function send_security_mail($to, $code, $from)
{
    if (!$to || $to === '***.com')
        return false; // Prevent logic bomb fail
    $subject = "Code - Security Verification - Typo FX";
    $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: " . $from . "\r\n";
    $msg = "<html><body style='font-family: sans-serif; background: #fff; padding: 20px;'><div style='max-width: 400px; margin: 0 auto; background: #f9f9f9; padding: 30px; border-radius: 8px; border: 1px solid #ddd; text-align: center;'><h2 style='color: #000;'>Security Code</h2><p>Your authentication code is:</p><div style='font-size: 32px; font-weight: bold; letter-spacing: 5px; margin: 30px 0; background: #fff; border: 1px solid #ddd; padding: 15px;'>$code</div></div></body></html>";
    return mail($to, $subject, $msg, $headers);
}

// ---- CHANGE EMAIL LOGIC ----
if ($action == 'change_email') {
    if ($step == 1 && $_SERVER["REQUEST_METHOD"] == "POST") {
        $_SESSION['email_change_old_pin'] = rand(100000, 999999);
        $sent = send_security_mail($email, $_SESSION['email_change_old_pin'], $mail_from);
        if ($sent) {
            $action = 'change_email';
            $step = 2;
        } else {
            $_SESSION['flash_error'] = "Could not send verification code. Please try again later.";
        }
    } elseif ($step == 2 && $_SERVER["REQUEST_METHOD"] == "POST") {
        $old_pin = trim($_POST['old_email_pin'] ?? '');
        $totp = trim($_POST['totp_token'] ?? '');
        $new_email = trim($_POST['new_email'] ?? '');

        $g = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
        $secret = $users_data['users'][$user_key]['totp_secret'] ?? '';

        if ($old_pin == $_SESSION['email_change_old_pin']) {
            $secret = $user_config['auth_secret'] ?? '';
            if ($g->checkCode($secret, $totp)) {

                $validationResult = validateEmail($new_email, 'auth.email_rules.json');

                if ($validationResult === true) {
                    $_SESSION['email_change_new_addr'] = $new_email;
                    $_SESSION['email_change_new_pin'] = rand(100000, 999999);
                    send_security_mail($new_email, $_SESSION['email_change_new_pin'], $mail_from);
                    $action = 'change_email';
                    $step = 3;
                } else {
                    $_SESSION['flash_error'] = $validationResult;
                }
            } else {
                $_SESSION['flash_error'] = "Incorrect Authenticator Code.";
            }
        } else {
            $_SESSION['flash_error'] = "Incorrect Old Email Code.";
        }
    } elseif ($step == 3 && $_SERVER["REQUEST_METHOD"] == "POST") {
        $new_pin = trim($_POST['new_email_pin'] ?? '');
        if ($new_pin == $_SESSION['email_change_new_pin']) {
            // SUCCESS! Update the main table
            try {
                $pdo->prepare("UPDATE $table SET email = ? WHERE id = ?")->execute([$_SESSION['email_change_new_addr'], $user_id]);
                $_SESSION['registered_email'] = $_SESSION['email_change_new_addr'];
            } catch (Exception $e) {
                error_log("Email update error: " . $e->getMessage());
            }

            unset($_SESSION['email_change_old_pin']);
            unset($_SESSION['email_change_new_pin']);
            unset($_SESSION['email_change_new_addr']);
            clean_redirect('email_updated');
        } else {
            $_SESSION['flash_error'] = "Incorrect New Email Code.";
        }
    }
}

// ---- NORMAL CHANGE PASSWORD LOGIC ----
if ($action == 'change_password') {
    if ($step == 1 && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password'])) {
        $current_pass = trim($_POST['current_password'] ?? '');
        $new_pass = trim($_POST['new_password'] ?? '');
        $confirm_pass = trim($_POST['confirm_password'] ?? '');

        if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
            $_SESSION['flash_error'] = "All password fields are required.";
        } elseif ($new_pass !== $confirm_pass) {
            $_SESSION['flash_error'] = "New passwords do not match.";
        } else {
            $validation = validatePassword($new_pass);
            if ($validation !== true) {
                $_SESSION['flash_error'] = $validation;
            } else {
                // VERIFY CURRENT PASSWORD
                try {
                    $stmt = $pdo->prepare("SELECT password FROM $tableUsers WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $db_pass = $stmt->fetchColumn();

                    if (!$db_pass || !password_verify($current_pass, $db_pass)) {
                        $_SESSION['flash_error'] = "Incorrect current password.";
                    } else {
                        $_SESSION['pwd_change_new'] = $new_pass;

                        if ($is_auth_app_enabled) {
                            $step = 2;
                        } elseif ($is_email_enabled) {
                            $_SESSION['pwd_change_email_pin'] = rand(100000, 999999);
                            $sent = send_security_mail($email, $_SESSION['pwd_change_email_pin'], $mail_from);
                            if ($sent) {
                                $step = 3;
                            } else {
                                $_SESSION['flash_error'] = "Mail server failed to send email during change_password step 1.";
                            }
                        } else {
                            // NO PROTECTION ACTIVE - UPDATE IMMEDIATELY
                            $hashed_pwd = password_hash($new_pass, PASSWORD_DEFAULT);
                            $pdo->prepare("UPDATE $tableUsers SET password = ? WHERE id = ?")->execute([$hashed_pwd, $user_id]);
                            unset($_SESSION['pwd_change_new']);
                            clean_redirect('password_updated');
                        }
                    }
                } catch (Exception $e) {
                    error_log("Password verification error: " . $e->getMessage());
                    $_SESSION['flash_error'] = "A technical error occurred.";
                }
            }
        }
    } elseif ($step == 2 && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['totp_token'])) {
        $totp = trim($_POST['totp_token'] ?? '');
        $g = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
        $secret = $user_config['auth_secret'] ?? '';

        // Added discrepancy of 4 to allow 2 minutes of tolerance for desync
        if ($g->checkCode($secret, $totp, 4)) {
            if ($is_email_enabled) {
                $_SESSION['pwd_change_email_pin'] = rand(100000, 999999);
                $sent = send_security_mail($email, $_SESSION['pwd_change_email_pin'], $mail_from);
                if ($sent) {
                    $step = 3;
                } else {
                    $_SESSION['flash_error'] = "Mail server failed to send email to: " . htmlspecialchars($email);
                }
            } else {
                // UPDATE IMMEDIATELY
                if ($user_id > 0) {
                    try {
                        $hashed_pwd = password_hash($_SESSION['pwd_change_new'], PASSWORD_DEFAULT);
                        $pdo->prepare("UPDATE $tableUsers SET password = ? WHERE id = ?")->execute([$hashed_pwd, $user_id]);
                        unset($_SESSION['pwd_change_new']);
                        clean_redirect('password_updated');
                    } catch (Exception $e) {
                        error_log("Password update error: " . $e->getMessage());
                    }
                }
            }
        } else {
            $_SESSION['flash_error'] = "Incorrect Authenticator Code.";
        }
    } elseif ($step == 3 && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email_pin'])) {
        $pin = trim($_POST['email_pin'] ?? '');
        if (!empty($_SESSION['pwd_change_email_pin']) && $pin == $_SESSION['pwd_change_email_pin']) {
            // SUCCESS DB UPDATE
            if ($user_id > 0) {
                try {
                    $hashed_pwd = password_hash($_SESSION['pwd_change_new'], PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE $tableUsers SET password = ? WHERE id = ?")->execute([$hashed_pwd, $user_id]);
                } catch (Exception $e) {
                }
            }
            unset($_SESSION['pwd_change_new']);
            unset($_SESSION['pwd_change_email_pin']);
            clean_redirect('password_updated');
        } else {
            $_SESSION['flash_error'] = "Incorrect email code.";
        }
    }
}

// ---- FORGOT PASSWORD LOGIC (Logged In) ----
if ($action == 'forgot_password') {
    // 1. Skip Authenticator if disabled
    if ($step == 1 && !$is_auth_app_enabled) {
        if ($is_email_enabled) {
            $step = 2;
        } else {
            $_SESSION['forgot_pwd_pin_passed'] = true;
            $step = 3;
        }
    }

    // 2. Send email if we just arrived at Step 2 (and it's enabled)
    if ($step == 2 && $is_email_enabled && !isset($_SESSION['forgot_pwd_email_pin'])) {
        $_SESSION['forgot_pwd_email_pin'] = rand(100000, 999999);
        send_security_mail($email, $_SESSION['forgot_pwd_email_pin'], $mail_from);
    }

    if ($step == 1 && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['totp_token'])) {
        $totp = trim($_POST['totp_token'] ?? '');
        $g = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
        $secret = $user_config['auth_secret'] ?? '';

        // Added discrepancy of 4 to allow 2 minutes of tolerance for desync
        if ($g->checkCode($secret, $totp, 4)) {
            if ($is_email_enabled) {
                $_SESSION['forgot_pwd_email_pin'] = rand(100000, 999999);
                $sent = send_security_mail($email, $_SESSION['forgot_pwd_email_pin'], $mail_from);
                if ($sent) {
                    $step = 2;
                } else {
                    $_SESSION['flash_error'] = "Mail server failed to send email to: " . htmlspecialchars($email);
                }
            } else {
                $_SESSION['forgot_pwd_pin_passed'] = true;
                $step = 3;
            }
        } else {
            $_SESSION['flash_error'] = "Incorrect Authenticator Code.";
        }
    } elseif ($step == 2 && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email_pin'])) {
        $pin = trim($_POST['email_pin'] ?? '');
        if (!empty($_SESSION['forgot_pwd_email_pin']) && $pin == $_SESSION['forgot_pwd_email_pin']) {
            $_SESSION['forgot_pwd_pin_passed'] = true;
            $action = 'forgot_password';
            $step = 3;
        } else {
            $_SESSION['flash_error'] = "Incorrect email code.";
        }
    } elseif ($step == 3 && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password'])) {
        if (!isset($_SESSION['forgot_pwd_pin_passed'])) {
            exit("Access denied.");
        }

        $new_pass = trim($_POST['new_password'] ?? '');
        $confirm_pass = trim($_POST['confirm_password'] ?? '');

        if (empty($new_pass) || empty($confirm_pass)) {
            $_SESSION['flash_error'] = "All password fields are required.";
        } elseif ($new_pass !== $confirm_pass) {
            $_SESSION['flash_error'] = "Passwords do not match.";
        } else {
            $validation = validatePassword($new_pass);
            if ($validation !== true) {
                $_SESSION['flash_error'] = $validation;
            } else {
                // SUCCESS DB UPDATE
                if ($user_id > 0) {
                    try {
                        $hashed_pwd = password_hash($new_pass, PASSWORD_DEFAULT);
                        $pdo->prepare("UPDATE $tableUsers SET password = ? WHERE id = ?")->execute([$hashed_pwd, $user_id]);
                    } catch (Exception $e) {
                    }
                }
                unset($_SESSION['forgot_pwd_email_pin']);
                unset($_SESSION['forgot_pwd_pin_passed']);
                clean_redirect('password_recovered');
            }
        }
    }
}

// ==== MAIN LIST AND SETUP LOGIC ====
if ($action == 'setup_2fa') {
    if (class_exists('Sonata\GoogleAuthenticator\GoogleAuthenticator')) {
        $g = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
    } else {
        die("Google Authenticator library not loaded. Check paths.");
    }

    $secret = null;
    $test_result = null;
    $test_message = null;

    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        if (empty($user_config['auth_secret']) || isset($_GET['reset'])) {
            $secret = $g->generateSecret();
            if ($user_id > 0) {
                try {
                    $pdo->prepare("UPDATE " . DB_TABLE_TYPO . " SET auth_secret = ? WHERE id = ?")->execute([$secret, $user_id]);
                    $user_row['auth_secret'] = $secret;
                } catch (Exception $e) {
                }
            }
        } else {
            $secret = $user_row['auth_secret'] ?? '';
        }
    } else {
        $secret = $user_row['auth_secret'] ?? '';
    }

    $qr_code = \Sonata\GoogleAuthenticator\GoogleQrUrl::generate($email, $secret, 'typofx.ie');

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['test_token'])) {
        $test_token = trim($_POST['test_token']);
        if (empty($test_token) || strlen($test_token) !== 6 || !ctype_digit($test_token)) {
            $test_message = "Token must contain 6 digits.";
            $test_result = false;
        } else {
            if ($g->checkCode($secret, $test_token, 4)) {
                if ($user_id > 0) {
                    try {
                        // Simple UPDATE into config table
                        $sql = "UPDATE " . DB_TABLE_CONF_TYPO . " SET authenticator_app = 1 WHERE user_id = ?";
                        $pdo->prepare($sql)->execute([$user_id]);
                        // Auto-verify in session
                        if (!in_array('authenticator_app', $_SESSION['temp_2fa_passed'])) {
                            $_SESSION['temp_2fa_passed'][] = 'authenticator_app';
                        }
                    } catch (Exception $e) {
                    }
                }
                clean_redirect('1');
            } else {
                $test_message = "Invalid token.";
                $test_result = false;
            }
        }
    }
}

// ---- TOGGLE SECURITY SETTINGS (CSP COMPLIANT) ----
if ($action == 'toggle_security') {
    $type = $_POST['type'] ?? $_GET['type'] ?? '';
    $val = isset($_POST['val']) ? (int) $_POST['val'] : (isset($_GET['val']) ? (int) $_GET['val'] : null);

    if ($val === null) {
        $current = $user_config[$type] ?? 0;
        $val = ($current == 1) ? 0 : 1;
    }

    $valid_mapping = [
        'authenticator_app' => 'authenticator_app',
        'email' => 'email',
        'biometrics' => 'biometrics',
        'mobile_phone' => 'mobile_phone',
        'telegram' => 'telegram',
        'token' => 'token'
    ];

    if (array_key_exists($type, $valid_mapping) && $user_id > 0) {
        $db_column = $valid_mapping[$type];

        // 1. Requirement: ENABLE (Redirect to setup)
        if ($val == 1) {
            if ($type == 'authenticator_app') {
                $_SESSION['last_security_action'] = 'setup_2fa';
                redirect('auth/security');
            } elseif ($type == 'email') {
                $_SESSION['last_security_action'] = 'setup_email_2fa';
                redirect('auth/security');
            }
        } else {
            // NEW: Requirement: DISABLE (Redirect to disable confirmation)
            if ($type == 'authenticator_app' || $type == 'email') {
                $_SESSION['last_security_action'] = 'disable_layer';
                $_SESSION['disable_type'] = $type; // Store type for disable flow
                redirect('auth/security');
            }
        }

        try {
            // Simple UPDATE into config table
            $sql = "UPDATE " . DB_TABLE_CONF_TYPO . " SET `$db_column` = ? WHERE user_id = ?";
            $pdo->prepare($sql)->execute([$val, $user_id]);
        } catch (Exception $e) {
        }
    }

    clean_redirect();
}

// SETUP EMAIL 2FA LOGIC
if ($action == 'setup_email_2fa') {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['test_email_pin'])) {
        $test_pin = trim($_POST['test_email_pin']);
        if ($test_pin == ($_SESSION['setup_email_pin'] ?? '')) {
            if ($user_id > 0) {
                try {
                    // Simple UPDATE into config table
                    $sql = "UPDATE " . DB_TABLE_CONF_TYPO . " SET email = 1 WHERE user_id = ?";
                    $pdo->prepare($sql)->execute([$user_id]);
                    // Auto-verify in session
                    if (!in_array('email', $_SESSION['temp_2fa_passed'])) {
                        $_SESSION['temp_2fa_passed'][] = 'email';
                    }
                } catch (Exception $e) {
                }
            }
            unset($_SESSION['setup_email_pin']);
            unset($_SESSION['setup_email_pin_sent']);
            clean_redirect('1');
        } else {
            $_SESSION['flash_error'] = "Incorrect Email Code.";
        }
    } else {
        if (isset($_GET['resend'])) {
            unset($_SESSION['setup_email_pin_sent']);
        }
        if (!isset($_SESSION['setup_email_pin_sent'])) {
            $_SESSION['setup_email_pin'] = rand(100000, 999999);
            send_security_mail($email, $_SESSION['setup_email_pin'], $mail_from);
            $_SESSION['setup_email_pin_sent'] = true;
            if (isset($_GET['resend'])) {
                redirect('auth/security?action=setup_email_2fa');
            }
        }
    }
}

// DISABLE 2FA LAYER LOGIC
if ($action == 'disable_layer') {
    $type = $_POST['type'] ?? $_GET['type'] ?? $_SESSION['disable_type'] ?? '';

    if ($type !== 'authenticator_app' && $type !== 'email') {
        clean_redirect();
    }

    if ($type === 'email' && !isset($_SESSION['disable_email_pin_sent'])) {
        $_SESSION['disable_email_pin'] = rand(100000, 999999);
        send_security_mail($email, $_SESSION['disable_email_pin'], $mail_from);
        $_SESSION['disable_email_pin_sent'] = true;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if ($type === 'authenticator_app') {
            $totp = trim($_POST['totp_token'] ?? '');
            $g = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
            $secret = $user_config['auth_secret'] ?? '';

            if ($g->checkCode($secret, $totp, 4)) {
                if ($user_id > 0) {
                    try {
                        $pdo->prepare("UPDATE " . DB_TABLE_CONF_TYPO . " SET authenticator_app = 0 WHERE user_id = ?")->execute([$user_id]);
                        // Remove from passed methods if disabled
                        if (($key = array_search('authenticator_app', $_SESSION['temp_2fa_passed'])) !== false) {
                            unset($_SESSION['temp_2fa_passed'][$key]);
                        }
                    } catch (Exception $e) {}
                }
                clean_redirect('1');
            } else {
                $_SESSION['flash_error'] = "Incorrect Authenticator Code.";
            }
        } elseif ($type === 'email') {
            $pin = trim($_POST['email_pin'] ?? '');
            if ($pin == ($_SESSION['disable_email_pin'] ?? '')) {
                if ($user_id > 0) {
                    try {
                        $pdo->prepare("UPDATE " . DB_TABLE_CONF_TYPO . " SET email = 0 WHERE user_id = ?")->execute([$user_id]);
                        // Remove from passed methods if disabled
                        if (($key = array_search('email', $_SESSION['temp_2fa_passed'])) !== false) {
                            unset($_SESSION['temp_2fa_passed'][$key]);
                        }
                    } catch (Exception $e) {}
                }
                unset($_SESSION['disable_email_pin']);
                unset($_SESSION['disable_email_pin_sent']);
                clean_redirect('1');
            } else {
                $_SESSION['flash_error'] = "Incorrect Email Code.";
            }
        }
    }
}


$is_email_enabled = (int)($user_row['email_mfa'] ?? 0) === 1;

$dashboard_items = [
    [
        'id' => 'biometrics',
        'icon' => 'fa-solid fa-user-shield',
        'title' => 'Passkeys (Biometrics)',
        'description' => 'Protect your account and withdrawals with Passkeys and/or security keys.',
        'status' => (int)($user_row['biometrics'] ?? 0) === 1,
        'enabled' => false
    ],
    [
        'id' => 'authenticator_app',
        'icon' => 'fa-solid fa-asterisk',
        'title' => 'Authenticator App',
        'description' => 'Use Binance/Google Authenticator to protect your account and transactions.',
        'status' => (int)($user_row['authenticator_app'] ?? 0) === 1,
        'enabled' => true
    ],
    [
        'id' => 'email',
        'icon' => 'fa-regular fa-envelope',
        'title' => 'Email',
        'description' => 'Use your email to protect your account and transactions.',
        'status' => (int)($user_row['email_mfa'] ?? 0) === 1,
        'enabled' => true
    ],
    [
        'id' => 'mobile_phone',
        'icon' => 'fa-solid fa-phone',
        'title' => 'Phone Number',
        'description' => 'Use your phone number to protect your account and transactions.',
        'status' => (int)($user_row['mobile_phone'] ?? 0) === 1,
        'enabled' => false
    ],
    [
        'id' => 'telegram',
        'icon' => 'fa-brands fa-telegram',
        'title' => 'Telegram Notification',
        'description' => 'Receive security alerts and login approvals instantly via Telegram Bot.',
        'status' => (int)($user_row['telegram'] ?? 0) === 1,
        'enabled' => false
    ],
    [
        'id' => 'token',
        'icon' => 'fa-solid fa-key',
        'title' => 'Security Token',
        'description' => 'Register a physical USB or NFC security token for high-level account protection.',
        'status' => (int)($user_row['token'] ?? 0) === 1,
        'enabled' => false
    ],
    [
        'id' => 'password',
        'icon' => 'fa-solid fa-lock',
        'title' => 'Login Password',
        'description' => 'Login password is used to log in to your account.',
        'status' => true,
        'enabled' => true
    ]
];


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication (2FA) | typofxCX</title>
    <link rel="stylesheet" href="<?php echo url('assets/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/auth.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/fontawesome.css'); ?>">
</head>
<body data-theme="dark">
    
    <div class="main-wrapper" style="min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 20px;">
        


        <?php if ($action == 'setup_2fa'): ?>
                <!-- Google Authenticator Setup View -->
                <div class="setup-card">
                    <div class="setup-icon-box">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h2 class="setup-title">Authenticator App</h2>
                    <p class="setup-desc">Protect your account with a second layer of security using Google or Binance Authenticator.</p>

                    <?php displayFlashMessages(); ?>

                    <div style="margin-bottom: 30px;">
                        <p style="font-size: 0.85rem; margin-bottom: 15px; color: #888; text-transform: uppercase; letter-spacing: 1px;">Step 1: Scan this QR Code</p>
                        <div style="background: white; padding: 12px; display: inline-block; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                            <img src="<?php echo htmlspecialchars($qr_code); ?>" alt="QR Code" style="display: block; width: 180px; height: 180px;">
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <p style="font-size: 0.85rem; margin-bottom: 10px; color: #888; text-transform: uppercase; letter-spacing: 1px;">Step 2: Backup Secret Key</p>
                        <code style="display: block; background: rgba(255,255,255,0.05); padding: 12px; border-radius: 8px; font-size: 1.1rem; letter-spacing: 2px; color: #4CAF50; border: 1px dashed rgba(76, 175, 80, 0.3); font-family: monospace;"><?php echo htmlspecialchars($secret); ?></code>
                    </div>

                    <form method="POST" action="<?php echo url('auth/security'); ?>" class="setup-input-group">
                        <input type="hidden" name="action" value="setup_2fa">
                        <label for="test_token">Step 3: Enter 6-digit Code</label>
                        <input type="text" id="test_token" name="test_token" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus class="setup-field">
                        <button type="submit" class="btn-setup-confirm" style="margin-top: 20px;">Validate and Activate</button>
                    </form>

                    <?php if ($test_result !== null): ?>
                            <div style="margin-top: 20px; padding: 12px; border-radius: 8px; background: <?php echo $test_result ? 'rgba(76,175,80,0.1)' : 'rgba(244,67,54,0.1)'; ?>; color: <?php echo $test_result ? '#4CAF50' : '#f44336'; ?>; font-weight: 600; border: 1px solid <?php echo $test_result ? 'rgba(76,175,80,0.2)' : 'rgba(244,67,54,0.2)'; ?>;">
                                <?php echo htmlspecialchars($test_message); ?>
                            </div>
                    <?php endif; ?>

                    <a href="<?php echo url('auth/security', ['action' => 'dashboard']); ?>" class="btn-setup-cancel">Back to Security Panel</a>
                </div>

        <?php elseif ($action == 'setup_email_2fa'): ?>
                <!-- Activate Email 2FA Flow -->
                <div class="setup-card">
                    <div class="setup-icon-box" style="background: rgba(33, 150, 243, 0.1); color: #2196F3; border-color: rgba(33, 150, 243, 0.2); box-shadow: 0 0 20px rgba(33, 150, 243, 0.15);">
                        <i class="fa-regular fa-envelope"></i>
                    </div>
                    <h2 class="setup-title">Email Verification</h2>
                    <p class="setup-desc">We've sent a 6-digit PIN to <b><?php echo htmlspecialchars(maskEmail($email)); ?></b>.<br>Enter it below to verify and activate protection.</p>
                
                    <?php displayFlashMessages(); ?>
                
                    <form method="POST" action="<?php echo url('auth/security'); ?>" class="setup-input-group">
                        <input type="hidden" name="action" value="setup_email_2fa">
                        <label for="test_email_pin">Enter 6-digit PIN</label>
                        <input type="text" id="test_email_pin" name="test_email_pin" autocomplete="one-time-code" inputmode="numeric" required placeholder="000000" maxlength="6" autofocus class="setup-field">
                        <button type="submit" class="btn-setup-confirm" style="background: rgba(33, 150, 243, 0.15); border: 1px solid rgba(33, 150, 243, 0.2); color: #90caf9; margin-top: 20px; box-shadow: none;">Verify and Activate</button>
                    </form>
                    <div style="margin-top: 15px; text-align: center;">
                        <form method="POST" action="<?php echo url('auth/security'); ?>">
                            <input type="hidden" name="action" value="setup_email_2fa">
                            <input type="hidden" name="resend" value="1">
                            <button type="submit" style="background:none; border:none; padding:0; color: #888; font-size: 0.85rem; text-decoration: none; border-bottom: 1px solid #444; cursor:pointer;">Didn't receive code? Resend</button>
                        </form>
                    </div>
                
                    <a href="<?php echo url('auth/security', ['action' => 'dashboard']); ?>" class="btn-setup-cancel">Cancel</a>
                </div>

        <?php elseif ($action == 'change_email'): ?>
                <!-- Email Change Flow -->
                <div style="text-align: center; margin-top:20px;">
                    <h2>Change Email Address</h2>
                
                    <?php if ($step == 1): ?>
                            <p>Changing your email is a sensitive action. To proceed, click below to receive a security code sent to your current email.</p>
                            <form method="POST" action="<?php echo url('auth/security'); ?>" style="max-width: 400px; margin: 20px auto; text-align:center;">
                                <input type="hidden" name="action" value="change_email">
                                <input type="hidden" name="step" value="1">
                                <button type="submit" class="btn-signup" style="width: 100%; margin-top:10px; background: #0b0e11; color: #fff;">Send Code & Start Transfer</button>
                            </form>

                    <?php elseif ($step == 2): ?>
                            <p>Enter the Double Challenge codes and your new destination email.</p>
                            <form method="POST" action="<?php echo url('auth/security'); ?>" style="max-width: 400px; margin: 20px auto; text-align:left;">
                                <input type="hidden" name="action" value="change_email">
                                <input type="hidden" name="step" value="2">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label>PIN Code sent to Current Email</label>
                                    <input type="text" name="old_email_pin" autocomplete="one-time-code" inputmode="numeric" required placeholder="000000" maxlength="6" style="width: 100%; padding: 10px; font-size:1.2rem; text-align:center; letter-spacing:5px;">
                                </div>
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label>Authenticator App Code</label>
                                    <input type="text" name="totp_token" autocomplete="one-time-code" inputmode="numeric" required placeholder="000000" maxlength="6" style="width: 100%; padding: 10px; font-size:1.2rem; text-align:center; letter-spacing:5px;">
                                </div>
                                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #444;">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label>New Email Address</label>
                                    <input type="email" name="new_email" placeholder="new@domain.com" required style="width: 100%; padding: 10px; font-size:1rem;">
                                </div>
                                <button type="submit" class="btn-signup" style="width: 100%; margin-top:10px; background: #0b0e11; color: #fff;">Verify Transfer</button>
                            </form>

                    <?php elseif ($step == 3): ?>
                            <p>Almost there! We just sent a validation code to your <b>New Email Address</b>.</p>
                            <form method="POST" action="<?php echo url('auth/security'); ?>" style="max-width: 400px; margin: 20px auto; text-align:left;">
                                <input type="hidden" name="action" value="change_email">
                                <input type="hidden" name="step" value="3">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label>PIN Code sent to New Email</label>
                                    <input type="text" name="new_email_pin" autocomplete="one-time-code" inputmode="numeric" required placeholder="000000" maxlength="6" style="width: 100%; padding: 10px; font-size:1.2rem; text-align:center; letter-spacing:5px;">
                                </div>
                                <button type="submit" class="btn-signup" style="width: 100%; margin-top:10px; background: #0b0e11; color: #fff;">Confirm New Email</button>
                            </form>
                    <?php endif; ?>
                
                    <div style="margin-top: 30px;">
                        <a href="<?php echo url('auth/security', ['action' => 'dashboard']); ?>" class="btn-signup" style="width:auto; padding: 5px 15px;">Cancel</a>
                    </div>
                </div>

        <?php elseif ($action == 'change_password'): ?>
                <!-- Normal Password Change Flow -->
                <div class="setup-card">
                    <div class="setup-icon-box" style="background: rgba(156, 39, 176, 0.1); color: #9c27b0; border-color: rgba(156, 39, 176, 0.2); box-shadow: 0 0 20px rgba(156, 39, 176, 0.15);">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <h2 class="setup-title">Change Password</h2>
                
                    <?php displayFlashMessages(); ?>
                
                    <?php if ($step == 1): ?>
                            <p class="setup-desc">Ensure your account is using a long, random password to stay secure.</p>
                            <form method="POST" action="<?php echo url('auth/security'); ?>" class="setup-input-group">
                                <input type="hidden" name="action" value="change_password">
                                <input type="hidden" name="step" value="1">
                                <div style="margin-bottom: 15px;">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" required class="setup-field" style="text-align: left;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label for="new_password">New Password (min 12 chars)</label>
                                    <input type="password" id="new_password" name="new_password" required minlength="12" maxlength="128" class="setup-field" style="text-align: left;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label for="confirm_password_change">Confirm New Password</label>
                                    <input type="password" id="confirm_password_change" name="confirm_password" required minlength="12" maxlength="128" class="setup-field" style="text-align: left;">
                                </div>
                                <button type="submit" class="btn-setup-confirm" style="background: rgba(156, 39, 176, 0.15); border: 1px solid rgba(156, 39, 176, 0.2); color: #ce93d8; box-shadow: none;">Continue</button>
                            </form>
                            <div style="margin-top: 15px; font-size: 0.85rem; text-align: center;">
                                <form method="POST" action="<?php echo url('auth/security'); ?>">
                                    <input type="hidden" name="action" value="forgot_password">
                                    <input type="hidden" name="step" value="1">
                                    <button type="submit" style="background:none; border:none; padding:0; color:#aaa; text-decoration:none; border-bottom: 1px dashed #444; cursor:pointer;">I forgot my password</button>
                                </form>
                            </div>

                    <?php elseif ($step == 2): ?>
                            <p class="setup-desc">Verify ownership of the account using Authenticator.</p>
                            <form method="POST" action="<?php echo url('auth/security'); ?>" class="setup-input-group">
                                <input type="hidden" name="action" value="change_password">
                                <input type="hidden" name="step" value="2">
                                <label>Authenticator Code</label>
                                <input type="text" name="totp_token" required placeholder="000000" maxlength="6" autofocus class="setup-field">
                                <button type="submit" class="btn-setup-confirm" style="background: rgba(156, 39, 176, 0.15); border: 1px solid rgba(156, 39, 176, 0.2); color: #ce93d8; margin-top:20px; box-shadow: none;">Authorize</button>
                            </form>

                    <?php elseif ($step == 3): ?>
                            <p class="setup-desc">Enter the PIN sent to your email to confirm.</p>
                            <form method="POST" action="<?php echo url('auth/security'); ?>" class="setup-input-group">
                                <input type="hidden" name="action" value="change_password">
                                <input type="hidden" name="step" value="3">
                                <label>Email PIN Code</label>
                                <input type="text" name="email_pin" autocomplete="one-time-code" inputmode="numeric" required placeholder="000000" maxlength="6" autofocus class="setup-field">
                                <button type="submit" class="btn-setup-confirm" style="background: #9c27b0; margin-top:20px;">Confirm & Change</button>
                            </form>
                    <?php endif; ?>
                
                    <a href="<?php echo url('auth/security', ['action' => 'dashboard']); ?>" class="btn-setup-cancel">Cancel</a>
                </div>

        <?php elseif ($action == 'forgot_password'): ?>
                <!-- Forgot Password Flow (Logged In) -->
                <div class="setup-card">
                    <h2 class="auth-title">Safety Setup</h2>
                    
                    <?php displayFlashMessages(); ?>

                    <div class="security-list">
                    <div class="setup-icon-box" style="background: rgba(255, 152, 0, 0.1); color: #ff9800; border-color: rgba(255, 152, 0, 0.2); box-shadow: 0 0 20px rgba(255, 152, 0, 0.15);">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <h2 class="setup-title">Recover Password</h2>

                    <?php if ($step == 1): ?>
                            <p class="setup-desc">Prove ownership via Authenticator App and Email confirmation.</p>
                            <form method="POST" action="<?php echo url('auth/security'); ?>" class="setup-input-group">
                                <input type="hidden" name="action" value="forgot_password">
                                <input type="hidden" name="step" value="1">
                                <label>Authenticator App Code</label>
                                <input type="text" name="totp_token" required placeholder="000000" maxlength="6" autofocus class="setup-field">
                                <button type="submit" class="btn-setup-confirm" style="background: rgba(255, 152, 0, 0.15); border: 1px solid rgba(255, 152, 0, 0.2); color: #ffcc80; margin-top: 20px; box-shadow: none;">Verify TOTP</button>
                            </form>

                    <?php elseif ($step == 2): ?>
                            <p class="setup-desc">Enter the PIN sent to your registered email.</p>
                            <form method="POST" action="<?php echo url('auth/security'); ?>" class="setup-input-group">
                                <input type="hidden" name="action" value="forgot_password">
                                <input type="hidden" name="step" value="2">
                                <label>Email PIN Code</label>
                                <input type="text" name="email_pin" autocomplete="one-time-code" inputmode="numeric" required placeholder="000000" maxlength="6" autofocus class="setup-field">
                                <button type="submit" class="btn-setup-confirm" style="background: #ff9800; margin-top: 20px;">Verify Email</button>
                            </form>

                    <?php elseif ($step == 3): ?>
                            <p class="setup-desc">Verification successful. Define a new strong password.</p>
                            <form method="POST" action="<?php echo url('auth/security'); ?>" class="setup-input-group">
                                <input type="hidden" name="action" value="forgot_password">
                                <input type="hidden" name="step" value="3">
                                <div style="margin-bottom: 15px;">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" required minlength="12" maxlength="128" class="setup-field" style="text-align: left;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" required minlength="12" maxlength="128" class="setup-field" style="text-align: left;">
                                </div>
                                <button type="submit" class="btn-setup-confirm" style="background: #ff9800; margin-top: 20px;">Set New Password</button>
                            </form>
                    <?php endif; ?>
                
                    <a href="<?php echo url('auth/security', ['action' => 'dashboard']); ?>" class="btn-setup-cancel">Cancel</a>
                </div>

        <?php elseif ($action == 'disable_layer'): ?>
                <!-- Disable Layer Flow -->
                <div class="setup-card">
                    <div class="setup-icon-box" style="background: rgba(244, 67, 54, 0.1); color: #f44336; border-color: rgba(244, 67, 54, 0.2); box-shadow: 0 0 20px rgba(244, 67, 54, 0.15);">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h2 class="setup-title">Disable <?php echo $type === 'authenticator_app' ? 'Authenticator' : 'Email 2FA'; ?></h2>
                    <p class="setup-desc">To disable this security layer, please verify your identity.</p>
                
                    <?php display_flash_message(); ?>
                
                    <form method="POST" action="<?php echo url('auth/security'); ?>" class="setup-input-group">
                        <input type="hidden" name="action" value="disable_layer">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                    
                        <?php if ($type === 'authenticator_app'): ?>
                            <label>Authenticator Code</label>
                            <input type="text" name="totp_token" required placeholder="000000" maxlength="6" autofocus class="setup-field">
                        <?php elseif ($type === 'email'): ?>
                            <label>Email PIN Code sent to <?php echo htmlspecialchars(maskEmail($email)); ?></label>
                            <input type="text" name="email_pin" autocomplete="one-time-code" inputmode="numeric" required placeholder="000000" maxlength="6" autofocus class="setup-field">
                        <?php endif; ?>
                    
                        <button type="submit" class="btn-setup-confirm" style="background: rgba(244, 67, 54, 0.15); border: 1px solid rgba(244, 67, 54, 0.2); color: #ef5350; margin-top: 20px; box-shadow: none;">Verify & Disable</button>
                    </form>
                
                    <a href="<?php echo url('auth/security', ['action' => 'dashboard']); ?>" class="btn-setup-cancel">Cancel</a>
                </div>

        <?php else: ?>
                <!-- Dashboard View -->
                <div class="glass-card wide" style="margin: 0 auto;">
                    <div class="brand">
                        <h1>typofx.ie</h1>
                        <p>Two-Factor Authentication (2FA)</p>
                    </div>

                    <?php display_flash_message(); ?>

                    <div class="security-list">
                    <?php foreach ($dashboard_items as $item): ?>
                        <?php
                        $disabled_class = !$item['enabled'] ? ' disabled-item' : '';
                        $is_on = $item['status'];
                        $checked_attr = $is_on ? 'checked' : '';
                        $toggle_val = $is_on ? 0 : 1;
                        $toggle_html = '';

                        if ($item['id'] !== 'password') {
                            if ($item['enabled']) {
                                $toggle_html = '
                            <form method="POST" action="' . url('auth/security') . '" class="toggle-form" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_security">
                                <input type="hidden" name="type" value="' . $item['id'] . '">
                                <input type="hidden" name="val" value="' . $toggle_val . '">
                                <label class="switch">
                                    <input type="checkbox" ' . $checked_attr . ' class="auto-submit-checkbox">
                                    <span class="slider round"></span>
                                </label>
                            </form>';
                            } else {
                                $toggle_html = '
                            <label class="switch">
                                <input type="checkbox" disabled ' . $checked_attr . '>
                                <span class="slider round"></span>
                            </label>';
                            }
                        } else {
                            $toggle_html = '
                            <form method="POST" action="' . url('auth/security') . '" style="display: inline;">
                                <input type="hidden" name="action" value="change_password">
                                <button type="submit" class="btn-manage" style="border:none; cursor:pointer;">Manage</button>
                            </form>';
                        }

                        $badge_class = $is_on ? 'badge-on' : 'badge-off';
                        $badge_text = $is_on ? 'Enabled' : 'Disabled';
                        $status_html = '<span class="status-badge ' . $badge_class . '">' . $badge_text . '</span>';
                        ?>
                        <div class="security-item<?php echo $disabled_class; ?>">
                            <div class="sec-icon">
                                <i class="<?php echo $item['icon']; ?>"></i>
                            </div>
                            <div class="sec-info">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p><?php echo $item['description']; ?></p>
                            </div>
                            <div class="sec-status">
                                <?php echo ($item['id'] !== 'password') ? $status_html : ''; ?>
                            </div>
                            <div class="sec-action" style="display: flex; align-items: center;">
                                <?php echo $toggle_html; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="<?php echo url('auth/menu'); ?>" class="btn-signup" style="width: auto; padding: 10px 25px; text-decoration: none; display: inline-block; background: #e0e0e0; color: #333; border: none;"><i class="fa-solid fa-arrow-left"></i> Back to Menu</a>
                </div>

        <?php endif; ?>
    </div>
    
    <script>
        // Automatically submit checkboxes for toggling security options
        document.querySelectorAll('.auto-submit-checkbox').forEach(input => {
            input.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });

        
        if (window.history.replaceState && window.location.search !== '') {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
