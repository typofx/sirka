<?php
/**
 * Utility Helpers - typofx.ie
 */

/**
 * Centralized user data and security fetch
 */
function getUserIdentity($pdo, $user_id) {
    if (!$user_id) return null;
    
    $tableUsers = 'users';
    $tableConfig = 'typofx_user_config';
    
    try {
        $stmt = $pdo->prepare("SELECT u.level, u.username, u.email, u.auth_secret,
                                      c.authenticator_app, c.email as email_mfa, c.mobile_phone, c.telegram
                               FROM $tableUsers u 
                               LEFT JOIN $tableConfig c ON u.id = c.user_id 
                               WHERE u.id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Helper Error: getUserIdentity failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Calculate active security layers based on passed and enabled MFA
 */
function calculateActiveLayers($user, $passed_layers) {
    $passed = 0;
    $passed_layers = is_array($passed_layers) ? $passed_layers : [];

    if (!$user) return 0;

    // Database gates that can be enabled/disabled
    if (($user['authenticator_app'] ?? 0) == 1 && in_array('authenticator_app', $passed_layers)) $passed++;
    if (($user['email_mfa'] ?? 0) == 1         && in_array('email', $passed_layers))             $passed++;
    if (($user['mobile_phone'] ?? 0) == 1      && in_array('mobile_phone', $passed_layers))      $passed++;
    if (($user['telegram'] ?? 0) == 1          && in_array('telegram', $passed_layers))          $passed++;

    return $passed;
}

/**
 * Generate fully qualified URL for modular routes and external URLs
 */
function url(string $path = '', array $params = []): string {
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        $baseUrl = $path;
    } else {
        $base = rtrim(defined('BASE_URL') ? BASE_URL : '/', '/');
        $cleanPath = ltrim($path, '/');
        $baseUrl = $cleanPath !== '' ? $base . '/' . $cleanPath : $base . '/';
    }

    if (!empty($params)) {
        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        $baseUrl .= $separator . http_build_query($params);
    }

    return $baseUrl;
}

/**
 * Unified Redirect with Flash Message
 */
function redirect(string $path, array $flash = []): void {
    if (!empty($flash['success'])) $_SESSION['flash_success'] = $flash['success'];
    if (!empty($flash['error']))   $_SESSION['flash_error'] = $flash['error'];
    $params = array_diff_key($flash, ['success' => 1, 'error' => 1]);
    header('Location: ' . url($path, $params));
    exit();
}

/**
 * Legacy Redirect with Flash Message adapter
 */
function redirectWithFlash($url, $success = null, $error = null) {
    redirect($url, [
        'success' => $success,
        'error'   => $error
    ]);
}

/**
 * Display Success/Error alerts with premium styling
 */
function displayFlashMessages() {
    $success = $_SESSION['flash_success'] ?? '';
    $error = $_SESSION['flash_error'] ?? '';
    unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    
    if ($success) {
        $messages = [
            '1' => 'Operation successful!',
            'email_updated' => 'Email updated successfully!',
            'password_updated' => 'Password updated successfully!',
            'password_recovered' => 'Password recovered successfully!',
        ];
        $text = $messages[$success] ?? $success;
        echo '<div class="alert alert-success" style="background: rgba(40, 167, 69, 0.1); color: #28a745; padding: 12px 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid rgba(40, 167, 69, 0.2); font-weight: 500; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-circle-check"></i> ' . htmlspecialchars($text) . '</div>';
    }
    
    if ($error) {
        echo '<div class="alert alert-danger" style="background: rgba(220, 53, 69, 0.1); color: #dc3545; padding: 12px 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid rgba(220, 53, 69, 0.2); font-weight: 500; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-circle-exclamation"></i> ' . htmlspecialchars($error) . '</div>';
    }
}
