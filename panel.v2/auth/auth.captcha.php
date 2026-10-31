<?php 
$currentDir = __DIR__;
while ($currentDir !== dirname($currentDir) && !file_exists($currentDir . '/%/config.php')) {
    $currentDir = dirname($currentDir);
}
require_once $currentDir . '/%/config.php';
?>

<?php
$error = ''; // Initialize error variable

if (is_ip_verified()) {
    $redirect = $_SESSION['captcha_redirect'] ?? 'signin';
    unset($_SESSION['captcha_redirect']);
    
    if (strpos($redirect, 'http') === 0) {
        redirect($redirect);
    } else {
        $cleanRoute = str_replace('auth.', '', $redirect);
        $cleanRoute = str_starts_with($cleanRoute, 'auth/') ? $cleanRoute : 'auth/' . ltrim($cleanRoute, '/');
        redirect($cleanRoute);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    if (empty($recaptchaResponse)) {
        $error = "Please verify that you are a human.";
    } else {
        // Validate against Google API
        $secretKey = RECAPTCHA_SECRET;
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $ip = function_exists('get_client_ip') ? get_client_ip() : $_SERVER['REMOTE_ADDR'];
        $data = [
            'secret' => $secretKey,
            'response' => $recaptchaResponse,
            'remoteip' => $ip
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        
        $responseKeys = json_decode($result, true);

        // Enterprise API returns "score" and "tokenProperties" if using REST, but with siteverify it returns standard success/fail.
        if (isset($responseKeys["success"]) && $responseKeys["success"]) {
            // Passed!
            mark_ip_verified();
            $redirect = $_SESSION['captcha_redirect'] ?? 'signin';
            if ($redirect === 'auth.signup' || $redirect === 'auth/signup' || $redirect === 'signup') {
                $_SESSION['signup_captcha_passed'] = true;
            }
            unset($_SESSION['captcha_redirect']);
            session_write_close(); 
            
            if (strpos($redirect, 'http') === 0) {
                redirect($redirect);
            } else {
                $cleanRoute = str_replace('auth.', '', $redirect);
                $cleanRoute = str_starts_with($cleanRoute, 'auth/') ? $cleanRoute : 'auth/' . ltrim($cleanRoute, '/');
                redirect($cleanRoute);
            }
        } else {
            $error = "Security validation failed. Please try again.";
        }
    }
}

$errorHtml = $error ? '<div class="error-msg">' . htmlspecialchars($error) . '</div>' : '';
$captchaAction = url('auth/captcha');
$styleUrl = url('assets/style.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Verification | Typo FX</title>
    <link rel="stylesheet" href="<?php echo $styleUrl; ?>">
    <script src="https://www.google.com/recaptcha/enterprise.js" async defer></script>
</head>

<body class="captcha-theme" data-theme="dark">

    <div class="captcha-container">
        <h1 class="brand-title">typofx.ie</h1>
        <h2 class="section-title">Performing security verification</h2>
        <p class="description">
            This website uses a security service to protect against malicious bots. This page is displayed while the
            website verifies you are not a bot.
        </p>

        <?php echo $errorHtml; ?>

        <form id="captchaForm" method="POST" action="<?php echo $captchaAction; ?>">
            <!-- reCAPTCHA Enterprise -->
            <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE; ?>" data-theme="dark" data-callback="onCaptchaSuccess"></div>
        </form>
    </div>

    <div class="footer-info">
        Ray ID: <?php echo uniqid(); ?><br>
        Performance and Security Verification Module
    </div>

    <script>
        // Callback function triggered when reCAPTCHA is successfully completed
        function onCaptchaSuccess() {
            // Auto-submit the form
            document.getElementById('captchaForm').submit();
        }
    </script>

</body>

</html>
<?php ob_end_flush(); ?>
