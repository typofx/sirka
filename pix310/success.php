<?php
session_start();

// Pegar as variáveis da sessão com valores padrão de fallback
$brlAmount = isset($_SESSION['success_brl_amount']) ? number_format($_SESSION['success_brl_amount'], 2, '.', '') : '0.00';
$pltAmount = isset($_SESSION['success_plt_amount']) ? number_format($_SESSION['success_plt_amount'], 4, '.', '') : '0.0000';
$wallet = !empty($_SESSION['success_wallet']) ? $_SESSION['success_wallet'] : '0x2a4bd71ee240e6a40214e48570b848ea2d9d9f6c'; // Fallback 
$invoice = !empty($_SESSION['success_invoice']) ? $_SESSION['success_invoice'] : 'N/A';
$txHash = !empty($_SESSION['success_tx_hash']) ? $_SESSION['success_tx_hash'] : '0x7440c8c4a79d0d162668c943cfc4d208cf3e70bfa3c0c46ff537c30f5a01bb3d'; // Placeholder até o bot enviar

function maskHash($hash) {
    if (strlen($hash) > 20) {
        return substr($hash, 0, 10) . '....' . substr($hash, -10);
    }
    return $hash;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Transação Concluída - Plata Token</title>
    <link rel="stylesheet" href="pix-style.css">

</head>
<body class="success-page">
    <div style="display: flex; flex-direction: column; align-items: center; width: 100%;">

    <div class="plata-card-lg">
        
        <!-- Banner -->
        <div class="success-banner">
            Transação Efetuada
        </div>

        <!-- Content Body -->
        <div class="success-body">
            
            <!-- Document Icon -->
            <div class="success-icon-wrapper">
                <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="var(--color-success)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                    <path d="M9 16l2 2 4-4"></path>
                </svg>
            </div>

            <h2 class="success-title">Transação concluída</h2>

            <div class="success-conversion">
                <?php echo $brlAmount; ?> BRL 
                <span style="color: var(--color-text-muted);">➔</span> 
                <strong><?php echo $pltAmount; ?> PLT</strong>
            </div>
            
            <div class="success-wallet-address" style="line-height: 1.8;">
                <a href="https://polygonscan.com/address/<?php echo $wallet; ?>" target="_blank" style="color: var(--color-text-main); text-decoration: underline;">
                    <?php echo maskHash($wallet); ?>
                </a>
                <br>
                <a href="https://polygonscan.com/tx/<?php echo $txHash; ?>" target="_blank" style="color: var(--color-text-main); text-decoration: underline;">
                    TxHash: <?php echo maskHash($txHash); ?>
                </a>
            </div>

            <div class="success-invoice">
                Invoice: #<?php echo $invoice; ?>
                <br>

                <a href="#" class="download-invoice-link" onclick="window.print(); return false;" title="Download PDF">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Baixar recibo
                </a>
            </div>


            <button class="buttonBuyNow" onclick="window.location.href='https://typofx.ie/sirka/pauloalves/pix310/';" style="width: 100%; border: none; cursor: pointer; display: block; box-sizing: border-box;">
                Voltar ao Início
            </button>

        </div>
    </div>

    <?php include __DIR__ . '/version.php'; ?>
    </div>
</body>
</html>
