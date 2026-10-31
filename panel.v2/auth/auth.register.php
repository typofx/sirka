<?php
// Registration flow is disabled
header('Location: ' . BASE_URL . 'auth/signin');
exit();

$registrationSuccess = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $table = DB_TABLE_TYPO;
        $resId = $pdo->query("SELECT MAX(id) as max_id FROM $table");
        $rowId = $resId->fetch();
        $nextId = (int) ($rowId['max_id'] ?? 0) + 1;

        $u_username = $_POST['username'] ?? '';
        $u_first = $_POST['first_name'] ?? '';
        $u_last = $_POST['last_name'] ?? '';
        $u_pass = $_POST['password'] ?? '';
        $u_email = $user_email;

        $chk = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE username = ?");
        $chk->execute([$u_username]);
        if ($chk->fetchColumn() > 0) {
            $error = "Username '{$u_username}' is already taken. Please choose another.";
        } else {
            $val_pass = validatePassword($u_pass);
            if ($val_pass !== true) {
                $error = $val_pass;
            } else {
                $hashed_pass = password_hash($u_pass, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO $table 
                    (id, email, username, name, last_name, password, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())");

                if ($stmt->execute([$nextId, $u_email, $u_username, $u_first, $u_last, $hashed_pass])) {
                    $tableConfig = DB_TABLE_CONF_TYPO;
                    $stmtConfig = $pdo->prepare("INSERT IGNORE INTO $tableConfig (user_id, biometrics, authenticator_app, email, mobile_phone, telegram, token) VALUES (?, 0, 0, 1, 0, 0, 0)");
                    $stmtConfig->execute([$nextId]);

                    $_SESSION['user_id'] = $nextId;
                    $_SESSION['user_logged_in_panel'] = true; // Required by is_logged.php
                    $_SESSION['just_registered'] = true;
                    header('Location: ' . BASE_URL . 'auth/menu');
                    exit();
                } else {
                    $error = "Failed to save data.";
                }
            }
        }
    } catch (PDOException $e) {
        $error = "Registration failed. Please try again later.";
        error_log("Registration error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Registration | Typo FX</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/style.css"><link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/auth.css">
</head>

<body data-theme="dark">
    <div class="register-container">
        <div id="main-card" class="glass-card">
            <?php if(function_exists('display_flash_message')) display_flash_message(); ?>

            <div id="form-view">
                <div class="brand">
                    <h1>typofx.ie</h1>
                    <p>Complete profile for <strong><?php echo htmlspecialchars($user_email); ?></strong></p>
                </div>
                <?php if ($error): ?>
                    <div class="error-message" style="color: #ff6b6b; text-align: center; margin-bottom: 1rem; font-weight: 600;">
                        <?php echo $error; ?></div><?php endif; ?>
                
                <form id="register-form" method="POST" action="<?php echo BASE_URL; ?>auth/register" autocomplete="off">
                    <div class="form-group"><label>Username</label><input type="text" id="username" name="username" maxlength="18" required></div>
                    <div class="form-row">
                        <div class="form-group"><label>First Name</label><input type="text" name="first_name" maxlength="14" required></div>
                        <div class="form-group"><label>Last Name</label><input type="text" name="last_name" maxlength="14" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Password</label><input type="password" id="password" name="password" minlength="12" maxlength="128" required></div>
                        <div class="form-group"><label>Confirm</label><input type="password" id="confirm_password" name="confirm_password" minlength="12" maxlength="128" required></div>
                    </div>
                    <button type="submit" id="complete-btn" class="btn-signup">Finish Registration</button>
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="<?php echo BASE_URL; ?>auth/signin?reset=1" style="color: #888; text-decoration: underline; font-size: 0.9rem;">Start Over</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const p = document.getElementById('password');
            const cp = document.getElementById('confirm_password');
            if (p && cp) {
                const val = () => { if (cp.value !== '') cp.style.borderColor = (p.value === cp.value) ? 'rgba(76, 175, 80, 0.5)' : 'rgba(255, 107, 107, 0.5)'; };
                [p, cp].forEach(el => el.addEventListener('input', val));
            }
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
