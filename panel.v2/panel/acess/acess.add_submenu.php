<?php 
$currentDir = __DIR__;
while ($currentDir !== dirname($currentDir) && !file_exists($currentDir . '/%/is_logged.php')) {
    $currentDir = dirname($currentDir);
}
require_once $currentDir . '/%/is_logged.php';
?>

<?php

if ($user_level !== 'root' || $active_layers < 2) {
    redirect('auth/menu');
}

$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
if ($parent_id <= 0) {
    redirect('panel/acess/list');
}

try {
    // Fetch parent name
    $stmt = $pdo->prepare("SELECT name FROM user_permissions WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parent) {
        redirect('panel/acess/list');
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!hash_equals($token, $_POST['csrf_token'] ?? '')) {
            die('CSRF token validation failed.');
        }
        $name = $_POST['name'] ?? '';
        $link = $_POST['link'] ?? '';
        
        if (!empty($name) && !empty($link)) {
            $insertStmt = $pdo->prepare("INSERT INTO submenus (parent_id, name, link) VALUES (?, ?, ?)");
            $insertStmt->execute([$parent_id, $name, $link]);
            
            if (isset($_POST['action']) && $_POST['action'] === 'save') {
                redirect("panel/acess/edit?id=$parent_id");
            } else {
                redirect('panel/acess/list');
            }
            exit();
        } else {
            $error = "Please fill all fields.";
        }
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Submenu | Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/panel.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/fontawesome.css">
</head>
<body>
    <div class="form-container">
        <h1>Add Submenu for <?php echo htmlspecialchars($parent['name']); ?></h1>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form action="<?php echo BASE_URL; ?>panel/acess/add_submenu?parent_id=<?php echo $parent_id; ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
            <label for="name">Submenu Name:</label>
            <input type="text" id="name" name="name" required>
            
            <label for="link">Submenu Link:</label>
            <input type="text" id="link" name="link" required>
            
            <div class="button-container">
                <a href="<?php echo BASE_URL; ?>panel/acess/list" class="btn" style="background-color: #888;">Cancel</a>
                <button type="submit" name="action" value="save" class="btn">Save</button>
                <button type="submit" name="action" value="save_close" class="btn" style="background-color: #4CAF50;">Save & Close</button>
            </div>
        </form>
    </div>
</body>
</html>
