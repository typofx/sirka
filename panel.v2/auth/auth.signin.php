<?php 
$currentDir = __DIR__;
while ($currentDir !== dirname($currentDir) && !file_exists($currentDir . '/%/config.php')) {
    $currentDir = dirname($currentDir);
}
require_once $currentDir . '/%/config.php';
?>
<?php require_once 'auth.validate.php'; ?>

<?php

// Handle Logout/Reset via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
    unverify_ip();
    session_destroy();
    redirect('https://typofx.ie');
}

// Verify that the client IP has passed the CAPTCHA
if (!is_ip_verified()) {
    $_SESSION['captcha_redirect'] = 'auth/signin';
    redirect('auth/captcha');
}

$emailError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && !isset($_POST['action'])) {
    if (!is_ip_verified()) {
        $_SESSION['captcha_redirect'] = 'auth/signin';
        redirect('auth/captcha');
    }
    $email = $_POST['email'];
    $password = $_POST['password'] ?? '';
    $validationResult = validateEmail($email, 'auth.email_rules.json');

    if ($validationResult !== true) {
        $emailError = $validationResult;
    } elseif (empty($password)) {
        $emailError = "Password is required.";
    } else {
        try {
            // Check if user exists and password matches
            $table = DB_TABLE_TYPO;
            $stmt = $pdo->prepare("SELECT password FROM $table WHERE email = ?");
            $stmt->execute([$email]);
            $db_password = $stmt->fetchColumn();

            if ($db_password === false) {
                $emailError = "Account not found.";
            } else {
                $authenticated = false;
                
                // 1. Check if it matches via password_verify 
                if (password_verify($password, $db_password)) {
                    $authenticated = true;
                } 
                // 2. Legacy Check: Check if it matches as plain text
                elseif ($password === $db_password) {
                    $authenticated = true;
                    // Migrate to hash immediately
                    try {
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = $pdo->prepare("UPDATE $table SET password = ? WHERE email = ?");
                        $update_stmt->execute([$new_hash, $email]);
                    } catch (Exception $e) {
                        // Log error but allow login to continue
                        error_log("Password migration error for $email: " . $e->getMessage());
                    }
                }

                if ($authenticated) {
                    session_regenerate_id(true);
                    unset($_SESSION['verification_code']);
                    unset($_SESSION['temp_2fa_passed']); 
                    $_SESSION['temp_email'] = $email;
                    $_SESSION['session_start_time'] = time();
                    redirect('auth/verify');
                } else {
                    $emailError = "Incorrect password.";
                }
            }
        } catch (Exception $e) {
            $emailError = log_system_error($e);
        }
    }
}

$emailErrorHtml = !empty($emailError) ? '<div class="error-message mb-20">' . $emailError . '</div>' : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | typofxCX</title>
    <link rel="stylesheet" href="<?php echo url('assets/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/auth.css'); ?>">
</head>

<body data-theme="dark">
    <div class="register-container">
        <div class="glass-card">
            <div class="brand">
                <h1>typofx.ie</h1>
            </div>

            <?php if(function_exists('display_flash_message')) display_flash_message(); ?>

            <?php echo $emailErrorHtml; ?>

            <form action="<?php echo url('auth/signin'); ?>" method="POST" id="signin-form">


                <div class="form-group">
                    <label for="email">Enter your email</label>
                    <input type="email" name="email" id="email" placeholder="email@example.com" required autofocus autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="••••••••••••" maxlength="128" required autocomplete="current-password">
                </div>

                <button type="submit" id="signin-btn" class="btn-signup">Sign In</button>
            </form>

            <div style="text-align: center; margin-top: 2rem; font-size: 0.9rem; color: #888;">
            </div>

        </div>
    </div>
</body>

</html>
