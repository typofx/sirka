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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!hash_equals($token, $_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $name = $_POST['name'] ?? '';
    $link = $_POST['link'] ?? '';
    $guest = isset($_POST['guest']) ? 1 : 0;
    $trainee = isset($_POST['trainee']) ? 1 : 0;
    $admin = isset($_POST['admin']) ? 1 : 0;
    $root = isset($_POST['root']) ? 1 : 0;
    $office = isset($_POST['office']) ? 1 : 0;
    $block = isset($_POST['block']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("INSERT INTO user_permissions (name, link, guest, trainee, admin, root, office, block) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $link, $guest, $trainee, $admin, $root, $office, $block]);
        $new_id = $pdo->lastInsertId();

        if (isset($_POST['action']) && $_POST['action'] === 'save') {
            redirect("panel/acess/edit?id=$new_id");
        } else {
            redirect("panel/acess/list");
        }
        exit();
    } catch (Exception $e) {
        $error = "Error adding record: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Record | Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/panel.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/fontawesome.css">
</head>
<body>
    <h1>Add New Record</h1>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form action="<?php echo BASE_URL; ?>panel/acess/add" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Link</th>
                    <th>Guest</th>
                    <th>Trainee</th>
                    <th>Admin</th>
                    <th>Root</th>
                    <th>Office</th>
                    <th>Block</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="text" name="name" required style="margin:0;"></td>
                    <td><input type="text" name="link" style="margin:0;"></td>
                    <td><input type="checkbox" name="guest"></td>
                    <td><input type="checkbox" name="trainee"></td>
                    <td><input type="checkbox" name="admin"></td>
                    <td><input type="checkbox" name="root"></td>
                    <td><input type="checkbox" name="office"></td>
                    <td><input type="checkbox" name="block"></td>
                </tr>
            </tbody>
        </table>
        <div class="button-container">
            <a href="<?php echo BASE_URL; ?>panel/acess/list" class="btn" style="background-color: #888;">Cancel</a>
            <button type="submit" name="action" value="save" class="btn">Save</button>
            <button type="submit" name="action" value="save_close" class="btn" style="background-color: #4CAF50;">Save & Close</button>
        </div>
    </form>
</body>
</html>
