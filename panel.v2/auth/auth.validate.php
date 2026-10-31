<?php
if (basename($_SERVER['PHP_SELF']) === 'validate.php') {
    header('Location: ' . BASE_URL . 'auth/signin');
    exit();
}

/**
 * UNIFIED VALIDATION MODULE
 * Combines email and password validation functions
 */

// ============================================================
// EMAIL VALIDATION
// ============================================================
function validateEmail($email, $configPath) {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid e-mail format/syntax.";
    }

    // 1. Basic format validation
    if (!preg_match('/^[a-zA-Z]/', $email)) {
        return "E-mail must start with a letter (a-z).";
    }

    $forbidden = ['/', '<', '>', ';', "'", '"', '\\', '`', '|', '(', ')', '[', ']', '{', '}', ',', ' ', '#', '+'];
    foreach ($forbidden as $char) {
        if (str_contains($email, $char)) {
            return "E-mail contains forbidden characters ($char).";
        }
    }

    if (str_contains($email, '..')) {
        return "E-mail cannot contain consecutive dots.";
    }

    // 2. Split email
    $atPos = strrpos($email, '@');
    if ($atPos === false) return "Invalid e-mail format.";
    
    $userName = substr($email, 0, $atPos);
    $domainPart = strtolower(substr($email, $atPos + 1));
    $emailLower = strtolower($email);

    if (!str_contains($domainPart, '.')) {
        return "Invalid domain format (missing dot).";
    }

    $dotPos = strpos($domainPart, '.');
    $company = substr($domainPart, 0, $dotPos);

    // 3. Load Configuration
    if (!file_exists($configPath)) {
        return "Server error: Validation rules not found.";
    }
    $rules = json_decode(file_get_contents($configPath), true);

    $disposable = isset($rules['disposable_domains']) ? array_map('strtolower', $rules['disposable_domains']) : [];
    $providers = isset($rules['whitelist_providers']) ? array_map('strtolower', $rules['whitelist_providers']) : [];
    $tlds = isset($rules['whitelist_tlds']) ? array_map('strtolower', $rules['whitelist_tlds']) : [];

    // 4. Whitelist Validation (Priority)
    // Check Providers
    if (!in_array($company, $providers)) {
        return "E-mail provider is not in our allowed list.";
    }

    // Check TLDs
    $isTldValid = false;
    foreach ($tlds as $tld) {
        if (str_ends_with($emailLower, $tld)) {
            $isTldValid = true;
            break;
        }
    }
    if (!$isTldValid) {
        return "E-mail TLD is not in our allowed list.";
    }

    // 5. Disposable / Blacklist
    if (in_array($domainPart, $disposable)) {
        return "Temporary or disposable e-mail addresses are not allowed.";
    }
    $blacklist = ['flosek'];
    foreach ($blacklist as $bad) {
        if (str_contains($emailLower, strtolower($bad))) {
            return "This e-mail domain or provider is not allowed.";
        }
    }

    // 6. Strict Custom Rules 
    if (strlen($userName) <= 3) {
        return "The part before @ must be longer than 3 characters.";
    }

    return true; 
}

// ============================================================
// PASSWORD VALIDATION
// ============================================================
function validatePassword($password) {
    $password = trim($password);
    
    // 1. Check minimum length (12 characters)
    if (strlen($password) < 12) {
        return "Password must be at least 12 characters long.";
    }

    // 2. Check for uppercase letters
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter (A-Z).";
    }

    // 3. Check for lowercase letters
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter (a-z).";
    }

    // 4. Check for at least 3 special characters
    $specialChars = preg_match_all('/[^a-zA-Z0-9]/', $password);
    if ($specialChars < 3) {
        return "Password must contain at least 3 special characters.";
    }

    // 5. Check for 3 consecutive digits (not allowed)
    if (preg_match('/\d{3}/', $password)) {
        return "Password cannot contain 3 or more consecutive digits (e.g., 123, 567, 234).";
    }

    // Password is valid
    return true;
}

// ============================================================
// FLASH MESSAGES (SHARED)
// ============================================================
if (!function_exists('set_flash_message')) {
    function set_flash_message($msg, $type = 'success') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $key = ($type === 'error') ? 'flash_error' : 'flash_success';
        $_SESSION[$key] = $msg;
    }
}

if (!function_exists('get_flash_messages')) {
    function get_flash_messages() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $msgs = [
            'success' => $_SESSION['flash_success'] ?? '',
            'error' => $_SESSION['flash_error'] ?? ''
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        return $msgs;
    }
}

if (!function_exists('display_flash_message')) {
    function display_flash_message() {
        $msgs = get_flash_messages();
        $color = ''; $bg = ''; $border = ''; $msg = '';
        
        if ($msgs['error']) {
            $msg = $msgs['error'];
            $color = '#ff6b6b'; $bg = 'rgba(255,107,107,0.1)'; $border = 'rgba(255,107,107,0.2)';
        } elseif ($msgs['success']) {
            $msg = $msgs['success'];
            $color = '#4CAF50'; $bg = 'rgba(76,175,80,0.1)'; $border = 'rgba(76,175,80,0.2)';
        } else {
            return;
        }

        $map = [
            'email_updated' => 'Email address updated.',
            'password_updated' => 'Password changed.',
            'password_recovered' => 'Password recovered.',
            'deactivated' => 'Security layer deactivated.',
            '1' => 'Security layer activated!'
        ];
        $display_msg = $map[$msg] ?? $msg;

        echo '<div style="margin-bottom: 25px; padding: 14px 20px; color: '.$color.'; font-weight:600; background: '.$bg.'; border: 1px solid '.$border.'; text-align:center; border-radius: 12px; backdrop-filter: blur(15px);">'.htmlspecialchars($display_msg).'</div>';
    }
}

// ============================================================
// EMAIL MASKING UTILITY
// ============================================================
if (!function_exists('maskEmail')) {
    function maskEmail($email) {
        if (empty($email)) return "";
        
        $atPos = strpos($email, '@');
        if ($atPos === false) return $email;

        $localPart = substr($email, 0, $atPos);
        $domainPart = substr($email, $atPos + 1);
        
        $lastDotPos = strrpos($domainPart, '.');
        if ($lastDotPos === false) {
            $domainName = $domainPart;
            $extension = "";
        } else {
            $domainName = substr($domainPart, 0, $lastDotPos);
            $extension = substr($domainPart, $lastDotPos + 1);
        }

        $maskedLocal = substr($localPart, 0, 2) . "****" . substr($localPart, -1);
        $maskedDomain = substr($domainName, 0, 1) . "****";
        $maskedExt = "***";

        return $maskedLocal . "@" . $maskedDomain . ($extension ? "." . $maskedExt : "");
    }
}

?>
