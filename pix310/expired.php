<?php
session_start();

// Se o email veio via POST, foi uma expiração natural de tempo.
// Caso contrário (acesso direto, refresh, ou redirecionamento após fechar a aba), exibimos "Transação Anulada".
$isClosed = !isset($_POST['email']);

if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $emailUser = $_POST['email'];
    
    // Evita o envio duplicado ao atualizar a página
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
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Plata Token - <?php echo $isClosed ? "Transação Anulada" : "Pedido Expirado"; ?></title>
    <link rel="stylesheet" href="pix-style.css">
</head>
<body>
    <div class="plata-card">
            <h3>Plata Token / PIX</h3>
            <div>
                <form>
                    <div class="div-label-center">
                        <?php if ($isClosed): ?>
                            <label class="message-title-voided">Transação Anulada</label>
                        <?php else: ?>
                            <label class="message-title-expired">Pedido Expirado</label>
                        <?php endif; ?>
                    </div>
                    <div class="message-text">
                        <?php if ($isClosed): ?>
                            Transação anulada pois a aba foi fechada ou atualizada. Mantenha a página aberta até a conclusão do pagamento.
                        <?php else: ?>
                            O tempo limite de 10 minutos para efetuar o pagamento expirou. O QR Code gerado não é mais válido.
                        <?php endif; ?>
                    </div>
                    <hr width="95%" class="hrline">
                    <a href="index.php" class="buttonBuyNow btn-redirect">Novo Pedido</a>
                    <hr width="95%" class="hrline">
                    <div class="logo-container">
                        <br>
                        <img src="https://www.plata.ie/images/pix-full-logo.svg" class="logo-pix" alt="PIX Logo">
                        <br>
                        <br>
                    </div>
                </form>
            </div>
        </div>
    <?php include __DIR__ . '/version.php'; ?>
</body>
</html>
