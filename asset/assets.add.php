<?php include $_SERVER['DOCUMENT_ROOT'] . '/plataforma/panel/is_logged.php';?>
<?php

require __DIR__ . '/vendor/autoload.php'; // Path to Composer autoload

use Web3\Web3;
use Web3\Contract;
use Web3\Providers\HttpProvider;

// Function to handle contract response
function handleResponse($result)
{
    if (is_array($result)) {
        $result = reset($result); // Get the first value from the array
    }
    return $result;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Select Infura endpoint URL based on the selected network
    $network = $_POST['network'];
    $infuraProjectId = '55525f35a6194564837bdb0f6d842255'; // Replace with your Infura project ID

    if ($network == 'ethereum') {
        $infuraUrl = "https://mainnet.infura.io/v3/{$infuraProjectId}";
    } elseif ($network == 'bnb') {
        $infuraUrl = "https://bsc-dataseed.bnbchain.org";
    } elseif ($network == 'polygon') {
        $infuraUrl = "https://polygon-mainnet.infura.io/v3/{$infuraProjectId}";
    } else {
        die('Invalid network selected.');
    }

    // Token contract you want to query
    $contractAddress = trim($_POST['contract_address'] ?? ''); // Contract address sent by the form

    // Create an instance of the HTTP provider for Infura
    // web3.php defaults to a 1s timeout, too short for most RPC calls
    $httpProvider = new HttpProvider($infuraUrl, 15);

    // Create an instance of the Web3 object connected to Infura
    $web3 = new Web3($httpProvider);

    // Ethereum contract ABI you want to interact with (example of a USDC contract)
    $contractABI = '[{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"symbol","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"decimals","outputs":[{"name":"","type":"uint8"}],"payable":false,"stateMutability":"view","type":"function"}]';

    // Create an instance of the contract with the HTTP provider and ABI
    $contract = new Contract($web3->provider, json_decode($contractABI, true));

    // Initialize variables to store results
    $contract_name = '';
    $ticker_symbol = '';
    $decimal_value = '';

    // Methods and parameters to call directly
    $methods = [
        'name' => [],       // name method without parameters
        'symbol' => [],     // symbol method without parameters
        'decimals' => [],   // decimals method without parameters
    ];

    // Loop to call each method
    foreach ($methods as $method => $params) {
        $contract->at($contractAddress)->call($method, $params, function ($err, $result) use ($method, &$contract_name, &$ticker_symbol, &$decimal_value) {
            if ($err !== null) {
                echo 'Error fetching ' . $method . ': ' . $err->getMessage() . '<br>';
                return;
            }

            if ($method == 'name') {
                $contract_name = handleResponse($result);
            } elseif ($method == 'symbol') {
                $ticker_symbol = handleResponse($result);
            } elseif ($method == 'decimals') {
                $decimal_value = handleResponse($result);
            }
        });
    }

    // Abort instead of saving an empty record when the on-chain lookup fails
    if ($contract_name === '' || $ticker_symbol === '' || $decimal_value === '') {
        $_SESSION['message'] = "Error: could not read name/symbol/decimals from {$contractAddress} on {$network}. Check the address and network.";
        echo "<script>window.location.href = 'assets.add.php';</script>";
        exit();
    }
    $decimal_value = (string)$decimal_value;

    // Connect to the database
    if (!isset($conn) || !$conn) {
        if (!empty($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/.scr/conexao.php')) {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/.scr/conexao.php';
        } else {
            include 'conexao.php';
        }
    }

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $nmid = trim($_POST['nmid'] ?? '');

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO granna80_bdlinks.assets (name, nmid, contract_name, ticker_symbol, decimal_value, network) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $contract_name, $nmid, $contractAddress, $ticker_symbol, $decimal_value, $network);

    // Execute the insertion into the database
    if ($stmt->execute()) {
        $last_id = $conn->insert_id;
        $_SESSION['message'] = "New record created successfully. ID: " . $last_id . " <a href='assets.edit.php?id=" . $last_id . "'><i class='fa-solid fa-pen-to-square'>edit</i></a>";
        $stmt->close();

        if (!empty($_POST['create_folder']) && !empty($nmid)) {
            @mkdir($_SERVER['DOCUMENT_ROOT'] . '/.scr/library/json/' . trim($nmid), 0755, true);
        }

        require_once __DIR__ . '/update_assets_json.php';
        update_assets_json($conn);
    } else {
        $_SESSION['message'] = "Error: " . $stmt->error;
        $stmt->close();
    }

    $conn->close();

    $redirectUrl = isset($_POST['add_and_close']) ? 'index.php' : 'assets.add.php';
    echo "<script>window.location.href = '{$redirectUrl}';</script>";
    exit();
}

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Add Asset</title>
</head>

<body>
    <h1>Add Asset</h1>
    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
 

    <form action="" method="post">
        <label for="network">Network:</label>
        <select id="network" name="network" required>
            <option value="polygon" selected>Polygon</option>
            <option value="ethereum">Ethereum</option>
            <option value="bnb">BNB Chain</option>
        </select><br><br>

        <label for="contract_address">Contract:</label>
        <input type="text" id="contract_address" name="contract_address" required><br><br>

        <label for="nmid">NMID:</label>
        <input type="text" id="nmid" name="nmid" placeholder="ex: usdc.coin"><br><br>

        <label style="cursor: pointer; user-select: none;">
            <input type="checkbox" id="create_folder" name="create_folder" value="1"> Create folder
        </label><br><br>

        <input type="submit" value="Add Asset">
        <input type="submit" name="add_and_close" value="Add Asset & Close">
        <a href="index.php">[Back]</a>
    </form>



</body>

</html>
