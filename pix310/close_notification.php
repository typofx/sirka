<?php
session_start();

if (isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    $emailUser = $_GET['email'];
    
    // Evita o envio duplicado se já foi enviado
    if (!isset($_SESSION['close_email_sent_' . md5($emailUser)])) {
        $emailSubject = "=?UTF-8?B?" . base64_encode("Transação Cancelada - Plata Token") . "?=";
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "From: noreply@plata.ie\r\n";
        $emailMessage = 
            "Olá,\n\n" .
            "Identificamos que você fechou a aba de pagamento do PIX antes de concluir a transação.\n" .
            "Por motivos de segurança e processamento, a transação foi anulada. Por favor, mantenha a aba de pagamento aberta até que o pagamento seja processado.\n\n" .
            "Se você ainda deseja adquirir Plata Tokens, por favor inicie um novo pedido.\n\n" .
            "Atenciosamente,\n" .
            "Typo FX";
            
        mail($emailUser, $emailSubject, $emailMessage, $headers);
        mail('salesdone@plata.ie', $emailSubject, $emailMessage, $headers);
        mail('uarloque@live.com', $emailSubject, $emailMessage, $headers);
        
        $_SESSION['close_email_sent_' . md5($emailUser)] = true;
    }
}
?>
