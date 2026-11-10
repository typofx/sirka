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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    redirect('panel/acess/list');
}

try {
    // Handle individual submenu deletion
    if (isset($_GET['delete_submenu'])) {
        $submenu_id_to_delete = intval($_GET['delete_submenu']);
        $deleteSubStmt = $pdo->prepare("DELETE FROM submenus WHERE id = ? AND parent_id = ?");
        $deleteSubStmt->execute([$submenu_id_to_delete, $id]);
        redirect("panel/acess/edit?id=$id");
    }

    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!hash_equals($token, $_POST['csrf_token'] ?? '')) {
            die('CSRF token validation failed.');
        }
        if (isset($_POST['action']) && $_POST['action'] == 'delete') {
            // Delete associated submenus first
            $pdo->prepare("DELETE FROM submenus WHERE parent_id = ?")->execute([$id]);
            // Delete the parent item
            $pdo->prepare("DELETE FROM user_permissions WHERE id = ?")->execute([$id]);
            redirect('panel/acess/list');
        } else {
            // Update the parent item
            $name = $_POST['name'] ?? '';
            $link = $_POST['link'] ?? '';
            $guest = isset($_POST['guest']) ? 1 : 0;
            $trainee = isset($_POST['trainee']) ? 1 : 0;
            $admin = isset($_POST['admin']) ? 1 : 0;
            $root = isset($_POST['root']) ? 1 : 0;
            $office = isset($_POST['office']) ? 1 : 0;
            $block = isset($_POST['block']) ? 1 : 0;

            $updateStmt = $pdo->prepare("UPDATE user_permissions 
                            SET name = ?, link = ?, guest = ?, trainee = ?, admin = ?, root = ?, office = ?, block = ? 
                            WHERE id = ?");
            $updateStmt->execute([$name, $link, $guest, $trainee, $admin, $root, $office, $block, $id]);

            // Update submenus
            if (!empty($_POST['submenu_id'])) {
                foreach ($_POST['submenu_id'] as $submenuId) {
                    $submenuName = $_POST['submenu_name'][$submenuId] ?? '';
                    $submenuLink = $_POST['submenu_link'][$submenuId] ?? '';
                    $pdo->prepare("UPDATE submenus SET name = ?, link = ? WHERE id = ?")
                        ->execute([$submenuName, $submenuLink, $submenuId]);
                }
            }
            if (isset($_POST['action']) && $_POST['action'] === 'save_close') {
                redirect('panel/acess/list');
            } else {
                redirect("panel/acess/edit?id=$id&success=1");
            }
            exit();
        }
    }

    // Fetch the item
    $stmt = $pdo->prepare("SELECT * FROM user_permissions WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        redirect('panel/acess/list');
    }

    // Fetch submenus
    $submenuStmt = $pdo->prepare("SELECT * FROM submenus WHERE parent_id = ?");
    $submenuStmt->execute([$id]);
    $submenus = $submenuStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item | Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/panel.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/fontawesome.css">
</head>
<body>
    <h1>Edit Record</h1>

    <form action="<?php echo BASE_URL; ?>panel/acess/edit?id=<?php echo $id; ?>" method="POST">
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
                    <td><input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required style="margin:0;"></td>
                    <td><input type="text" name="link" value="<?php echo htmlspecialchars($row['link']); ?>" style="margin:0;"></td>
                    <td><input type="checkbox" name="guest" <?php if ($row['guest']) echo 'checked'; ?>></td>
                    <td><input type="checkbox" name="trainee" <?php if ($row['trainee']) echo 'checked'; ?>></td>
                    <td><input type="checkbox" name="admin" <?php if ($row['admin']) echo 'checked'; ?>></td>
                    <td><input type="checkbox" name="root" <?php if ($row['root']) echo 'checked'; ?>></td>
                    <td><input type="checkbox" name="office" <?php if ($row['office']) echo 'checked'; ?>></td>
                    <td><input type="checkbox" name="block" <?php if ($row['block']) echo 'checked'; ?>></td>
                </tr>
            </tbody>
        </table>

        <?php if (!empty($submenus)) { ?>
            <div class="submenu-section">
                <h2>Submenus</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Submenu Name</th>
                            <th>Submenu Link</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submenus as $submenu) { ?>
                            <tr>
                                <td><input type="text" name="submenu_name[<?php echo $submenu['id']; ?>]" value="<?php echo htmlspecialchars($submenu['name']); ?>" required style="margin:0;"></td>
                                <td><input type="text" name="submenu_link[<?php echo $submenu['id']; ?>]" value="<?php echo htmlspecialchars($submenu['link']); ?>" style="margin:0;"></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>panel/acess/edit?id=<?php echo $id; ?>&delete_submenu=<?php echo $submenu['id']; ?>" 
                                       class="btn-delete-small" 
                                       onclick="return confirm('⚠️ Are you sure you want to delete this submenu?')">
                                       <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                                <input type="hidden" name="submenu_id[]" value="<?php echo $submenu['id']; ?>">
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>

        <div class="button-container">
            <a href="<?php echo BASE_URL; ?>panel/acess/list" class="btn" style="background-color: #888;">Cancel</a>
            <button type="submit" name="action" value="save" class="btn">Save</button>
            <button type="submit" name="action" value="save_close" class="btn" style="background-color: #4CAF50;">Save & Close</button>
            <button type="submit" name="action" value="delete" class="btn btn-delete" onclick="return confirm('⚠️ WARNING: Are you sure you want to delete this item and all its submenus?')">Delete</button>
        </div>
    </form>
</body>
</html>
