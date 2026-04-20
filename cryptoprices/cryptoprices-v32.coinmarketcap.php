<?php include_once ('/home2/granna80/%/env.php'); ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/.scr/conexao.php'; ?>
<?php include ('plata-math.php'); ?>

<?php
echo '<pre>';

$PLTcirculatingSupply = 11299000992;

$json_wmatic_pool__0x0E1_671a6 = 'https://api.etherscan.io/v2/api?module=account&chainid=137&action=tokenbalance&contractaddress=0x0d500B1d8E8eF31E21C99d1Db9A6444d3ADf1270&address=0x0E145c7637747CF9cfFEF81b6A0317cA3c9671a6&tag=latest&apikey='.$API_KEY_ETHERSCAN;
$json_plata_pool__0x0E1_671a6 = 'https://api.etherscan.io/v2/api?module=account&chainid=137&action=tokenbalance&contractaddress=0xc298812164bd558268f51cc6e3b8b5daaf0b6341&address=0x0E145c7637747CF9cfFEF81b6A0317cA3c9671a6&tag=latest&apikey='.$API_KEY_ETHERSCAN;

$qtd_wmatic_pool__0x0E1_671a6 = number_format(json_decode(array(file_get_contents($json_wmatic_pool__0x0E1_671a6))[0],true)['result'] / (10 ** 18) ?? 0 , 5, '.', ',');
$qtd_plata_pool__0x0E1_671a6 = number_format(json_decode(array(file_get_contents($json_plata_pool__0x0E1_671a6))[0],true)['result'] / (10 ** 4) ?? 0 , 4, '.', ',');

$api_endpoint = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest?symbol=';

$cryptocurrency = 'POL';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "X-CMC_PRO_API_KEY: {$API_KEY_COINMARKETCAP}\r\nAccept: application/json\r\n"
    ]
]);

/*
$company_asset = 'PLT';
$coins_query = $conn->query("SELECT ticker_symbol FROM granna80_bdlinks.assets WHERE network = 'polygon' AND ticker_symbol != '{$company_asset}'");
if ($coins_query) {
    while ($asset_row = $coins_query->fetch_assoc()) {
        $coins[] = strtoupper($asset_row['ticker_symbol']);
    }
    $coins_query->free();
}
*/

function extract_rate_from_api_TEST($_api_endpoint, $_context, $_cryptocurrency, $_fiat) {
    return json_decode(file_get_contents($_api_endpoint.$_cryptocurrency.'&convert='.$_fiat, false, $_context), true)['data'][$_cryptocurrency]['quote'][$_fiat]['price'];
}

$query = " SELECT * FROM granna80_bdlinks.assets WHERE network = 'fiduciary coin' ";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fiats[] = strval($row['ticker_symbol']);
    }

}   print_r($fiats); echo '<br>';


//$url = $api_endpoint . '?symbol=' . $symbols . '&convert=USD';
//$response = json_decode(file_get_contents($url, false, $context), true);

print_r(extract_rate_from_api_TEST($api_endpoint, $context, 'POL', 'BRL')); echo '<br><br>';

foreach ($fiats as $fiat) {
    
    usleep(250);

    ${$cryptocurrency.$fiat} = number_format((float)extract_rate_from_api_TEST($api_endpoint, $context, $cryptocurrency, $fiat), 8, '.', '');
    ${$fiat.$cryptocurrency} = number_format(1/(${$cryptocurrency.$fiat}), 8, '.', '');
    
    $prices_vs_usd[$cryptocurrency.$fiat] = ${$cryptocurrency.$fiat};
    $usd_vs_prices[$fiat.$cryptocurrency] = ${$fiat.$cryptocurrency};
    
    echo $cryptocurrency.$fiat . ' : ' . $prices_vs_usd[$cryptocurrency.$fiat]. '<br>';
    echo $fiat.$cryptocurrency . ' : ' . $usd_vs_prices[$fiat.$cryptocurrency]. '<br>';
}

/*

    $EURUSD = number_format($usd_vs_prices['USDPOL'] / $usd_vs_prices['EURPOL'], 8, '.', '');
    $USDEUR = 1 / $EURUSD;
    
    $prices_vs_usd['EURUSD'] = $EURUSD;
    $usd_vs_prices['USDEUR'] = $USDEUR;
    echo 'EURUSD : ' . $EURUSD."<br>";
    echo 'USDEUR : ' . $USDEUR."<br>";

    $BRLUSD = number_format($usd_vs_prices['BRLPOL'] / $usd_vs_prices['USDPOL'], 8, '.', '');
    $USDBRL = 1 / $BRLUSD;
    $prices_vs_usd['BRLUSD'] = $BRLUSD;
    $usd_vs_prices['USDBRL'] = $USDBRL;
    echo 'BRLUSD : ' . $BRLUSD."<br>";
    echo 'USDBRL : ' . $USDBRL."<br>";


// Tickers do banco que diferem do símbolo real na CMC
/*$cmc_symbol_map = [
    'USDCE'  => 'USDC',
    'EURE'   => 'EURS',
    'BRLA'   => 'BRZ',
    'XAUT'   => 'XAUt',
    'MATIC'  => 'POL',
    'WMATIC' => 'POL',
];*/

// Monta lista de símbolos CMC sem duplicatas
foreach (($coins ?? []) as $coin) {
    $cmc_symbols[] = $cmc_symbol_map[$coin] ?? $coin;
}
//$cmc_symbols = array_unique($cmc_symbols);

$symbols = implode(',', $cmc_symbols);
$url = $api_endpoint . '?symbol=' . $symbols . '&convert=USD';





// Mapeia preço de volta para o ticker original do banco
foreach (($coins ?? []) as $coin) {
    $cmc_sym = $cmc_symbol_map[$coin] ?? $coin;
    $token_prices[$coin] = $response['data'][$cmc_sym]['quote']['USD']['price'] ?? 0;
}

$token_prices['MATIC'] = $token_prices['MATIC'] ?? ($token_prices['WMATIC'] ?? 0);
$token_prices['POL']   = ((float)($token_prices['POL'] ?? 0) > 0) ? $token_prices['POL'] : ($token_prices['MATIC'] ?? 0);
$token_prices['USDC']  = $token_prices['USDC'] ?? ($token_prices['USDC.E'] ?? 0);

foreach (($coins ?? []) as $coin){
    $usd_price = ($token_prices ?? [])[$coin] ?? 0;

    (is_numeric($usd_price))
        ? ${$coin.'USD'} = number_format($usd_price, 5, '.', ',')
        : ${$coin . 'USD'} = rtrim(rtrim(number_format($usd_price, 5, '.', ''), '0'), '.');

    ${'USD'.$coin} = ((${$coin.'USD'}) > 0)
        ? number_format((1/(${$coin.'USD'})) / ( (${$coin.'USD'}) > 1000 ? (10 ** 4) : 1), 8, '.', ',')
        : 0;

    $prices_vs_usd[$coin.'USD'] = round( (float)str_replace(',', '', ${$coin.'USD'}) , 5);
    $usd_vs_prices['USD'.$coin] = round((float)str_replace(',', '', ${'USD'.$coin}), 8);
}

$BRZUSD  = (float)($prices_vs_usd['BRZUSD']  ?? $prices_vs_usd['BRLUSD']  ?? 0);
$EURSUSD = (float)($prices_vs_usd['EURSUSD'] ?? $prices_vs_usd['EURUSD']  ?? 0);
$POLUSD  = (float)($prices_vs_usd['POLUSD']  ?? $prices_vs_usd['MATICUSD'] ?? $prices_vs_usd['WMATICUSD'] ?? 0);

$BRLUSD   = $BRZUSD;
$EURUSD   = $EURSUSD;
$MATICUSD = $POLUSD; // MATIC não retorna o valor nessa API, usar POL]
unset($usd_vs_prices['USDPOL']);

$USDEUR   = number_format((1 / $EURUSD)   / ($EURUSD   > 1000 ? (10 ** 3) : 1) ?? 0, 8, '.', ',');
$USDBRL   = number_format((1 / $BRZUSD)   / ($BRZUSD   > 1000 ? (10 ** 3) : 1) ?? 0, 8, '.', ',');
$USDMATIC = number_format((1 / $MATICUSD) / ($MATICUSD > 1000 ? (10 ** 3) : 1) ?? 0, 8, '.', ',');

$usd_vs_prices['USDMATIC'] = $USDMATIC;

$plata_values = deploy_plata_rates($MATICUSD, $EURSUSD, $BRZUSD, $qtd_plata_pool__0x0E1_671a6, $qtd_wmatic_pool__0x0E1_671a6, $PLTcirculatingSupply);

$output = [
    'last_updated_at' => gmdate('d-m-Y H:i:s') . ' UTC',
    'id'              => 'coinmarketcap v3',
    'prices_vs_usd'   => $prices_vs_usd,
    'usd_vs_prices'   => $usd_vs_prices,
    'plt_prices'      => $plata_values['plt_prices'],
    'plt_marketcap'   => $plata_values['plt_marketcap']
];

$json_out = json_encode($output, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR);

file_put_contents(__DIR__ . '/all_pricesV3.'.basename(preg_replace('/.cryptoprices-v3./i', '',__FILE__), ".php").'.json', $json_out);

echo '<pre>';
print_r($output);
echo '</pre>';
