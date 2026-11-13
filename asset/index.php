<?php 
require_once $_SERVER['DOCUMENT_ROOT'] . '/plataforma/panel/is_logged.php'; 
require_once $_SERVER['DOCUMENT_ROOT'] . '/.scr/conexao.php'; 

if (!defined('ASSETS_LIB_PATH')) {
    define('ASSETS_LIB_PATH', 'https://www.typofx.ie/.scr/library/json/');
}

$prices = [];
$raw_assets = @file_get_contents('https://www.typofx.ie/sirka/alexscarano/asset.pc/assets.json');
$asset_list = $raw_assets ? json_decode($raw_assets, true) : [];

if (is_array($asset_list)) {
    foreach ($asset_list as $asset) {
        if (!empty($asset['contract']) && !empty($asset['nmid'])) {
            $price_url = ASSETS_LIB_PATH . $asset['nmid'] . '/USD.json';
            $price_content = @file_get_contents($price_url);
            if ($price_content !== false) {
                $price_data = json_decode($price_content, true);
                if (isset($price_data['pc'])) {
                    $prices[strtolower($asset['contract'])] = (float) $price_data['pc'];
                }
            }
        }
    }
}


function get_geckoterminal_price(string $_contractAddress): float {
    
    if (empty($_contractAddress)) return null;

    $api_url = "https://api.geckoterminal.com/api/v2/networks/polygon_pos/tokens/$_contractAddress";
    $data = json_decode(@file_get_contents($api_url), true);
    
    if ($data === false) return null;
 
    return (float)($data['data']['attributes']['price_usd']);
}

//echo get_geckoterminal_price('0xc298812164bd558268f51cc6e3b8b5daaf0b6341');

$query = "SELECT id, nmid, name, icon_path, ticker_symbol, decimal_value, contract_name, pool, network, on_at_exmachina FROM granna80_bdlinks.assets";
$result = $conn->query($query);

if ($conn->connect_error) die("Falha na conexao: " . $conn->connect_error);

$jsonData = [];
$jsonNewData = [
    "updated" => (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z'),
    "chain" => []
];
$jsonExmData = $jsonNewData;
$htmlRows = '';
$cont = 1;
$contNew = 1;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $ticker = $row['ticker_symbol'];
        $rawNetwork = $row['network'];
        $network = strtolower($rawNetwork ?? '');
        $contract = $row['contract_name'];
        $nmid = $row['nmid'];
        $name = $row['name'];
        $icon = $row['icon_path'];
        $decimal_value = (int)($row['decimal_value'] ?? 0);
        $on_at_exmachina = $row['on_at_exmachina'];
        
        
        $rowClass = '';
        if (!empty($row['pool']) && $row['pool'] != '0') {
            $rowClass = 'pastel-yellow';
        }

        if ($network === 'polygon' && !empty($contract)) {
            $contractKey = strtolower($contract);
            $price = $prices[$contractKey] ?? 0.0;
        }
        
        $jsonData[] = [
            "id" => $cont,
            "nmid" => $nmid,
            "name" => $name,
            "ticker" => $ticker,
            "contract" => $contract,
            "decimals" => $decimal_value,
            "network" => $rawNetwork
        ];

        $trimmedContract = trim($contract ?? '');
        if (!empty($trimmedContract) && $trimmedContract !== '...') {
            $jsonNewData["chain"][strtolower(trim($rawNetwork ?? ''))][strtolower($trimmedContract)] = [
                "id" => $contNew,
                "name" => $name,
                "nmid" => $nmid,
                "ticker" => $ticker,
                "decimals" => $decimal_value
            ];
            if ((int)($on_at_exmachina ?? 0) === 1) {
                $networkKey = strtolower(trim($rawNetwork ?? ''));
                if (!isset($jsonExmData["chain"][$networkKey])) {
                    $jsonExmData["chain"][$networkKey] = [];
                }
                $jsonExmData["chain"][$networkKey][] = strtolower($trimmedContract);
            }
            $contNew++;
        }
        $explorers = ['polygon' => 'https://polygonscan.com/token/', 'ethereum' => 'https://etherscan.io/token/', 'bnb' => 'https://bscscan.com/token/'];
        $trimmed = trim($contract ?? '');
        $contractLink = ($trimmed !== '' && $trimmed !== '...' && isset($explorers[$network]))
            ? "<a href='{$explorers[$network]}{$trimmed}' target='_blank'>" . substr($trimmed, 0, 6) . "..." . substr($trimmed, -4) . "</a>"
            : '...';

        $htmlRows .= "<tr class='{$rowClass}'>
            <td>{$id}</td>
            <td>{$name}</td>
            <td>" . (!empty($icon) ? "<img src='/images/assets-icons/" . basename($icon) . "' alt='Asset Icon' style='width:20px; height:20px;'>" : "") . "</td>
            <td>{$ticker}</td>
            <td>{$nmid}</td>
            <td>{$contractLink}</td>
            <td>{$decimal_value}</td>
            <td>{$rawNetwork}</td>
            <td class='fiat-price' data-nmid='{$nmid}' data-ticker='{$ticker}'>". number_format((float)str_replace(',', '', $prices[strtolower($contract)] ?? 0), 8) ."</td>
            <td>{$on_at_exmachina}</td>
            <td>
                <a href='assets.edit.php?id={$id}'><img src='https://www.typofx.ie/.scr/.img/icon-edit.svg' style='height:18'></a> 
                <a href='assets.delete.php?id={$id}' onclick='return confirmDeleteAsset({$id});'><img src='https://www.typofx.ie/.scr/.img/icon-delete.svg' style='height:18'></a>
            </td>
        </tr>"; 
        $cont++;
    }
}
file_put_contents(__DIR__ . '/assets.json', json_encode(array_values($jsonData), JSON_PRETTY_PRINT));
file_put_contents(__DIR__ . '/json-new.json', json_encode($jsonNewData, JSON_PRETTY_PRINT));
file_put_contents(__DIR__ . '/json-exm.json', json_encode($jsonExmData, JSON_PRETTY_PRINT));

$fiatQuery = "SELECT id, ticker FROM granna80_bdlinks.fiat ORDER BY id ASC";
$fiatResult = $conn->query($fiatQuery);
$tickers = [];
if ($fiatResult && $fiatResult->num_rows > 0) {
    while ($fRow = $fiatResult->fetch_assoc()) {
        $rawT = trim($fRow['ticker'] ?? '');
        if ($rawT !== '' && !in_array($rawT, $tickers, true)) {
            $tickers[] = $rawT;
        }
    }
}

$optionsHtml = '';
foreach ($tickers as $t) {
    $escapedTicker = htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
    $selected = ($t === 'USD') ? 'selected' : '';
    $optionsHtml .= "<option value='{$escapedTicker}' {$selected}>{$escapedTicker}</option>";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assets List</title>
    <link rel="stylesheet" href="https://www.typofx.ie/.scr/dataTables.min.css">
    <link rel="stylesheet" href="asset.style.css?v=<?php echo time(); ?>">
    <script src="https://www.typofx.ie/.scr/jquery-3.5.1.js"></script>
    <script src="https://www.typofx.ie/.scr/jquery.dataTables.min.js"></script>
    <script src="asset.jquery.js?v=<?php echo time(); ?>"></script>
    <script>
    function confirmDeleteAsset(id) {
        if (!confirm("Are you sure?")) return false;
        location.href = 'assets.delete.php?id=' + id + (confirm("Do you also want to delete the folder?") ? '&delete_folder=1' : '');
        return false;
    }
    </script>
</head>
<body>

    <a href="/plataforma/panel/">[Back]</a>
    <a href="javascript:window.location.reload(true)">[Refresh]</a>
    <a href="assets.add.php">[Add]</a>
    <a href="assets.json?v=<?php echo time(); ?>" target="_blank">[JSON]</a>
    <a href="json-new.json?v=<?php echo time(); ?>" target="_blank">[JSON2]</a>
    <a href="json-exm.json?v=<?php echo time(); ?>" target="_blank">[JSON-EXM]</a>
    <a href="/fiats/fiat.add.php">[Add Fiat]</a>
    <a href="https://typofx.ie/sirka/murilofurtado/fiats/index.php">[Fiats]</a>
    <label style="margin-left: 10px; cursor: pointer; user-select: none;">
        <input type="checkbox" id="showFiatsCheckbox" style="vertical-align: middle; cursor: pointer;"> Show Fiats
    </label>
    <h1>Assets List</h1>
    <table id="assetsTable" class="display" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Icon</th>
                <th>Ticker</th>
                <th>NameID</th>
                <th>Contract</th>
                <th>Dec</th>
                <th>Network</th>
                <th class="th-price">
                    Price
                    <select id="priceCurrencySelect">
                        <?php echo $optionsHtml; ?>
                    </select>
                </th>
                <th>EXM</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php echo $htmlRows; ?>
        </tbody>
    </table>

</body>
</html>