<?php
// Attempt to fetch active coins from the database
$contracts = [];
try {
    $dbPath = __DIR__ . '/../../%/conexao2.php';
    if (file_exists($dbPath)) {
        require_once $dbPath;
        if (isset($pdo)) {
            $stmt = $pdo->prepare("SELECT ticker_symbol, contract_name FROM assets WHERE on_at_exmachina = true");
            $stmt->execute();
            $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        // Fallback for local testing if DB is not available
        $contracts = [
            ['ticker_symbol' => 'PLT'],
            ['ticker_symbol' => 'POL'],
            ['ticker_symbol' => 'DAI'],
            ['ticker_symbol' => 'EURe'],
            ['ticker_symbol' => 'BRLA']
        ];
    }
} catch (Exception $e) {
    // Security fallback
    $contracts = [
        ['ticker_symbol' => 'PLT'],
        ['ticker_symbol' => 'POL'],
        ['ticker_symbol' => 'DAI'],
        ['ticker_symbol' => 'EURe'],
        ['ticker_symbol' => 'BRLA']
    ];
}

// Keep the original case for the URL, because Linux (HostGator) is case-sensitive for folders
$tickers = array_map(function($c) { return $c['ticker_symbol']; }, $contracts);
?>
