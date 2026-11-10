<? include $_SERVER['DOCUMENT_ROOT'] . '/plataforma/panel/is_logged.php'; ?>
<? include $_SERVER['DOCUMENT_ROOT'] . '/.scr/conexao.php'; ?>

<?php
// Include connection file
//include 'conexao.php';

// Check if asset ID is passed via GET
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Asset ID not specified.");
}

$id = (int)$_GET['id'];

$query_select = "SELECT * FROM granna80_bdlinks.assets WHERE id = ?";
$stmt_select = mysqli_prepare($conn, $query_select);

if (!$stmt_select) {
    die("Error preparing select statement: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt_select, "i", $id);
mysqli_stmt_execute($stmt_select);
$result_select = mysqli_stmt_get_result($stmt_select);

if (mysqli_num_rows($result_select) !== 1) {
    mysqli_stmt_close($stmt_select);
    mysqli_close($conn);
    die("Asset not found.");
}

$asset = mysqli_fetch_assoc($result_select);
mysqli_stmt_close($stmt_select);

if (isset($_POST['submit'])) {
    $asset['nmid'] = trim($_POST['nmid'] ?? '');
    $asset['contract_name'] = trim($_POST['contract_name'] ?? '');
    $asset['aka_contract_name'] = trim($_POST['aka_contract_name'] ?? '');
    $asset['ticker_symbol'] = trim($_POST['ticker_symbol'] ?? '');
    $asset['decimal_value'] = trim($_POST['decimal_value'] ?? '');
    $asset['price'] = trim($_POST['price'] ?? '');
    $asset['name'] = trim($_POST['name'] ?? '');
    $asset['pool'] = trim($_POST['pool'] ?? '');

    $new_icon = null;
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['icon']['error'] !== UPLOAD_ERR_OK) {
            echo "Error uploading icon.";
        } else {
            $extension = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
            $file_content = file_get_contents($_FILES['icon']['tmp_name']);
            $mime_type = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['icon']['tmp_name']);
            $allowed_mime_types = ['image/svg+xml', 'application/xml', 'text/xml', 'text/plain'];
            $has_svg_root = $file_content !== false
                && preg_match('/<(?:[a-zA-Z][\w.-]*:)?svg(?:\s|>)/i', $file_content);

            if ($extension !== 'svg' || !in_array($mime_type, $allowed_mime_types, true) || !$has_svg_root) {
                echo "Invalid icon. Only SVG files are allowed.";
            } else {
                $icon_name = uniqid() . ".svg";
                $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/images/assets-icons/";
                $target_file = $target_dir . $icon_name;

                if (move_uploaded_file($_FILES['icon']['tmp_name'], $target_file)) {
                    $new_icon = $icon_name;
                    $asset['icon_path'] = $icon_name;
                } else {
                    echo "Error uploading icon.";
                }
            }
        }
    }

    if ($new_icon !== null) {
        $query = "UPDATE granna80_bdlinks.assets SET
        contract_name = ?, nmid = ?, aka_contract_name = ?, ticker_symbol = ?,
        decimal_value = ?, price = ?, name = ?, pool = ?, icon_path = ?
        WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssdssssi", $asset['contract_name'], $asset['nmid'], $asset['aka_contract_name'], $asset['ticker_symbol'], $asset['decimal_value'], $asset['price'], $asset['name'], $asset['pool'], $asset['icon_path'], $id);
        }
    } else {
        $query = "UPDATE granna80_bdlinks.assets SET
        contract_name = ?, nmid = ?, aka_contract_name = ?, ticker_symbol = ?,
        decimal_value = ?, price = ?, name = ?, pool = ?
        WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssdsssi", $asset['contract_name'], $asset['nmid'], $asset['aka_contract_name'], $asset['ticker_symbol'], $asset['decimal_value'], $asset['price'], $asset['name'], $asset['pool'], $id);
        }
    }

    if ($stmt) {
        $is_updated = mysqli_stmt_execute($stmt);
        if ($is_updated) {
            echo "Asset updated successfully.";
            require_once __DIR__ . '/update_assets_json.php';
            update_assets_json($conn);
        } else {
            echo "Error updating asset: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Edit Asset</title>
</head>

<body>
    <h2>Edit Asset</h2>
    <form action="assets.edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">

        <label>Current Icon:</label><br>
        <?php if (!empty($asset['icon_path'])) { ?>
            <img src="/images/assets-icons/<?php echo htmlspecialchars($asset['icon_path']); ?>" alt="Asset Icon" style="width:100px;height:100px;"><br><br>
        <?php } else { ?>
            <p>No icon available.</p><br><br>
        <?php } ?>
        <label>Upload new Icon:</label><br>
        <input type="file" name="icon" accept=".svg,image/svg+xml"><br><br>
        <input type="submit" name="submit" value="Save"><br><br>
        <label>Contract:</label>
        <input type="text" name="contract_name" value="<?php echo htmlspecialchars($asset['contract_name'] ?? ''); ?>"><br><br>
        <label>NMID:</label>
        <input type="text" name="nmid" value="<?php echo htmlspecialchars($asset['nmid'] ?? ''); ?>"><br><br>
        <label>Pool:</label>
        <input type="text" name="pool" value="<?php echo htmlspecialchars($asset['pool'] ?? ''); ?>"><br><br>
        <label>Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($asset['name'] ?? ''); ?>"><br><br>
        <label>AKA Contract:</label>
        <input type="text" name="aka_contract_name" value="<?php echo htmlspecialchars($asset['aka_contract_name'] ?? ''); ?>"><br><br>
        <label>Ticker Symbol:</label>
        <input type="text" name="ticker_symbol" value="<?php echo htmlspecialchars($asset['ticker_symbol'] ?? ''); ?>"><br><br>
        <label>Decimal Value:</label>
        <input type="number" name="decimal_value" value="<?php echo htmlspecialchars($asset['decimal_value'] ?? ''); ?>"><br><br>
        <label>Price:</label>
        <input type="text" name="price" value="<?php echo htmlspecialchars($asset['price'] ?? ''); ?>"><br><br>
        <input type="submit" name="submit" value="Save">
        <a href="index.php">Back</a>
    </form>
</body>

</html>
<?php
mysqli_close($conn);
?>
