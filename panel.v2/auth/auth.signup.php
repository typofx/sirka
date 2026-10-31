<?php 
$currentDir = __DIR__;
while ($currentDir !== dirname($currentDir) && !file_exists($currentDir . '/%/config.php')) {
    $currentDir = dirname($currentDir);
}
require_once $currentDir . '/%/config.php';

// Signup flow is disabled
header('Location: ' . BASE_URL . 'auth/signin');
exit();

$emailError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];
    $validationResult = validateEmail($email, 'auth.email_rules.json');

    if ($validationResult !== true) {
        $emailError = $validationResult;
    } else {
        try {
            // Check if user exists (Sign Up requires new account)
            $table = DB_TABLE_TYPO;
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE email = ?");
            $stmt->execute([$email]);
            $exists = $stmt->fetchColumn() > 0;

            if ($exists) {
                $emailError = "Email already registered. Please <a href='" . BASE_URL . "auth/signin' style='color:inherit;text-decoration:underline;'>Sign In</a>.";
            } else {
                unset($_SESSION['verification_code']);
                unset($_SESSION['signup_captcha_passed']);
                unset($_SESSION['user_id']); 
                $_SESSION['registered_email'] = $email;
                header('Location: ' . BASE_URL . 'auth/register');
                exit();
            }
        } catch (Exception $e) {
            $emailError = "System error: " . $e->getMessage();
            error_log($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | typofxCX</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/style.css"><link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/auth.css">
</head>

<body data-theme="dark">
    <div class="register-container">
        <div class="glass-card">
            <div class="brand">
                <h1>typofx.ie</h1>
            </div>

            <?php if ($emailError): ?>
                <div class="error-message" style="background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.3); color: #ff8e8e; padding: 15px; border-radius: 12px; margin-bottom: 2rem; display: flex; align-items: center; gap: 12px; font-size: 0.95rem; line-height: 1.4;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span><?php echo $emailError; ?></span>
                </div>
            <?php endif; ?>

            <form action="<?php echo BASE_URL; ?>auth/signup" method="POST" id="signup-form">
                <div class="form-group">
                    <label for="email">Enter your email</label>
                    <input type="email" name="email" id="email" placeholder="email@example.com" required autofocus autocomplete="email">
                </div>

                <button type="submit" id="signup-btn" class="btn-signup">Sign Up</button>

                <div class="checkbox-group" style="margin-top: 1.5rem; margin-bottom: 0;">
                    <input type="checkbox" id="privacy-check" required>
                    <label for="privacy-check">I agree to <a href="https://www.typofx.ie/">Typo FX</a>'s <a
                            href="#">Privacy Notice</a></label>
                </div>
            </form>

            <div style="text-align: center; margin-top: 2rem; font-size: 0.9rem; color: var(--text-dim);">
                Already have an account? <a href="<?php echo BASE_URL; ?>auth/signin" style="color: #fff; font-weight: 600;">Sign In</a>
            </div>
        </div>
    </div>
</body>

</html>
