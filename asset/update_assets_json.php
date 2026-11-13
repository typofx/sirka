<?php

function update_assets_json($conn = null) {
    $should_close = false;

    if (!$conn) {
        if (!empty($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/.scr/conexao.php')) {
            include $_SERVER['DOCUMENT_ROOT'] . '/.scr/conexao.php';
        } elseif (file_exists(__DIR__ . '/conexao.php')) {
            include __DIR__ . '/conexao.php';
        }
        $should_close = true;
    }

    if (!$conn || $conn->connect_error) {
        return false;
    }

    $query = "SELECT id, nmid, name, ticker_symbol, decimal_value, contract_name, network, on_at_exmachina FROM granna80_bdlinks.assets";
    $result = $conn->query($query);

    if ($result) {
        $jsonData = [];
        $jsonNewData = [
            "updated" => (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z'),
            "chain" => []
        ];
        $jsonExmData = $jsonNewData;
        $cont = 1;
        $contNew = 1;

        while ($row = $result->fetch_assoc()) {
            $contract = trim($row['contract_name'] ?? '');

            $jsonData[] = [
                "id" => $cont,
                "nmid" => $row['nmid'],
                "name" => $row['name'],
                "ticker" => $row['ticker_symbol'],
                "contract" => $contract,
                "decimals" => (int)$row['decimal_value'],
                "network" => $row['network']
            ];
            $cont++;

            if (!empty($contract) && $contract !== '...') {
                $chain = strtolower(trim($row['network']));
                $entry = [
                    "id" => $contNew,
                    "name" => $row['name'],
                    "nmid" => $row['nmid'],
                    "ticker" => $row['ticker_symbol'],
                    "decimals" => (int)$row['decimal_value']
                ];
                $jsonNewData["chain"][$chain][strtolower($contract)] = $entry;
                if ((int)($row['on_at_exmachina'] ?? 0) === 1) {
                    if (!isset($jsonExmData["chain"][$chain])) {
                        $jsonExmData["chain"][$chain] = [];
                    }
                    $jsonExmData["chain"][$chain][] = strtolower($contract);
                }
                $contNew++;
            }
        }

        file_put_contents(__DIR__ . '/assets.json', json_encode(array_values($jsonData), JSON_PRETTY_PRINT));
        file_put_contents(__DIR__ . '/json-new.json', json_encode($jsonNewData, JSON_PRETTY_PRINT));
        file_put_contents(__DIR__ . '/json-exm.json', json_encode($jsonExmData, JSON_PRETTY_PRINT));
    }

    if ($should_close && $conn) {
        $conn->close();
    }

    return true;
}
