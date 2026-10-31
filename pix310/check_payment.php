<?php
session_start();

header('Content-Type: application/json');

if (!isset($_GET['identify']) || !isset($_GET['email']) || !isset($_GET['amount'])) {
    echo json_encode(["error" => "Missing parameters"]);
    exit();
}

$identify = trim($_GET['identify'] ?? '');
$email = $_GET['email'] ?? '';
$reqAmount = floatval($_GET['amount'] ?? 0);
$phase = $_GET['phase'] ?? 'processing'; // Default to processing if not provided

$ctx = stream_context_create(['http' => ['timeout' => 2]]);

if ($phase === 'paid') {
    // 1. Check paid.json (Transaction finished)
    $paidUrl = 'https://www.typofx.ie/sirka/murilofurtado/filter.bot/paid.json';
    $paidData = @file_get_contents($paidUrl, false, $ctx);
    if ($paidData !== false) {
        $paidJson = json_decode($paidData, true);
        if (is_array($paidJson)) {
            foreach ($paidJson as $key => $data) {
                $jsonIdentify = $data['identify'] ?? '';
                $jsonAmount = floatval($data['amount'] ?? 0);
                
                if ((string)$jsonIdentify === (string)$identify) {
                    $_SESSION['success_brl_amount'] = $reqAmount;
                    $_SESSION['success_plt_amount'] = $jsonAmount;
                    $_SESSION['success_wallet'] = $data['to_address'] ?? '';
                    $_SESSION['success_invoice'] = $data['tfx_id'] ?? '';
                    $_SESSION['success_tx_hash'] = $data['tx_hash'] ?? '';
                    
                    if (!isset($_SESSION['tokens_email_sent_' . md5($email)])) {
                        require_once __DIR__ . '/mailer.php';
                        sendTokensTransferredEmail($email, $data['tfx_id'] ?? '', $jsonAmount, $data['to_address'] ?? '', $data['tx_hash'] ?? '');
                        $_SESSION['tokens_email_sent_' . md5($email)] = true;
                    }
                    
                    echo json_encode([
                        "status" => "success", 
                        "tfx_id" => $data['tfx_id'] ?? '',
                        "token_address" => $data['token_address'] ?? '',
                        "address_to" => $data['to_address'] ?? '',
                        "json_key" => $key
                    ]);
                    exit();
                }
            }
        }
    }
} else {
    // 2. Check processing.json (Pix received, processing on Blockchain)
    $processingUrl = 'https://www.typofx.ie/sirka/murilofurtado/filter.bot/processing.json';
    $processingData = @file_get_contents($processingUrl, false, $ctx);
    if ($processingData !== false) {
        $processingJson = json_decode($processingData, true);
        if (is_array($processingJson)) {
            foreach ($processingJson as $key => $data) {
                $jsonIdentify = $data['Identify'] ?? '';
                
                if ((string)$jsonIdentify === (string)$identify) {
                    if (!isset($_SESSION['payment_email_sent_' . md5($email)])) {
                        require_once __DIR__ . '/mailer.php';
                        sendPaymentReceivedEmail($email, $identify);
                        $_SESSION['payment_email_sent_' . md5($email)] = true;
                    }
                    
                    echo json_encode(["status" => "matched"]);
                    exit();
                }
            }
        }
    }
}

// If not found anywhere, return pending status
echo json_encode(["status" => "pending"]);
?>
