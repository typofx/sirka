<?php
session_start();
// Central configuration for Cancel URL redirect
$URL_CANCEL_REDIRECT = 'https://www.typofx.ie/sirka/pauloalves/pix310/';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if the session is active and if the last activity was less than 5 minutes ago
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 300)) {
    // If the last activity was more than 5 minutes ago, destroy the session and redirect to the homepage
    session_unset();     // remove all session variables
    session_destroy();   // destroy the session
    header("Location: index");
    exit();
}

// Update the last activity timestamp to the current timestamp
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION['user'])) {
    // If the session is not started, redirect back to the homepage
    header("Location: index");
    exit();
}

// Function to generate a random 6-digit verification code
function generateVerificationCode()
{
    return mt_rand(100000, 999999);
}

// Function to send the verification code via email
function sendVerificationCode($email, $code)
{
    $subject = 'Plata Token';
    $message = 'Código de verificação: ' . $code;
    $headers = "From: Typo FX <no-reply@plata.ie>\n";
    return mail($email, $subject, $message, $headers);
}

// Check if the resend limit has been exceeded
function checkResendLimit()
{
    if (!isset($_SESSION['resend_count'])) {
        $_SESSION['resend_count'] = 0;
    }
    $_SESSION['resend_count']++;
    return $_SESSION['resend_count'] < 3;
}

// Reset the verification process
function resetVerification()
{
    unset($_SESSION['email']);
    unset($_SESSION['verification_code']);
    unset($_SESSION['resend_count']);
    unset($_SESSION['code_sent']);
    unset($_SESSION['email_sent']);
}

function resetVerification2()
{
    unset($_SESSION['email']);
    unset($_SESSION['verification_code']);
    unset($_SESSION['resend_count']);
    unset($_SESSION['code_sent']);
    unset($_SESSION['email_sent']);
    unset($_SESSION['user']);
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_email']) || isset($_POST['resend_code'])) {
        if (isset($_POST['email']) || isset($_SESSION['email'])) {
            $email = $_POST['email'] ?? $_SESSION['email'];
            if (!isset($_SESSION['code_sent']) || checkResendLimit()) {
                $_SESSION['email'] = $email; // Store the email in the session
                $_SESSION['verification_code'] = generateVerificationCode();
                $_SESSION['code_sent_time'] = time();
                sendVerificationCode($email, $_SESSION['verification_code']);
                $_SESSION['code_sent'] = true;
                //echo $email;
            } else {
                echo 'Limite de reenvio excedido. Estamos reiniciando o processo.';
                resetVerification2(); // Clear the sessions
                echo '<script type="text/javascript"> window.location.href = "' . $URL_CANCEL_REDIRECT . '";</script>';
            }
        } else {
            echo 'Por favor, insira um endereço de e-mail válido.';
        }
    } elseif (isset($_POST['verify_code'])) {
        if (isset($_POST['verification_code']) && isset($_SESSION['verification_code']) && $_POST['verification_code'] == $_SESSION['verification_code']) {
            echo '<form id="redirectForm" method="post" action="order">';
            echo '<input type="hidden" name="emailUser" value="' . $_SESSION['email'] . '">';
            resetVerification();
            echo '</form>';
            echo '<script>document.getElementById("redirectForm").submit();</script>';
        } else {
            $error_message = 'Verificação inválida';
        }
    }
}

// Check if the time to confirm exceeded 5 minutes
if (isset($_SESSION['code_sent_time']) && time() - $_SESSION['code_sent_time'] > 300) {
    echo 'Tempo limite excedido para inserir o código. Por favor, reinicie o processo.';
    resetVerification(); // Clear the sessions
}

// Check if there's an error in the URL
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'incorrect_verification_code':
            resetVerification();
            echo "Código de verificação incorreto. Por favor, tente novamente.";
            break;
        default:
            resetVerification();
            echo "Ocorreu um erro desconhecido.";
            break;
    }
}

if (isset($_GET['cancel']) || isset($_GET['order_cancel'])) {
    resetVerification2();
    session_unset();
    session_destroy();
    echo '<script type="text/javascript"> window.location.href = "' . $URL_CANCEL_REDIRECT . '";</script>';
    exit();
}

if (!isset($_SESSION['code_sent'])) {
    include __DIR__ . '/email_sent.php';
}

if (isset($_SESSION['email'])) {
    include __DIR__ . '/confirm_email.php';
}
?>
