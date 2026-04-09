<? include $_SERVER['DOCUMENT_ROOT'] . '/.scr/conexao.php'; ?>

<?

$jsonFile = __DIR__ . '/prices.json';

if (file_exists($jsonFile)) {

    $jsonContent = file_get_contents($jsonFile);
    $priceData = json_decode($jsonContent, true);

} else {

    echo 'json file not found';
    $priceData = [];
}

$livePrices = array_merge($priceData['prices_vs_usd'],$priceData['plt_prices']);

$query = "SELECT id, name, icon_path, ticker_symbol, decimal_value, contract_name, pool, network FROM granna80_bdlinks.assets";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ticker = $row['ticker_symbol'];

        $tickerUpper = strtoupper(trim($ticker));
        $normalizedTicker = preg_replace('/[^A-Z]/', '', $tickerUpper);
        $candidateTickers = [$tickerUpper, $normalizedTicker];
        $candidateTickers = array_values(array_unique(array_filter($candidateTickers)));

        foreach ($candidateTickers as $symbol) {

            $price = number_format((float)$livePrices[$symbol.'USD'], 8, '.', ',');

        }
        
        $network = strtolower($row['network']);
        $contract = $row['contract_name'];
        $id = $row['id'];
        $name = $row['name'];
        $icon = $row['icon_path'];
        $decimal_value = $row['decimal_value'];

        $contractLink = $network === 'polygon' && !empty($contract)
            ? "<a href='https://polygonscan.com/token/{$contract}' target='_blank'>" . substr($contract, 0, 6) . "..." . substr($contract, -4) . "</a>"
            : '...';

        $htmlRows .= "<tr>
            <td>{$id}</td>
            <td>{$name}</td>
            <td>" . (!empty($icon) ? "<img src='/images/assets-icons/" . basename($icon) . "' alt='Asset Icon' style='width:20px; height:20px;'>" : "") . "</td>
            <td>{$ticker}</td>
            <td>{$contractLink}</td>
            <td>{$decimal_value}</td>
            <td>{$row['network']}</td>
            <td class='assetprice'>" . $price . "</td>
        </tr>";
        
        //$cont++;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assets List</title>
    
    <style>
        <? include $_SERVER['DOCUMENT_ROOT'] . '/.scr/jquery.dataTables.min.css'; ?>
        <? include $_SERVER['DOCUMENT_ROOT'] . '/.scr/all.min.css'; ?>
        <? include 'asset.css'; ?>
        
    </style>
    
    <script>
        <? include $_SERVER['DOCUMENT_ROOT'] . '/.scr/jquery-3.5.1.min.js'; ?>
        <? include $_SERVER['DOCUMENT_ROOT'] . '/.scr/jquery.dataTables.min.js'; ?>
        <? include 'config.js'; ?>
    </script>

</head>

<body>

    <h1>Assets List</h1>
    <p><? echo 'Last Updated : ' . $priceData['last_updated_at'] ?></p>
    
    <table id="assetsTable" class="display">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Icon</th>
                <th>Ticker</th>
                <th>Contract</th>
                <th>Dec</th>
                <th>Network</th>
                <th class="assetprice">USD</th>
            </tr>
        </thead>
        <tbody>
            <?php echo $htmlRows; ?>
        </tbody>
    </table>

</body>

</html>
<? $conn->close(); ?>
