<?php include $_SERVER['DOCUMENT_ROOT'] . '/plataforma/painel/is_logged.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/.scr/conexao.php'; ?>
<?php 

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Retrieve nmid before deleting record
    $nmid = '';
    $sel = $conn->prepare("SELECT nmid FROM granna80_bdlinks.assets WHERE id = ?");
    if ($sel) {
        $sel->bind_param('i', $id);
        $sel->execute();
        $sel->bind_result($nmid);
        $sel->fetch();
        $sel->close();
    }

    // Prepare and execute deletion
    $stmt = $conn->prepare("DELETE FROM granna80_bdlinks.assets WHERE id = ?");
    if ($stmt === false) {
        trigger_error($conn->error, E_USER_ERROR);
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();

        // Delete folder if requested
        if (!empty($_GET['delete_folder']) && !empty($nmid)) {
            $folder = $_SERVER['DOCUMENT_ROOT'] . '/.scr/library/json/' . trim($nmid);
            if (is_dir($folder)) {
                array_map('unlink', glob("{$folder}/*.*"));
                @rmdir($folder);
            }
        }

        require_once __DIR__ . '/update_assets_json.php';
        update_assets_json($conn);
        $conn->close();

        echo "<script>window.location.href = 'index.php';</script>";
        exit();
    } else {
        echo "Error deleting record.";
        $stmt->close();
        $conn->close();
    }
} else {
    echo "Invalid or unspecified ID.";
}
?>
