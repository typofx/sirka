<?php
session_start();

// Checks if the session is active and if the last activity was less than 10 minutes ago
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 600)) {
    // If the last activity was more than 5 minutes ago, destroy the session and redirect to the home page
    session_unset();     // removes all session variables
    session_destroy();   // destroys the session
    header("Location: index.php");
    exit();
}

// Updates the last activity timestamp to the current timestamp
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION['user'])) {
    // If the session has not started, redirect back to the home page
    header("Location: index.php");
    exit();
}

if (isset($_POST['emailUser'])) {
    $_SESSION['emailUser'] = $_POST['emailUser'];
 
    $emailUser = $_SESSION['emailUser'];
    //echo 'Email received: ' . htmlspecialchars($emailUser);
} else {
    header("Location: email.php"); // Replace "previous_page.php" with the desired URL
    exit();
}


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, user-scalable=no">
    <meta charset="utf-8">
    <meta name="keywords" content="pix, qrcode pix, qr code, br code, brcode pix, pix copia e cola" />
    <script src="https://cdn.jsdelivr.net/npm/web3@latest/dist/web3.min.js"></script> 
    <script src="https://www.typofx.ie/.scr/jquery-3.7.1.min.js" type="text/javascript"></script>
    <link rel="stylesheet" href="https://www.typofx.ie/.scr/all.min.css">
    <style>
        .invisibled {
            font-size: 0px;
            text-align: center;
            border-style: none;
            resize: none;
            width: 0px;
            height: 0px;
        }
    </style>
    <title>Plata Token - Gerador de QR Code do PIX</title>
</head>

<body>
    
    <html lang="">
<link rel="stylesheet" href="pix-style.css">

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">


<head>
</head>
<body>
    
    <div class="plata-card-lg" style="height: 550px; min-height: 550px; box-sizing: border-box; display: flex; flex-direction: column;">
<h3>exmachina lite</h3>
<div style="flex: 1; display: flex; flex-direction: column;">
    
    <form id="form1" method="post" action="summary.php" style="flex: none;">
        <?php
        date_default_timezone_set('America/Sao_Paulo');
        $date = date("H:i:s \B\R\T d/m/Y");
        $Expdate = time() + 600; // 10 minutes in the future
        $Expdate = date("H:i:s \B\R\T d/m/Y", $Expdate);
        $_POST["Expdate"] = $Expdate;
        ?>
        <b style="color: var(--color-text-main); font-family: var(--font-family-main);"><?php echo $emailUser;  ?></b><br><br>

        <div class="div-label" style="margin-top: 10px; margin-bottom: 2px;"><label for="valorpix">Valor a Pagar (BRL)</label></div>
        <div>
            <input type="number" id="valorpix" name="valorpix" size="15" autocomplete="off" maxlength="13" step="0.01" min="0.05" value="<?= $_POST["valorpix"] ?? ''; ?>" onkeyup="BRLexec()" onkeypress="BRLexec()" onclick="select()" onfocusout="addZeroBRL()" required>
        </div>

        <div class="div-label" style="margin-top: 10px; margin-bottom: 2px;"><label for="PLTwanted">Tokens Plata Previstos (PLT)</label></div>
        <div>
            <input type="number" id="PLTwanted" name="PLTwanted" size="15" step="0.0001" autocomplete="off" maxlength="13" min="0.0001" value="<?= $_POST["PLTwanted"] ?? ''; ?>" onkeyup="PLTexec()" onkeypress="PLTexec()" onclick="select()" required>
        </div>

        <div class="div-label" style="margin-top: 10px; margin-bottom: 2px;"><label for="web3wallet">Carteira EVM / Polygon (137)</label></div>
        <div>
            <input type="text" id="web3wallet" name="web3wallet" placeholder="0x..." size="60" maxlength="42" value="<?= $_POST["web3wallet"] ?? ''; ?>" onclick="this.select();" oninput="formatEtherWallet(this)" onfocusout="isValidEtherWallet()" autocomplete="off" spellcheck="false" autocorrect="off" autocapitalize="off" pattern="^0x[a-fA-F0-9]{40}$" title="Endereço de carteira Polygon (EVM) com 42 caracteres iniciado por 0x" required>
        </div>
        <div style="display: none;">
            <label for="identificador">Identificador</label>
            <input type="text" id="identificador" name="identificador" value="<?php echo (rand(10000000, 99999999)); ?>" required>
        </div>

        <hr width="95%" />
        <button type="submit" name="verify_code" class="buttonBuyNow">Continuar <i class="fas fa-check"></i></button>
        <hr width="95%" />
    </form>

    <?php //include '../en/mobile/price.php'; ?>


    <div style="margin: auto 0;">
        <div id="rate-pair-display" style="font-weight: 600; font-size: 14px; margin-bottom: 5px; color: #2F2F2F;">
            1 BRL = XXXX PLT
        </div>
        <?php
        date_default_timezone_set('America/Sao_Paulo');
        echo "<div style='max-width: 330px; margin: 0 auto; font-size: 13px; text-align: center; color: var(--color-text-main); line-height: 1.5;'>";
        echo "Válido Até: <span id='countdown-timer'>". $Expdate . "</span>";
        echo "</div>";
        ?>
    </div>
    
    <hr width="95%" style="margin-top: 0;" />
    <a href="email?cancel=true" class="cancel-link" style="margin-top: 0;">Cancelar</a>
    </div>
    </div>
    <?php include __DIR__ . '/version.php'; ?>
    <script src="order.js?v=<?php echo time(); ?>"></script>


</body>

</html>
