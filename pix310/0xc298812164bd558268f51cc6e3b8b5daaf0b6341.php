<?php
session_start();
if (!isset($_SESSION['emailUser'])) {
    header("Location: expired");
    exit();
}

include "conexao.php"; 
include "phpqrcode/qrlib.php";
include "funcoes_pix.php";

$emailUser = $_SESSION['emailUser'];

$emailSubject = "=?UTF-8?B?" . base64_encode("Compra de Token Plata via PIX") . "?=";
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "From: noreply@plata.ie\r\n";

$valor_pix = number_format($_POST['valorpix'], 2, '.', ',');
$valor_pix_formatado = number_format((float)$_POST['valorpix'], 2, ',', '.');
$chave_pix = "2bfd45fe-c9ef-4931-8d32-0031d614266a";
$beneficiario_pix = "Typo FX Brasil";
$cidade_pix = "Santos";
$web3wallet = $_POST['web3wallet'] ?? '';
if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $web3wallet)) {
    header("Location: email.php");
    exit();
}
$identificador = $_POST['identificador'];
$gerar_qrcode = true;
$PLTwanted = number_format($_POST["PLTwanted"], 4, '.', ',');
$TXTdate = $_POST["TXTdate"] ?? '';

date_default_timezone_set('America/Sao_Paulo');
$date_front = date("H:i:s \B\R\T d/m/Y");
$ExpTimestamp = time() + 600; // 10 minutes in seconds
$Expdate_front = date("H:i:s \B\R\T d/m/Y", $ExpTimestamp);

$emailMessage =
    "Email do usuário: " . $emailUser . "\n" . "\n" .
    "Ordem Executada Em : " . $date_front . "\n" .
    "Valor Aguardado (BRL) : " . $valor_pix . "\n" .
    "Tokens Plata Previstas (PLT) : " . $PLTwanted . "\n" .
    "Web3 Wallet : " . $web3wallet . "\n" .
    "Chain ID : Polygon (137)" . "\n" .
    "Identificador : " . $identificador . "\n" .
    "Orçamento Válido Até : " . $Expdate_front;

mail($emailUser, $emailSubject, $emailMessage, $headers);
mail('salesdone@plata.ie', $emailSubject, $emailMessage, $headers);
mail('uarloque@live.com', $emailSubject, $emailMessage, $headers);

date_default_timezone_set('UTC'); // Database needs UTC
$date = date("Y-m-d H:i:s");
$bank = "PIX";
$plata = $PLTwanted;
$amount = $_POST['valorpix']; 
$asset = "BRL";
$address = $web3wallet;
$txid = "polygon"; 
$email = $emailUser; 
$status = "pending"; 

// Secure SQL query (Prepared Statement) to avoid SQL Injection
$sql = "INSERT INTO granna80_bdlinks.payments (date, bank, plata, amount, asset, address, txid, email, status, identify) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // 's' for strings and 'd' for double (decimal/numeric)
    $stmt->bind_param("sssdssssss", $date, $bank, $plata, $amount, $asset, $address, $txid, $email, $status, $identificador);
    
    if ($stmt->execute()) {
        $text1 = "Dados do pagamento inseridos com sucesso.";
    } else {
        $text2 = "Erro ao inserir dados do pagamento: " . $stmt->error;
    }
    $stmt->close();
} else {
    $text2 = "Erro ao preparar a consulta: " . $conn->error;
}

$px[00] = "01"; // Payload Format Indicator, Mandatory, fixed value: 01
// If the QR Code is for a single payment (can only be used once), uncomment the following line.
$px[01] = "12"; // If the value 12 is present, it means the BR Code can only be used once.
$px[26][00] = "br.gov.bcb.pix"; // Indicates specific arrangement; “00” (GUI) mandatory and fixed value: br.gov.bcb.pix
$px[26][01] = $chave_pix;
if (!empty($descricao)) {
    /*
                    It is not possible for the PIX key and additionalInfo to reach their maximum potential sizes simultaneously.
                    As per page 15 of Annex I - PIX Initiation Standards version 1.2.006.
                    */
    $tam_max_descr = 99 - (4 + 4 + 4 + 14 + strlen($chave_pix));
    if (strlen($descricao) > $tam_max_descr) {
        $descricao = substr($descricao, 0, $tam_max_descr);
    }
    $px[26][02] = $descricao;
}
$px[52] = "0000"; // Merchant Category Code “0000” or MCC ISO18245
$px[53] = "986"; // Currency, “986” = BRL: Brazilian real - ISO4217
if ($valor_pix > 0) {
    // Version 1.2.006 of Annex I - PIX Initiation Standards establishes the value field (54) as an optional field.
    $px[54] = $valor_pix;
}
$px[58] = "BR"; // “BR” – ISO3166-1 alpha 2 country code
$px[59] = $beneficiario_pix; // Beneficiary/receiver name. Maximum: 25 characters.
$px[60] = $cidade_pix; // City name where the transaction is made. Maximum 15 characters.
$px[62][05] = $identificador;
//   $px[62][50][00]="BR.GOV.BCB.BRCODE"; //Payment system specific template - GUI
//   $px[62][50][01]="1.2.006"; //Payment system specific template - versão
$pix = montaPix($px);
$pix .= "6304"; // Adds the CRC field at the end of the PIX string.
$pix .= crcChecksum($pix); // Calculates the CRC16 checksum and appends it to the end.
$linhas = round(strlen($pix) / 120) + 1;

?>
<link rel="stylesheet" href="pix-style.css">
<link rel="stylesheet" href="spinner.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">

<script>
    function copiar() {
        var copyText = document.getElementById("brcodepix");
        copyText.select();
        copyText.setSelectionRange(0, 99999); /* For mobile devices */
        document.execCommand("copy");
        alert("Código PIX Copiado!");
    }
</script>
<!-- Wrapper for all QR code related elements so we can hide them together -->
<div id="qrCodeWrapper">
    <div class="plata-card-lg" id="qrCodeContainer" style="height: 550px; min-height: 550px; box-sizing: border-box; display: flex; flex-direction: column;">
        <h3>Pedido Gerado</h3>
        <h4 style="color: var(--color-text-muted); font-weight: 500; margin-top: -10px; margin-bottom: 2px;">Tokens Plata Reservados</h4>
        <div style="text-align: center; margin: 4px 0 6px 0;">
            <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--color-text-muted); font-family: var(--font-family-main);">Aguardando Pagamento (R$ <?= $valor_pix_formatado; ?>)</span>
        </div>
        <div style="flex: 1; display: flex; flex-direction: column;">
            <div class="row">
                <div>
                    <textarea id="brcodepix" class="invisibled" rows="<?= $linhas; ?>" cols="130"><?= $pix; ?></textarea>
                </div>
            </div>
            
            <center>
            <?php
            ob_start();
            QRCode::png($pix, null, 'M', 5, 0);
            $imageString = base64_encode(ob_get_contents());
            ob_end_clean();
            // Displays the image directly in the browser encoded in base64.
            echo '<img src="data:image/png;base64,' . $imageString . '" style="width: 64%; height: auto; border-radius: var(--border-radius-button);"></center>';
            ?>
            <div style="color: var(--color-text-muted); font-weight: 500; font-size: 13px; text-align: center; margin: 4px 0;">Um email foi enviado</div>

            <hr width="95%" />
            <div>
                <center><button type="button" id="clip_btn" class="buttonBuyNow" data-toggle="tooltip" data-placement="top" onclick="copiar()">Pix Copia e Cola <svg viewBox="0 0 40 42" width="16" height="16" style="vertical-align: middle; margin-left: 6px; fill: currentColor; display: inline-block;"><path d="M38.1823 18.6122l-4.6009 -4.6009c-0.0968,0.036542 -0.206184,0.067155 -0.312059,0.067155l-2.10552 0c-1.0619,0 -2.11181,0.436084 -2.87097,1.16293l-5.99313 5.99446c-0.56749,0.56628 -1.32108,0.881243 -2.07467,0.881243 -0.742456,0 -1.47342,-0.30371 -2.00219,-0.809853l-6.01588 -6.01588c-0.768108,-0.783233 -1.81839,-1.21944 -2.88476,-1.23565l-2.61566 0c-0.097889,0.000121 -0.20691,-0.023716 -0.29887,-0.057838l-4.63103 4.61434c-1.11211,1.1132 -1.77664,2.70314 -1.77664,4.2932 0,1.56634 0.640816,3.10885 1.70876,4.22435l4.61857 4.61857c0.156453,0.035937 0.264869,0.008833 0.363,0.005566l2.58565 0.000121c1.07182,0 2.10564,-0.436205 2.8648,-1.16305l6.01588 -6.01588c1.08731,-1.08585 2.98204,-1.08719 4.06923,0l6.03318 5.95501c0.736527,0.735801 1.78511,1.18749 2.84991,1.20359l2.15223 0.000121c0.106843,-0.000121 0.221067,0.024079 0.317988,0.062194l4.59957 -4.59957c1.12215,-1.10969 1.78657,-2.69975 1.78657,-4.28981 0,-1.56622 -0.640816,-3.10873 -1.70864,-4.22423l-0.074415 -0.07018zm-28.9063 -6.31838c-0.005929,0 0.01694,0 0.01694,0 1.52642,0 3.03008,0.615043 4.10081,1.68589l6.04976 6.04504c0.203522,0.203401 0.495374,0.32549 0.787226,0.32549 0.287496,0 0.570636,-0.117733 0.775368,-0.313632l5.99434 -5.99446c1.09372,-1.09263 2.59739,-1.73066 4.1238,-1.73066l0.76109 0 -7.6132 -7.61308c-1.11259,-1.11175 -2.70266,-1.77616 -4.2926,-1.77616 -1.56634,0 -3.10885,0.640695 -4.22435,1.70864l-7.59432 7.59432 1.11514 0.068607zm21.8878 21.2019c0.008228,-0.000121 -0.014641,-0.000121 -0.014641,-0.000121 -1.52775,0 -3.05549,-0.638517 -4.12719,-1.71021l-5.99446 -5.99434c-0.213807,-0.204369 -0.507595,-0.324522 -0.796785,-0.324522 -0.28919,0 -0.574024,0.115676 -0.783112,0.311454l-6.02011 6.01794c-1.08114,1.08029 -2.58613,1.71868 -4.11388,1.71868l-1.21472 0 7.5942 7.59432c1.11259,1.11151 2.70266,1.77604 4.29272,1.77604 1.56622,0 3.10885,-0.640816 4.22423,-1.70876l7.61332 -7.6132 -0.659571 -0.067276z"/></svg></button></center>
            </div>
            <hr width="95%" />

            <?php
            echo "<div style='max-width: 330px; margin: 4px auto 0 auto; font-size: 13px; text-align: center; color: var(--color-text-main); line-height: 1.4; font-weight: 500;'>";
            echo "Válido até: " . $Expdate_front . " <span id='checkout-timer' class='timer-highlight' data-expiration='" . $ExpTimestamp . "' data-email='" . $emailUser . "'>10:00</span>";
            echo "</div>";
            ?>
            
            <div style="width: 100%; text-align: center; margin-top: 6px;">
                <p style="font-style: italic; font-size: 12px; color: var(--color-text-main); margin: 0; line-height: 1.35;">
                    Digitalize o QR Code via app de seu banco.<br>
                    Tokens (PLT) serão enviados pela Rede<br>
                    Polygon(137) após confirmação de pagamento.
                </p>
            </div>

            <div style="margin: auto 0;"></div>

            <hr width="95%" style="margin-top: 0;" />
            <a href="email?cancel=true" class="cancel-link" style="margin-top: 0;">Cancelar</a>
        </div>

    </div> <!-- End plata-card -->
    
</div> <!-- End qrCodeWrapper -->

<!-- Processing Container (Hidden by default) -->
    <div class="plata-card" id="processingContainer" style="display:none;">
        
        <br><br>
        <div style="position: relative; width: 160px; height: 160px; margin: 0 auto 20px;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; box-sizing: border-box; border: 6px solid #f3f3f3; border-top: 6px solid var(--color-primary); border-radius: 50%; animation: spin 1.5s linear infinite;"></div>
            
            <!-- Container dos Blocos -->
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: flex; gap: 8px; align-items: center; justify-content: center;">
                
                <!-- Bloco 1 -->
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width: 28px; height: 28px; animation: jump-spin-1 3s infinite ease-in-out;">
                  <polygon points="12 2 2 7 2 17 12 22 22 17 22 7 12 2"></polygon>
                  <polyline points="2 7 12 12 22 7"></polyline>
                  <line x1="12" y1="22" x2="12" y2="12"></line>
                </svg>

                <!-- Bloco 2 -->
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width: 28px; height: 28px; animation: jump-spin-2 3s infinite ease-in-out;">
                  <polygon points="12 2 2 7 2 17 12 22 22 17 22 7 12 2"></polygon>
                  <polyline points="2 7 12 12 22 7"></polyline>
                  <line x1="12" y1="22" x2="12" y2="12"></line>
                </svg>

                <!-- Bloco 3 -->
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width: 28px; height: 28px; animation: jump-spin-3 3s infinite ease-in-out;">
                  <polygon points="12 2 2 7 2 17 12 22 22 17 22 7 12 2"></polygon>
                  <polyline points="2 7 12 12 22 7"></polyline>
                  <line x1="12" y1="22" x2="12" y2="12"></line>
                </svg>

            </div>
        </div>
        
        <h3 style="color: var(--color-text-main); margin-bottom: 10px;">Pix Recebido!</h3>
        <p style="color: var(--color-text-muted); font-size: var(--font-size-small); font-family: var(--font-family-main);">Efetuando transferência na rede Polygon...</p>
    </div>

    <!-- Expired Container (Hidden by default) -->
    <div class="plata-card" id="expiredContainer" style="display:none;">
        <br>
        <div class="div-label-center" style="margin-bottom: 15px;">
            <label class="message-title-expired" style="font-size: 22px; font-weight: bold; color: var(--color-danger);">Pedido Expirado</label>
        </div>
        <p class="message-text" style="color: var(--color-text-muted); font-size: var(--font-size-small); margin-bottom: 20px; font-family: var(--font-family-main);">
            O tempo limite de 10 minutos expirou. O QR Code gerado não é mais válido.
        </p>
        <hr width="95%" class="hrline" style="margin: 15px auto;">
        <a href="index.php" class="buttonBuyNow btn-redirect">Novo Pedido</a>
        <hr width="95%" class="hrline" style="margin: 15px auto;">
        <div class="logo-container" style="margin-top: 15px;">
            <br>
            <img src="https://www.plata.ie/images/pix-full-logo.svg" class="logo-pix" alt="PIX Logo" style="max-width: 120px;">
            <br><br>
        </div>
    </div>

    <form id="expireForm" method="POST" action="expired" style="display:none;">
        <input type="hidden" id="userEmail" name="email" value="<?= htmlspecialchars($emailUser); ?>">
    </form>

    <input type="hidden" id="checkIdentify" value="<?= htmlspecialchars($identificador); ?>">
    <input type="hidden" id="checkAmount" value="<?= htmlspecialchars($amount); ?>">
    
    <script src="https://www.typofx.ie/.scr/jquery.min.js"></script>
    <script src="order.js?v=<?php echo time(); ?>"></script>
    <?php include __DIR__ . '/version.php'; ?>
    </body>

    </html>


   <?php unset($_SESSION['emailUser']); session_destroy(); ?>