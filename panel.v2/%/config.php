<?php
    if (basename($_SERVER['PHP_SELF']) === 'config.php') {
        header('Location: ../auth/signin');
        exit();
    }
    require_once (__DIR__ . '/paths.php');
    require_once (__DIR__ . '/helpers.php');
    
    // Safely include environment and connection files only if not already loaded
    if (!defined('DB_HOST')) {
        require_once (BASE_SERVER_PATH . 'env.php');
    }
    if (!isset($pdo)) {
        require_once (BASE_SERVER_PATH . 'conexao2.php');
    }
    
    // Base URL for the project (Automatically detects current folder relative to DOCUMENT_ROOT)
    $panelRootDir = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
    $docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']) : '';
    if ($docRoot !== '/') {
        $docRoot = rtrim($docRoot, '/');
    }

    if (!empty($docRoot) && str_starts_with($panelRootDir, $docRoot)) {
        $base_url = substr($panelRootDir, strlen($docRoot));
    } else {
        $script_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $panelFolder = basename($panelRootDir);
        if (preg_match('#/' . preg_quote($panelFolder, '#') . '(/|$)#', $script_path)) {
            $base_url = preg_replace('#(/' . preg_quote($panelFolder, '#') . ')(/.*)?$#', '$1', $script_path);
        } else {
            $base_url = preg_replace('#/(auth|panel|edit)(/.*)?$#', '', $script_path);
        }
    }
    define('BASE_URL', rtrim($base_url, '/') . '/');

    if (session_status() === PHP_SESSION_NONE) {
        // 12 hours session lifetime (43200 seconds)
        ini_set('session.gc_maxlifetime', 43200);
        ini_set('session.cookie_lifetime', 43200);
        session_start();
    }

    // 12h Session Expiration Logic (Manual fallback)
    if (isset($_SESSION['user_id'])) {
        $session_timeout = 43200; 
        if (!isset($_SESSION['session_start_time'])) {
            $_SESSION['session_start_time'] = time();
        }
        if (time() - $_SESSION['session_start_time'] > $session_timeout) {
            session_unset();
            session_destroy();
            header("Location: " . BASE_URL . "auth/signin");
            exit();
        }
    }
    ob_start();

    // Generate CSRF token if missing
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $token = $_SESSION['csrf_token'];

    // Project-Specific Table Constants
    define('DB_TABLE_TYPO', 'users');
    define('DB_TABLE_CONF_TYPO', 'typofx_user_config');
    define('IP_TABLE_TYPO', 'typofx_ip_list');
    
    if (!defined('IP_COLUMN')) {
        define('IP_COLUMN', 'ipv4_address');
    }

    // Security Headers (Simplified)
    if (!headers_sent()) {
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }

    /**
     * Logs a system error and returns a formatted message for the UI.
     * During debugging, it can be set to show more details.
     */
    function log_system_error(Exception $e): string
    {
        error_log($e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        return "An internal server error occurred. Please try again in a few minutes.";
    }

    function get_client_ip(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        }

        return trim($_SERVER['REMOTE_ADDR'] ?? '');
    }

    function is_ip_verified(): bool
    {
        $ip = get_client_ip();
        if ($ip === '') return false;

        try {
            global $pdo;
            if (!isset($pdo)) return false;

            $table = defined('IP_TABLE') ? IP_TABLE : 'typofx_ip_list';
            $column = IP_COLUMN;
            $stmt = $pdo->prepare("SELECT 1 FROM $table WHERE $column = ? LIMIT 1");
            $stmt->execute([$ip]);
            return (bool)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('IP verification check failed: ' . $e->getMessage());
            return false;
        }
    }

    function mark_ip_verified(): void
    {
        $ip = get_client_ip();
        if ($ip === '') return;

        try {
            global $pdo;
            if (!isset($pdo)) return;

            $table = defined('IP_TABLE') ? IP_TABLE : 'typofx_ip_list';
            $column = IP_COLUMN;
            $stmt = $pdo->prepare("INSERT IGNORE INTO $table ($column) VALUES (?)");
            $stmt->execute([$ip]);
        } catch (Exception $e) {
            error_log('IP verification insert failed: ' . $e->getMessage());
        }
    }

    function unverify_ip(): void
    {
        $ip = get_client_ip();
        if ($ip === '') return;

        try {
            global $pdo;
            if (!isset($pdo)) return;

            $table = defined('IP_TABLE') ? IP_TABLE : 'typofx_ip_list';
            $column = IP_COLUMN;
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $column = ?");
            $stmt->execute([$ip]);
        } catch (Exception $e) {
            error_log('IP verification delete failed: ' . $e->getMessage());
        }
    }
?>
