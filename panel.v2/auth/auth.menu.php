<?php 
$currentDir = __DIR__;
while ($currentDir !== dirname($currentDir) && !file_exists($currentDir . '/%/is_logged.php')) {
    $currentDir = dirname($currentDir);
}
require_once $currentDir . '/%/is_logged.php';
?>
<?php require_once 'auth.validate.php'; ?>

<?php

$email = $_SESSION['registered_email'];//null;
$user_id = $_SESSION['user_id'];//0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    unverify_ip();
    session_destroy();
    redirect('auth');
}

// Security layers and root status are now centrally calculated in is_logged.php
// $active_layers and $is_root are already available here.

$safe_username = htmlspecialchars($username ?? 'User', ENT_QUOTES, 'UTF-8');

$rootButtonsHtml = '';
if ($user_level === 'root' && $active_layers >= 2) {
    $accessUrl = url('panel/acess/list');
    $panelUrl = url('panel/menu');
    $rootButtonsHtml = <<<HTML
                <a href="{$accessUrl}" class="btn-signup btn-auth-action">
                    <i class="fa-solid fa-terminal"></i> User Access
                </a>
                <a href="{$panelUrl}" class="btn-signup btn-auth-action">
                    <i class="fa-solid fa-gauge-high"></i> Control Panel
                </a>
HTML;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Central Menu | typofxCX</title>
    <link rel="stylesheet" href="<?php echo url('assets/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/auth.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/fontawesome.css'); ?>">
</head>
<body data-theme="dark">
    <div class="register-container">
        <div class="glass-card wide auth-container">
            <div class="brand">
                <h1>typofx.ie</h1>
                <p>Welcome <strong><?php echo $safe_username; ?></strong>!</p>
            </div>
            
            <h2 class="auth-title">Level Zero One</h2>


            <div class="auth-button-group">
                <?php echo $rootButtonsHtml; ?>

                <a href="<?php echo url('edit/socialtree'); ?>" class="btn-signup btn-auth-action">
                    <i class="fa-solid fa-share-nodes"></i> Social Tree
                </a>
                <a href="<?php echo url('auth/security'); ?>" class="btn-signup btn-auth-action">
                    <i class="fa-solid fa-shield-halved"></i> Safety Setup
                </a>

                <div style="text-align: center; margin-top: 20px; font-size: 0.75rem; color: var(--text-dim); width: 100%;">
                    <?php echo $timerHtml; ?>
                </div>

                <form method="POST" action="<?php echo url('auth/menu'); ?>" style="display:inline; margin-top: 10px;">
                    <button type="submit" name="logout" value="1" class="sign-out-link" style="background:none; border:none; padding:0; cursor:pointer; font:inherit; color:inherit; text-decoration: underline;">[Sign-Out]</button>
                </form>
            </div>
            
        </div>
    </div>
</body>
</html>
<?php
ob_end_flush();
?>
