<? include_once ('/home2/granna80/%/env.php'); ?>
<? include_once $_SERVER['DOCUMENT_ROOT'] . '/.scr/conexao.php'; ?>
<? include_once ('plata-math.php'); ?>
<? include_once ('extract.php'); ?>

<?php
echo '<pre>';

$PLT_circulating_supply = 11299000992;

$json_wmatic_pool__0x0E1_671a6 = 'https://api.etherscan.io/v2/api?module=account&chainid=137&action=tokenbalance&contractaddress=0x0d500B1d8E8eF31E21C99d1Db9A6444d3ADf1270&address=0x0E145c7637747CF9cfFEF81b6A0317cA3c9671a6&tag=latest&apikey='.$API_KEY_ETHERSCAN;
$json_plata_pool__0x0E1_671a6 = 'https://api.etherscan.io/v2/api?module=account&chainid=137&action=tokenbalance&contractaddress=0xc298812164bd558268f51cc6e3b8b5daaf0b6341&address=0x0E145c7637747CF9cfFEF81b6A0317cA3c9671a6&tag=latest&apikey='.$API_KEY_ETHERSCAN;

$qtd_wmatic_pool__0x0E1_671a6 = number_format(json_decode(array(file_get_contents($json_wmatic_pool__0x0E1_671a6))[0],true)['result'] / (10 ** 18) ?? 0 , 5, '.', ',');
$qtd_plata_pool__0x0E1_671a6 = number_format(json_decode(array(file_get_contents($json_plata_pool__0x0E1_671a6))[0],true)['result'] / (10 ** 4) ?? 0 , 4, '.', ',');

$api_endpoint = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest?symbol=';

$cryptocurrency = 'POL';
$company_asset = 'PLT';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "X-CMC_PRO_API_KEY: {$API_KEY_COINMARKETCAP}\r\nAccept: application/json\r\n"
    ]
]);

$query = " SELECT * FROM granna80_bdlinks.assets WHERE network = 'fiduciary coin' ";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fiats[] = strval($row['ticker_symbol']);
    }

}   print_r($fiats); echo '<br>';

print_r("WMATICBRL: " . extract_rate_from_api($api_endpoint, $context, 'WMATIC', 'BRL')); echo '<br><br>';
print_r("WMATICEUR: " . extract_rate_from_api($api_endpoint, $context, 'WMATIC', 'EUR')); echo '<br><br>';
print_r("WMATICUSD: " . extract_rate_from_api($api_endpoint, $context, 'WMATIC', 'USD')); echo '<br><br>';

foreach ($fiats as $fiat) {
    
    usleep(250);

    ${$cryptocurrency.$fiat} = number_format((float)extract_rate_from_api($api_endpoint, $context, $cryptocurrency, $fiat), 8, '.', '');
    ${$fiat.$cryptocurrency} = number_format(1/(${$cryptocurrency.$fiat}), 8, '.', '');
    
    $prices_vs_usd[$cryptocurrency.$fiat] = ${$cryptocurrency.$fiat};
    $usd_vs_prices[$fiat.$cryptocurrency] = ${$fiat.$cryptocurrency};
    
    echo $cryptocurrency.$fiat . ' : ' . $prices_vs_usd[$cryptocurrency.$fiat]. '<br>';
    echo $fiat.$cryptocurrency . ' : ' . $usd_vs_prices[$fiat.$cryptocurrency]. '<br>';
}

//$usd_vs_prices['USDMATIC'] = $USDMATIC; 

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

$query = " SELECT * FROM granna80_bdlinks.assets WHERE network = 'polygon' AND ticker_symbol != '{$company_asset}' ";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $coins[] = strval($row['ticker_symbol']);
    }

}print_r($coins); echo '<br>';

foreach ($coins as $coin) {
    usleep(250);

    $rate_usd = (float) extract_rate_from_api($api_endpoint, $context, $coin, 'USD');
    if ($rate_usd > 0) {
        //${$coin.'USD'} = number_format((float)(extract_rate_from_api($api_endpoint, $context, $coin, 'USD' )), 8, '.', '');
        ${$coin . 'USD'} = number_format($rate_usd, 8, '.', '');
        ${'USD' . $coin} = number_format(1 / $rate_usd, 8, '.', '');

        $prices_vs_usd[strtoupper($coin) . 'USD'] = ${$coin . 'USD'};
        $usd_vs_prices['USD' . strtoupper($coin)] = ${'USD' . $coin};

        echo $coin . 'USD' . ' : ' . $prices_vs_usd[$coin . 'USD'] . '<br>';
        echo 'USD' . $coin . ' : ' . $usd_vs_prices['USD' . $coin] . '<br>';
    } else {
        echo $coin . 'USD' . ' : error (sem cotação / 429) <br>';
    }
}
//livecoinwatch
$plata_values = deploy_plata_rates($POLUSD, $POLEUR, $POLBRL, $qtd_plata_pool__0x0E1_671a6, $qtd_wmatic_pool__0x0E1_671a6, $PLT_circulating_supply);

//coinmarketcap
//$plata_values = deploy_plata_rates($MATICUSD, $EURSUSD, $BRZUSD, $qtd_plata_pool__0x0E1_671a6, $qtd_wmatic_pool__0x0E1_671a6, $PLTcirculatingSupply);

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
