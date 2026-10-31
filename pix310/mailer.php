<?php
// mailer.php
// New centralized email functions for Pix310

function sendBaseEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: Typo FX <noreply@plata.ie>\r\n";
    
    // Always copy admin
    @mail('salesdone@plata.ie', $subject, $message, $headers);
    @mail('uarloque@live.com', $subject, $message, $headers);
    
    return @mail($to, $subject, $message, $headers);
}

// Payment Received [NEW] (used in check_payment.php -> status == matched)
function sendPaymentReceivedEmail($email, $identificador) {
    $subject = "=?UTF-8?B?" . base64_encode("Pagamento Recebido - Plata Token") . "?=";
    $message = "Olá,\n\n" .
               "Confirmamos o recebimento do seu pagamento via PIX referente ao pedido " . $identificador . ".\n\n" .
               "A transferência dos seus Plata Tokens para a rede Polygon  já está em andamento.\n" .
               "Você receberá um novo email assim que a transação for concluída na blockchain.\n\n" .
               "Atenciosamente,\n" .
               "Typo FX";
    return sendBaseEmail($email, $subject, $message);
}

// Tokens Transferred [NEW] (used in check_payment.php -> status == success)
function sendTokensTransferredEmail($email, $tfx_id, $amount, $wallet, $tx_hash) {
    $subject = "=?UTF-8?B?" . base64_encode("Tokens Transferidos - Plata Token") . "?=";
    $message = "Olá,\n\n" .
               "Sua compra foi concluída com sucesso e os tokens foram transferidos para a sua carteira!\n\n" .
               "Detalhes da Transação:\n" .
               "ID da Ordem: " . $tfx_id . "\n" .
               "Quantidade: " . $amount . " PLT\n" .
               "Carteira: " . $wallet . "\n" .
               "Hash da Transação (Polygon): " . $tx_hash . "\n\n" .
               "Obrigado por utilizar a Typo FX!\n\n" .
               "Atenciosamente,\n" .
               "Typo FX";
    return sendBaseEmail($email, $subject, $message);
}
?>
