<?php include $_SERVER['DOCUMENT_ROOT'] . '/plataforma/painel/is_logged.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/.scr/conexao.php'; ?>
<?php 

// Check if the id parameter is passed in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {

    // Filter the id to prevent SQL injection
    $id = $_GET['id'];

    // Prepare the SQL query to delete the record
    $sql = "DELETE FROM granna80_bdlinks.assets WHERE id = ?";

    // Prepare the statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        trigger_error($conn->error, E_USER_ERROR);
    }

    // Bind parameters and execute the statement
    $stmt->bind_param('i', $id);
    $stmt->execute();

    // Check if the deletion was successful
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        require_once __DIR__ . '/update_assets_json.php';
        update_assets_json($conn);
        $conn->close();
        // Redirect back to the payments page
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
