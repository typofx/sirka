<?php 
// --- Portability Header: Recursive directory climbing to find security core ---
$currentDir = __DIR__;
while ($currentDir !== dirname($currentDir) && !file_exists($currentDir . '/%/config.php')) {
    $currentDir = dirname($currentDir);
}
require_once $currentDir . '/%/config.php';
require_once $currentDir . '/%/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Session Existence Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_logged_in_panel'])) {
    session_unset();
    session_destroy();
    redirect('auth/signin');
}

// 2. Session Timeout (12h)
$session_timeout = 43200;
if (isset($_SESSION['session_start_time']) && (time() - $_SESSION['session_start_time'] > $session_timeout)) {
    session_unset();
    session_destroy();
    redirect('auth/signin');
}

// 4. Intelligent Redirect (Prevent login page access if logged in)
$current_path = $_SERVER['REQUEST_URI'];
if (strpos($current_path, 'auth/auth.signin') !== false || strpos($current_path, 'auth/signin') !== false || strpos($current_path, 'auth/index') !== false) {
    if (isset($_SESSION['user_level_panel']) && $_SESSION['user_level_panel'] === 'root') {
        redirect('panel/menu');
    }
}

// 5. Fetch User Identity (Always fetch to ensure security settings are real-time)
if (isset($_SESSION['user_id'])) {
    try {
        global $pdo;
        
        if ($pdo) {
            $user_row = getUserIdentity($pdo, $_SESSION['user_id']);
            
            if (!$user_row) {
                session_destroy();
                redirect('auth/signin');
            }

            // 2. Propagate to Session (Keep it in sync)
            $_SESSION['user_level_panel'] = $user_row['level'];
            $_SESSION['username_panel']   = $user_row['username'];
            $_SESSION['registered_email'] = $user_row['email'];
            
            // MFA Status (Database state)
            $_SESSION['mfa_auth_app_enabled'] = (int)$user_row['authenticator_app'];
            $_SESSION['mfa_email_enabled']    = (int)$user_row['email_mfa'];
            $_SESSION['mfa_mobile_enabled']   = (int)$user_row['mobile_phone'];
            $_SESSION['mfa_telegram_enabled'] = (int)$user_row['telegram'];

            // 3. Calculate Active Layers (ONLY passed AND currently enabled)
            $active_layers = calculateActiveLayers($user_row, $_SESSION['temp_2fa_passed'] ?? []);
            
            // Globalize for menus
            $user_level = $_SESSION['user_level_panel'];
            $username   = $_SESSION['username_panel'];

        }
    } catch (Exception $e) {
        error_log("Security Core Error: " . $e->getMessage());
    }
}

// 7. Global CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

// 8. Session Timer Helper
if (!function_exists('getTimeRemaining')) {
    function getTimeRemaining() {
        $timeout = 43200;
        $elapsed = time() - ($_SESSION['session_start_time'] ?? time());
        $remaining = max(0, $timeout - $elapsed);
        return gmdate("H:i:s", $remaining);
    }
}

$timerHtml = getTimeRemaining();
?>