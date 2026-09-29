<? ob_start(); ?>
<? require_once $_SERVER['DOCUMENT_ROOT'] . '/.scp/plt.prices.v321.livecoinwatch.php'; ?>
<? ob_end_clean(); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liquidity Position</title>

    <link rel="stylesheet" href="https://www.typofx.ie/.scr/all.min.css">
    <link rel="stylesheet" href="https://www.typofx.ie/.scr/jquery.dataTables.min.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    
    <link rel="stylesheet" href="https://www.plata.ie/es/styleMain.css">
    <link rel="stylesheet" href="style.css">

    <script src="https://www.typofx.ie/.scr/jquery-3.5.1.min.js"></script>
    <script src="https://www.typofx.ie/.scr/jquery.dataTables.min.js"></script>
    <script src="script.liquidity.position.js"></script>
</head>
	

<body>

<?php

$json1 = 'https://typofx.ie/plataforma/panel/lp-contracts/lp_contracts.json';
$json2 = 'https://typofx.ie/plataforma/panel/order-book/order_book_data.json';

$json3_file_list_array = scandir($_SERVER['DOCUMENT_ROOT'] . '/.scr/library/json', SCANDIR_SORT_DESCENDING);
$json3 = 'https://www.typofx.ie/.scr/library/json/'.$json3_file_list_array[0];

foreach ($json3_file_list_array as $json3_file) {
        
        if ( substr($json3_file, 4, -18) == date("Y-m-d", strtotime(substr($json3, 44, -18). ' - 1 days')) ) {
            $json4 = 'https://www.typofx.ie/.scr/library/json/'.$json3_file;
            break;
        } 

}


function carregarJson($url) {
    $dados = @file_get_contents($url);

    if (!$dados) {
        return [];
    }

    $json = json_decode($dados, true);
    return is_array($json) ? $json : [];
}

$contracts1 = carregarJson($json1);
$contracts2 = carregarJson($json2);
$plt_rate = carregarJson($json3);
$plt_rate_yesterday = carregarJson($json4);

$contracts = array_merge($contracts1, $contracts2);

usort($contracts, function($a, $b) {
    $liqA = $a['liquidity'] ?? 0;
    $liqB = $b['liquidity'] ?? 0;

    return $liqB <=> $liqA;
});

$totalLiquidity = 0;
$lastTimestamp = time();

foreach ($contracts as $item) {
    if (isset($item['total_liquidity'])) {
        $totalLiquidity += (float)$item['total_liquidity'];
    }

    if (isset($item['timestamp'])) {
        $lastTimestamp = $item['timestamp'];
    }
}

if ($totalLiquidity == 0) {
    foreach ($contracts as $item) {
        $totalLiquidity += (float)($item['liquidity'] ?? 0);
    }
}

date_default_timezone_set('UTC');

$totalLiquidityFormatado = number_format($totalLiquidity, 4, '.', ',');
$lastUpdate = date("D, jS F Y H:i:s", $lastTimestamp) . " UTC";

// =========================================================================
// --- INFOBOX CONFIGURATION & DATA ---
// Modify the variables below to customize the values displayed in the Info Box.
// =========================================================================
$contractAddress  = $contractAddress ?? '0xC29882a1e969018446A3102434DE27B75c616341';
$bookValue        = $bookValue ?? 50000; // Divisor for calculating the Market-to-Book Ratio

// If variables are already loaded from price.php, preserve them;
// otherwise, define the default fallback/mock values.
$PLTUSD           = $plt_rate['plt_prices']['PLTUSD'];
$PLT24hVariation  = $plt_rate['plt_prices']['PLTUSD'] / $plt_rate_yesterday['plt_prices']['PLTUSD'];
$PLTmarketcap     = number_format($plt_rate['plt_marketcap']['USD'], 4);

// --- INFOBOX DATA CALCULATION & PROCESSING ---
$shortAddress = substr($contractAddress, 0, 8) . '...' . substr($contractAddress, -5);

// Format PLT USD price to exactly 10 decimal places
$formattedPLTUSD = number_format((float)$PLTUSD, 8, '.', ',');

// 24h percentage variation
$variationClass = ((float)$PLT24hVariation >= 0) ? 'positive' : 'negative';
$variationSign = ((float)$PLT24hVariation >= 0) ? '+' : '';
$formattedVariation = '(24h: ' . $variationSign . number_format($PLT24hVariation, 2, '.', '') . '%)';

// Market-to-Book Ratio
$marketToBookRatio = ($bookValue > 0) ? ($totalLiquidity / $bookValue) : 0;
$marketToBookFormatted = number_format($marketToBookRatio, 2, '.', '');
?>

<section class="top-area">
    <h1>$PLT Liquidity Position</h1>

    <div class="info-box">
        <!-- Header: Logo, Name, Address -->
        <div class="info-header">
            <img class="info-logo" src="https://www.plata.ie/images/platasmall.svg" alt="Plata">
            <div class="token-details">
                <div class="info-title">
                    <span class="token-name">Plata Token</span>
                    <span class="token-symbol">PLT</span>
                </div>
                <div class="network-row">
                    <span class="network-badge">
                        <i class="fa-solid fa-link network-icon"></i>
                        Polygon: <span class="token-address" id="contractAddress"><?php echo htmlspecialchars($shortAddress); ?></span>
                        <i class="fa-regular fa-copy copy-icon" id="copyAddressBtn" data-address="<?php echo htmlspecialchars($contractAddress); ?>" title="Copy Address"></i>
                        <img class="metamask-fox" src="./.img/MetaMask-icon.svg" alt="MetaMask">
                    </span>
                </div>
                <div class="price-row">
                    <span class="info-price"><?php echo htmlspecialchars($formattedPLTUSD); ?> USD</span>
                    <span class="info-variation <?php echo htmlspecialchars($variationClass); ?>"><?php echo htmlspecialchars($formattedVariation); ?></span>
                </div>
            </div>
        </div>

        <!-- Secondary Metrics list -->
        <div class="metrics-list">
            <div class="metric-item">
                <span class="metric-label">Market Capitalization :</span>
                <span class="metric-value"><?php echo htmlspecialchars($PLTmarketcap); ?> USD</span>
            </div>
            <div class="metric-item">
                <span class="metric-label">Total Asset Liquidity :</span>
                <span class="metric-value"><?php echo htmlspecialchars($totalLiquidityFormatado); ?> USD</span>
            </div>
            <div class="metric-item">
                <span class="metric-label">Market-to-Book Ratio :</span>
                <span class="metric-value"><?php echo htmlspecialchars($marketToBookFormatted); ?></span>
            </div>
            <div class="metric-item">
                <span class="metric-label">Last Updated :</span>
                <span class="metric-value"><?php echo htmlspecialchars($lastUpdate); ?></span>
            </div>

        </div>
    </div>
</section>



<main class="table-area">

    <table id="example" class="display">
        <thead>
            <tr>
                <th>#</th>
                <th>Ticker</th>
                <th>Pair</th>
                <th>Contract</th>
                <th>Exchange</th>
                <th>Liquidity (USD)</th>
            </tr>
        </thead>

        <tbody>
            <?php
            $contador = 1;

            foreach ($contracts as $item) {
                $visivel = $item['visible'] ?? true;

                if (!isset($item['id']) || !$visivel) {
                    continue;
                }

                $pair = $item['pair'] ?? '-';
                $contract = $item['contract'] ?? '';
                $exchange = $item['exchange'] ?? '-';
                $liquidity = $item['liquidity'] ?? 0;

                echo '<tr>';
                echo '<td>' . $contador . '</td>';
                echo '<td class="ticker-cell" data-pair="' . htmlspecialchars($pair) . '"></td>';
                echo '<td>' . htmlspecialchars($pair) . '</td>';

                if ($contract != '') {
                    $contractCurto = substr($contract, 0, 8) . '...' . substr($contract, -6);

                    if (filter_var($contract, FILTER_VALIDATE_URL)) {
                        $link = $contract;
                    } else {
                        $link = 'https://polygonscan.com/address/' . $contract;
                    }

                    echo '<td><a href="' . htmlspecialchars($link) . '" target="_blank">' . htmlspecialchars($contractCurto) . '</a></td>';
                } else {
                    echo '<td>-</td>';
                }

                echo '<td class="exchange-cell" data-exchange="' . htmlspecialchars($exchange) . '">' . htmlspecialchars($exchange) . '</td>';
                echo '<td><b>' . number_format((float)$liquidity, 2, '.', ',') . '</b></td>';
                echo '</tr>';

                $contador++;
            }
            ?>
        </tbody>
    </table>

</main>

</body>
</html>