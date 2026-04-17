<?php include ('/home2/granna80/%/env.php'); ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/.scr/conexao.php'; ?>
<?php include ('plata-math.php'); ?>

<?php

$PLTcirculatingSupply = 11299000992;

$json_wmatic_pool__0x0E1_671a6 = 'https://api.etherscan.io/v2/api?module=account&chainid=137&action=tokenbalance&contractaddress=0x0d500B1d8E8eF31E21C99d1Db9A6444d3ADf1270&address=0x0E145c7637747CF9cfFEF81b6A0317cA3c9671a6&tag=latest&apikey='.$API_KEY_ETHERSCAN;
$json_plata_pool__0x0E1_671a6 = 'https://api.etherscan.io/v2/api?module=account&chainid=137&action=tokenbalance&contractaddress=0xc298812164bd558268f51cc6e3b8b5daaf0b6341&address=0x0E145c7637747CF9cfFEF81b6A0317cA3c9671a6&tag=latest&apikey='.$API_KEY_ETHERSCAN;    

$qtd_wmatic_pool__0x0E1_671a6 = number_format(json_decode(array(file_get_contents($json_wmatic_pool__0x0E1_671a6))[0],true)['result'] / (10 ** 18) ?? 0 , 5, '.', ',');
$qtd_plata_pool__0x0E1_671a6 = number_format(json_decode(array(file_get_contents($json_plata_pool__0x0E1_671a6))[0],true)['result'] / (10 ** 4) ?? 0 , 4, '.', ',');

// Endpoints organizados https://www.plata.ie/bound-assets/
$api_endpoint = 'https://deep-index.moralis.io/api/v2.2/erc20/prices?chain=polygon';

// 0xeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee Ethereum contract - Ether Network
// 0xc02aaa39b223fe8d0a0e5c4f27ead9083c756cc2 Wrap Ethereum contract - Ether Network

// Coins e contratos vindos do banco
$company_asset = 'PLT';

$coins_query = $conn->query("SELECT ticker_symbol, contract_name FROM granna80_bdlinks.assets WHERE network = 'polygon' AND ticker_symbol != '{$company_asset}'");

while ($asset_row = $coins_query->fetch_assoc()) {
    $coins[] = strtoupper($asset_row['ticker_symbol']);
    $contracts[strtoupper($asset_row['ticker_symbol'])] = $asset_row['contract_name'];
}
$coins_query->free();

$context = stream_context_create(['http' => [
    'method'  => 'POST',
    'header'  => "Content-Type: application/json\r\nX-API-Key:{$API_KEY_MORALIS}\r\n",
    'content' => json_encode(['tokens' => array_map(
        fn($contract_address) => ['token_address' => $contract_address],
        array_values($contracts ?? [])
    )])
]]);

$response = json_decode(file_get_contents($api_endpoint, false, $context), true);

// atribui usdPrice para todos os tickers que usam o mesmo contrato.
foreach (($response ?? []) as $moralis_token) {
    $moralis_contract_address = $moralis_token['tokenAddress'];
    foreach (($contracts ?? []) as $ticker_symbol => $polygon_contract_address) {
        if (strtolower($polygon_contract_address) == $moralis_contract_address) {
            $token_prices[$ticker_symbol] = $moralis_token['usdPrice'];
        }
    }
}

if (isset(($token_prices ?? [])['WMATIC']) && !isset(($token_prices ?? [])['MATIC'])) {
    $token_prices['MATIC'] = $token_prices['WMATIC'];
}
if (
    isset(($token_prices ?? [])['MATIC']) &&
    (
        !isset(($token_prices ?? [])['POL']) ||
        (float)($token_prices['POL'] ?? 0) <= 0
    )
) {
    $token_prices['POL'] = $token_prices['MATIC'];
}
if (isset(($token_prices ?? [])['USDC.E']) && !isset(($token_prices ?? [])['USDC'])) {
    $token_prices['USDC'] = $token_prices['USDC.E'];
}

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

$fiat_symbols_query = $conn->query("SELECT ticker_symbol FROM granna80_bdlinks.assets WHERE network = 'fiduciary coin'");
while ($fiat_row = $fiat_symbols_query->fetch_assoc()) {
    $fiats[] = strtoupper($fiat_row['ticker_symbol']);
}
$fiat_symbols_query->free();

$fiats = array_values(array_unique($fiats ?? []));
//fallback para fiats que não tem preço na Moralis
$fiat_aliases = ['EUR' => 'EURS', 'BRL' => 'BRZ'];

foreach ($fiats as $fiat_symbol) {
    if ($fiat_symbol === 'USD') {
        continue;
    }

    $source_fiat_symbol = $fiat_symbol;
    if (!isset(($prices_vs_usd ?? [])[$source_fiat_symbol . 'USD']) && isset($fiat_aliases[$fiat_symbol])) {
        $source_fiat_symbol = $fiat_aliases[$fiat_symbol];
    }

    $fiat_to_usd_price = (float)(($prices_vs_usd ?? [])[$source_fiat_symbol . 'USD'] ?? 0);
    if ($fiat_to_usd_price <= 0) {
        continue;
    }

    $prices_vs_usd[$fiat_symbol . 'USD'] = round($fiat_to_usd_price, 8);
    $usd_vs_prices['USD' . $fiat_symbol] = round(1 / $fiat_to_usd_price, 8);
}

if (!isset($MATICUSD) && isset($WMATICUSD)) {
    $MATICUSD = $WMATICUSD;
}

$plata_values =  deploy_plata_rates($MATICUSD, $EURSUSD, $BRZUSD, $qtd_plata_pool__0x0E1_671a6, $qtd_wmatic_pool__0x0E1_671a6, $PLTcirculatingSupply);

$output = [
    'last_updated_at' => gmdate('d-m-Y H:i:s') . ' UTC',
    'id' => 'moralis v3',
    'prices_vs_usd' => $prices_vs_usd,
    'usd_vs_prices' => $usd_vs_prices,
    'plt_prices' => $plata_values['plt_prices'],
    'plt_marketcap' => $plata_values['plt_marketcap']
];

$json_out = json_encode($output, JSON_PRETTY_PRINT);
file_put_contents(__DIR__ . '/all_pricesV3.'.basename(preg_replace('/.cryptoprices-v3./i', '',__FILE__), ".php").'.json', $json_out);

if (php_sapi_name() !== 'cli') {
    echo '<h3>Conteúdo que será salvo no JSON ($output)</h3><pre>' . htmlspecialchars(print_r($output, true), ENT_QUOTES, 'UTF-8') . '</pre>';
}