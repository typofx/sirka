<?php 
$currentDir = __DIR__;
while ($currentDir !== dirname($currentDir) && !file_exists($currentDir . '/%/config.php')) {
    $currentDir = dirname($currentDir);
}
require_once $currentDir . '/%/config.php';
?>

<?php
require_once (BASE_GA_PATH . 'FixedBitNotation.php');
require_once (BASE_GA_PATH . 'GoogleAuthenticatorInterface.php');
require_once (BASE_GA_PATH . 'GoogleAuthenticator.php');

// --- ACTION: RESET ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    unset($_SESSION['temp_email']);
    unset($_SESSION['verification_code']);
    unset($_SESSION['2fa_method']);
    unset($_SESSION['email_verified']);
    redirect('auth/signin');
}

// --- SECURITY: CAPTCHA ENFORCEMENT ---
if (!is_ip_verified()) {
    redirect('auth/signin');
}

$email = $_SESSION['temp_email'] ?? '';
if (!$email) {
    redirect('auth/signin');
}

$error = '';
$chosen_method = $_SESSION['2fa_method'] ?? null;

// --- MFA STATE MANAGEMENT ---
if (!isset($_SESSION['temp_2fa_passed'])) {
    $_SESSION['temp_2fa_passed'] = [];
}

// ============================================================
// STEP 1: RESOLVE PENDING 2FA METHODS (SEQUENTIAL)
// ============================================================
$user_id = null;
$enabled_methods = [];
$otp_secret = null;

try {
    $tableUsers = DB_TABLE_TYPO;
    $tableConfig = DB_TABLE_CONF_TYPO;

    // Fetch user ID and MFA settings using accurate table and column names
    $stmt = $pdo->prepare("SELECT u.id, u.auth_secret, c.email as email_mfa, c.authenticator_app 
                           FROM $tableUsers u 
                           LEFT JOIN $tableConfig c ON u.id = c.user_id 
                           WHERE u.email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $user_id = $row['id'];
        $otp_secret = $row['auth_secret'];
        
        // Map fields from the configuration table to enabled methods
        if (isset($row['email_mfa']) && (int)$row['email_mfa'] === 1) {
            $enabled_methods[] = 'email';
        }
        if (isset($row['authenticator_app']) && (int)$row['authenticator_app'] === 1) {
            $enabled_methods[] = 'authenticator_app';
        }
    }
} catch (Exception $e) {
    $error = "Verification failed. Please try again later.";
    error_log("Authenticator login fetch error: " . $e->getMessage());
}

// If no MFA found, the flow will skip directly to dashboard (handled by loop below)

// Identify the next pending method
$chosen_method = null;
foreach ($enabled_methods as $m) {
    if (!in_array($m, $_SESSION['temp_2fa_passed'])) {
        $chosen_method = $m;
        break;
    }
}

// If all passed -> SUCCESS
if (!$chosen_method) {
    $_SESSION['registered_email'] = $email;
    if ($user_id > 0) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_logged_in_panel'] = true; // Required by is_logged.php
        redirect('auth/menu');
    } else {
        // Fallback to home if something is wrong with the session
        redirect('auth/signin');
    }
}

// --- EMAIL DISPATCH (If current step is email and code not yet sent) ---
if ($chosen_method === 'email') {
    if (!isset($_SESSION['verification_code']) || isset($_POST['resend'])) {
        $code = rand(100000, 999999);
        $_SESSION['verification_code'] = $code;
        
        $mail_from = defined('MAIL_FROM') ? MAIL_FROM : 'no-reply@plata.ie';
        $subject = "Code - Security Verification - Typo FX";
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: " . $mail_from . "\r\n";
        $msg = "<html><body style='font-family: sans-serif; background: #fff; padding: 20px;'><div style='max-width: 400px; margin: 0 auto; background: #f9f9f9; padding: 30px; border-radius: 8px; border: 1px solid #ddd; text-align: center;'><h2 style='color: #000;'>Security Code</h2><p>Your authentication code is:</p><div style='font-size: 32px; font-weight: bold; letter-spacing: 5px; margin: 30px 0; background: #fff; border: 1px solid #ddd; padding: 15px;'>$code</div></div></body></html>";
        mail($email, $subject, $msg, $headers);
        if (isset($_POST['resend'])) {
            redirect('auth/authenticator');
        }
    }
}

// ============================================================
// STEP 2: HANDLE VERIFICATION POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($chosen_method === 'authenticator_app' && isset($_POST['totp_code'])) {
        $code = trim($_POST['totp_code']);
        $totp_secret = $otp_secret;
        
        if ($totp_secret) {
            $ga = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
            if ($ga->checkCode($totp_secret, $code, 2)) {
                $_SESSION['temp_2fa_passed'][] = 'authenticator_app';
                redirect('auth/authenticator');
            } else {
                $error = "Incorrect Authenticator Code.";
            }
        } else {
            $error = "Authenticator not configured.";
        }
    } 
    elseif ($chosen_method === 'email' && isset($_POST['email_code'])) {
        $code = trim($_POST['email_code']);
        if ($code == ($_SESSION['verification_code'] ?? '')) {
            $_SESSION['temp_2fa_passed'][] = 'email';
            redirect('auth/authenticator');
        } else {
            $error = "Invalid Email security code.";
        }
    }
}

// --- UI PREPARATION ---
$titles = [
    'email' => ['title' => 'Verify Email', 'sub' => 'Code sent to your email address.', 'input' => 'email_code'],
    'authenticator_app' => ['title' => 'Authenticator App', 'sub' => 'Enter 6-digit code from your app.', 'input' => 'totp_code'],
    'biometrics' => ['title' => 'Biometric Identity', 'sub' => 'Please verify your identity via Biometrics.', 'input' => 'bio_token'],
    'token' => ['title' => 'Security Token', 'sub' => 'Enter the code from your security token.', 'input' => 'token_code'],
    'telegram' => ['title' => 'Telegram Verification', 'sub' => 'Code sent to your Telegram account.', 'input' => 'telegram_code'],
    'mobile_phone' => ['title' => 'SMS Verification', 'sub' => 'Code sent to your mobile phone.', 'input' => 'sms_code']
];

$ui = $titles[$chosen_method] ?? $titles['email'];
$html_title = $ui['title'];
$html_subtitle = $ui['sub'];
$input_name = $ui['input'];

$errorHtml = '';
if ($error) {
    $errorHtml = '<div class="error-message" style="color: #ff6b6b; text-align: center; margin-bottom: 20px; font-weight: 600;">' . htmlspecialchars($error) . '</div>';
}

$authenticatorUrl = url('auth/authenticator');
$styleUrl = url('assets/style.css');
$authCssUrl = url('assets/css/auth.css');

$resendHtml = '';
if ($chosen_method === 'email') {
    $resendHtml = <<<HTML
                    <div style="margin-bottom: 0.5rem;">Didn't receive the code? 
                        <form method="POST" action="{$authenticatorUrl}" style="display:inline;">
                            <button type="submit" name="resend" value="1" style="background:none; border:none; padding:0; cursor:pointer; font:inherit; color:#fff; text-decoration: underline;">Resend Email</button>
                        </form>
                    </div>
HTML;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($html_title); ?> | Typo FX</title>
    <link rel="stylesheet" href="<?php echo $styleUrl; ?>"><link rel="stylesheet" href="<?php echo $authCssUrl; ?>">
</head>
<body data-theme="dark">
    <div class="register-container">
        <div class="glass-card">
            <div class="brand">
                <h1><?php echo htmlspecialchars($html_title); ?></h1>
                <p><?php echo htmlspecialchars($html_subtitle); ?></p>
            </div>
            
            <?php echo $errorHtml; ?>
            
            <form method="POST" action="<?php echo $authenticatorUrl; ?>" autocomplete="off">
                <div class="form-group">
                    <label for="auth_code">Verification Code</label>
                    <input 
                        type="text" 
                        name="<?php echo $input_name; ?>" 
                        id="auth_code" 
                        placeholder="000000" 
                        maxlength="6"
                        pattern="\d{6}" 
                        required 
                        style="text-align: center; letter-spacing: 0.5rem; font-size: 1.2rem;"
                        autofocus
                        autocomplete="one-time-code"
                        inputmode="numeric"
                    >
                </div>
                <button type="submit" class="btn-signup">Verify Code</button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.8rem;">
                <?php echo $resendHtml; ?>
                
                <div>Wrong email? 
                    <form method="POST" action="<?php echo $authenticatorUrl; ?>" style="display:inline;">
                        <button type="submit" name="reset" value="1" style="background:none; border:none; padding:0; cursor:pointer; font:inherit; color:#fff; text-decoration: underline;">Start over</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>
