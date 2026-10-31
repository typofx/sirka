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

// Fetch all rows using PDO
$stmt = $pdo->query("SELECT * FROM user_permissions");
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all submenus
$submenuStmt = $pdo->query("SELECT * FROM submenus");
$submenus = [];
while ($row = $submenuStmt->fetch(PDO::FETCH_ASSOC)) {
    $submenus[$row['parent_id']][] = $row;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!hash_equals($token, $_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $ids = $_POST['id'] ?? [];
    foreach ($ids as $id) {
        $guest = isset($_POST['guest'][$id]) ? 1 : 0;
        $trainee = isset($_POST['trainee'][$id]) ? 1 : 0;
        $admin = isset($_POST['admin'][$id]) ? 1 : 0;
        $root = isset($_POST['root'][$id]) ? 1 : 0;
        $office = isset($_POST['office'][$id]) ? 1 : 0;
        $block = isset($_POST['block'][$id]) ? 1 : 0;

        $updateStmt = $pdo->prepare("UPDATE user_permissions 
                        SET guest = ?, trainee = ?, admin = ?, root = ?, office = ?, block = ? 
                        WHERE id = ?");
        $updateStmt->execute([$guest, $trainee, $admin, $root, $office, $block, $id]);
    }
    redirect('panel/acess/list');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Permissions | Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/panel.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/fontawesome.css">
</head>
<body>
    <div class="top-header">
        <a href="<?php echo BASE_URL; ?>auth/menu">[Control Panel]</a>
        <a href="javascript:window.location.reload(true)">[Refresh]</a>
        <a href="<?php echo BASE_URL; ?>panel/acess/add">[Add New Record]</a>
        <?php echo getTimeRemaining(); ?>
    </div>

    <h1>User Permissions Table</h1>
    <form action="<?php echo BASE_URL; ?>panel/acess/list" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Link</th>
                    <th>Guest</th>
                    <th>Trainee</th>
                    <th>Admin</th>
                    <th>Root</th>
                    <th>Office</th>
                    <th>Block</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $cont = 1;
                foreach ($result as $row) { ?>
                    <tr class="main-row">
                        <td>#<?php echo $cont; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><a href="<?php echo htmlspecialchars($row['link']); ?>" target="_blank"><?php echo htmlspecialchars($row['link']); ?></a></td>
                        <td><input type="checkbox" name="guest[<?php echo $row['id']; ?>]" <?php if ($row['guest']) echo 'checked'; ?>></td>
                        <td><input type="checkbox" name="trainee[<?php echo $row['id']; ?>]" <?php if ($row['trainee']) echo 'checked'; ?>></td>
                        <td><input type="checkbox" name="admin[<?php echo $row['id']; ?>]" <?php if ($row['admin']) echo 'checked'; ?>></td>
                        <td><input type="checkbox" name="root[<?php echo $row['id']; ?>]" <?php if ($row['root']) echo 'checked'; ?>></td>
                        <td><input type="checkbox" name="office[<?php echo $row['id']; ?>]" <?php if ($row['office']) echo 'checked'; ?>></td>
                        <td><input type="checkbox" name="block[<?php echo $row['id']; ?>]" <?php if ($row['block']) echo 'checked'; ?>></td>
                        <td><a href="<?php echo BASE_URL; ?>panel/acess/edit?id=<?php echo $row['id']; ?>"><i class="fa-solid fa-pen-to-square" title="Edit"></i></a></td>
                    </tr>
                    <input type="hidden" name="id[]" value="<?php echo $row['id']; ?>">

                    <?php if (isset($submenus[$row['id']])) { ?>
                        <tr class="submenu-row">
                            <td colspan="10" class="submenu-cell">
                                <div class="submenu-label">Submenus:</div>
                                <ul class="submenu-list-items">
                                    <?php foreach ($submenus[$row['id']] as $submenu) { ?>
                                        <li>
                                            <a href="<?php echo htmlspecialchars($submenu['link']); ?>" target="_blank"><?php echo htmlspecialchars($submenu['name']); ?></a>
                                        </li>
                                    <?php } ?>
                                </ul>
                                <a href="<?php echo BASE_URL; ?>panel/acess/add_submenu?parent_id=<?php echo $row['id']; ?>" class="add-submenu-link">[ Add Submenu ]</a>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <tr class="submenu-row">
                            <td colspan="10" class="submenu-cell">
                                <a href="<?php echo BASE_URL; ?>panel/acess/add_submenu?parent_id=<?php echo $row['id']; ?>" class="add-submenu-link">[ Add Submenu ]</a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php
                    $cont++;
                } ?>
            </tbody>
        </table>
        <div class="button-container">
            <button type="submit">Save Changes</button>
        </div>
    </form>
</body>
</html>
