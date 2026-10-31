<?php
session_start();

if (!isset($_SESSION['emailUser']) || !isset($_POST['valorpix'])) {
    header("Location: email.php");
    exit();
}

$web3wallet = $_POST['web3wallet'] ?? '';
if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $web3wallet)) {
    header("Location: order.php");
    exit();
}

$emailUser = $_SESSION['emailUser'];
$valor_pix = number_format((float)$_POST['valorpix'], 2, '.', ',');
$PLTwanted = number_format((float)$_POST["PLTwanted"], 4, '.', ',');
$identificador = $_POST['identificador'];

date_default_timezone_set('America/Sao_Paulo');
$date_front = date("H:i:s \B\R\T d/m/Y");
$ExpTimestamp = time() + 600; // 10 minutes in the future
$Expdate_front = date("H:i:s \B\R\T d/m/Y", $ExpTimestamp);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta name="viewport" content="width=device-width, user-scalable=no">
    <meta charset="utf-8">
    <title>Plata Token - Resumo do Pedido</title>
    <link rel="stylesheet" href="https://www.typofx.ie/.scr/all.min.css">
    <link rel="stylesheet" href="pix-style.css">
    <style>
        .summary-box {
            padding: 5px 0px;
            text-align: left;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 0;
        }
        .summary-item {
            margin-bottom: 0;
        }
        .summary-item:last-child {
            margin-bottom: 0;
        }
        .summary-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--color-text-muted);
            margin-bottom: 2px;
            font-family: var(--font-family-main);
        }
        .summary-value {
            display: block;
            font-size: 14px;
            color: var(--color-text-main);
            word-break: break-all;
            font-family: var(--font-family-main);
            font-weight: 500;
        }
        hr {
            margin: 12px 0 !important;
        }
    </style>
</head>
<body>
    <div class="plata-card-lg" style="height: 550px; min-height: 550px; box-sizing: border-box; display: flex; flex-direction: column;">
        <h3>Pedido reservado</h3>
        <h4 style="color: var(--color-text-muted); font-weight: 500; margin-top:-10px;">Aguardando confirmação</h4>
        
        <div style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
            <div class="summary-box">
                <div class="summary-item">
                    <span class="summary-label">Ordem Executada Em</span>
                    <span class="summary-value"><?php echo $date_front; ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Valor Aguardado (BRL)</span>
                    <span class="summary-value">R$ <?php echo $valor_pix; ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Tokens Plata Previstos (PLT)</span>
                    <span class="summary-value"><?php echo $PLTwanted; ?> PLT</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Carteira Web3 (Polygon 137)</span>
                    <span class="summary-value"><a href="https://polygonscan.com/address/<?php echo htmlspecialchars($web3wallet); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--color-text-main); text-decoration: underline;"><?php echo htmlspecialchars($web3wallet); ?></a></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Identificador</span>
                    <span class="summary-value">#TY<?php echo htmlspecialchars($identificador); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Orçamento Válido Até</span>
                    <span class="summary-value"><?php echo $Expdate_front; ?></span>
                </div>
            </div>

            <form id="form3" method="post" action="0xc298812164bd558268f51cc6e3b8b5daaf0b6341.php" style="width: 100%; flex: none;">
                <input type="hidden" name="emailUser" value="<?php echo htmlspecialchars($emailUser); ?>">
                <input type="hidden" name="valorpix" value="<?php echo htmlspecialchars($_POST['valorpix']); ?>">
                <input type="hidden" name="PLTwanted" value="<?php echo htmlspecialchars($_POST['PLTwanted']); ?>">
                <input type="hidden" name="web3wallet" value="<?php echo htmlspecialchars($_POST['web3wallet']); ?>">
                <input type="hidden" name="identificador" value="<?php echo htmlspecialchars($_POST['identificador']); ?>">
                <input type="hidden" name="emailUser" value="<?php echo htmlspecialchars($_POST['emailUser'] ?? $_SESSION['emailUser']); ?>">
                
                <hr width="95%" />
                <button type="submit" class="buttonBuyNow">Confirmar</button>
            </form>

            <hr width="95%" style="margin-top: 0;" />
            <a href="email?cancel=true" class="cancel-link" style="margin-top: 0;">Cancelar</a>
        </div>
    </div>
    
    <?php include __DIR__ . '/version.php'; ?>
</body>
</html>
