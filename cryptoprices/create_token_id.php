<? include $_SERVER['DOCUMENT_ROOT'] . '/.scr/conexao.php'; ?>
<?php require_once(__DIR__ . '/json-helper.php'); ?>
<?php

function extract_coin_id_from_api($_coin) {
    //return json_decode(@file_get_contents("https://api.coinpaprika.com/v1/search?q={$_coin}&c=currencies&id&modifier=symbol_search&limit=1"), true)['currencies'][0]['id'];
    return json_decode(@file_get_contents("https://api.coinpaprika.com/v1/search?q=".$_coin), true)['currencies'][0]['id'];
}

$company_asset = 'PLT';

$query = $conn->query(" SELECT * FROM granna80_bdlinks.assets WHERE network = 'polygon' AND ticker_symbol != '{$company_asset}' ");

if ($query->num_rows > 0) {
    while ($row = $query->fetch_assoc()) {
        $coins[] = $row['ticker_symbol'] . " : ". extract_coin_id_from_api($row['ticker_symbol']);
    }
}

//print_r($coins);

// Salva JSON
$token_map = [];
foreach ($coins as $entry) {
    [$sym, $id] = explode(' : ', $entry, 2);
    $token_map[strtoupper(trim($sym))] = trim($id);
}

$json_dir = __DIR__ . '/json';
if (!is_dir($json_dir)) mkdir($json_dir, 0755, true);

file_put_contents(
    $json_dir . '/coinpaprika_token_ids.json',
    json_encode(['last_updated_at' => gmdate('d-m-Y H:i:s') . ' UTC', 'token_ids' => $token_map], JSON_PRETTY_PRINT)
);
echo "OK : ".gmdate('d/m/y H:i:s') . " UTC";
?>
