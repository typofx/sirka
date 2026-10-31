<?php
session_start();
header('Content-Type: application/json');

if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $emailUser = $_POST['email'];
    
    // Evita o envio duplicado ao atualizar a página ou chamadas simultâneas
    if (!isset($_SESSION['expired_email_sent_' . md5($emailUser)])) {
        $emailSubject = "=?UTF-8?B?" . base64_encode("Compra Expirada - Plata Token") . "?=";
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "From: noreply@plata.ie\r\n";
        $emailMessage = 
            "Olá,\n\n" .
            "O tempo limite de 10 minutos para o pagamento do seu pedido PIX expirou.\n" .
            "A transação foi cancelada e o QR Code gerado não é mais válido.\n\n" .
            "Se você ainda deseja adquirir Plata Tokens, por favor inicie um novo pedido.\n\n" .
            "Atenciosamente,\n" .
            "Typo FX";
            
        mail($emailUser, $emailSubject, $emailMessage, $headers);
        mail('salesdone@plata.ie', $emailSubject, $emailMessage, $headers);
        mail('uarloque@live.com', $emailSubject, $emailMessage, $headers);
        
        $_SESSION['expired_email_sent_' . md5($emailUser)] = true;
        echo json_encode(["status" => "success", "message" => "Email sent"]);
    } else {
        echo json_encode(["status" => "skipped", "message" => "Email already sent in this session"]);
    }
} else {
    echo json_encode(["error" => "Invalid or missing email"]);
}
?>
