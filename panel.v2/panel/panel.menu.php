<? require_once __DIR__ . '/../%/is_logged.php'?>
<? require_once __DIR__ . '/../auth/auth.validate.php'?>

<?php
if ($user_level !== 'root' || $active_layers < 2) {
    redirect('auth/menu');
}

// Query to get the permissions for the user's level
$stmt = $pdo->prepare("SELECT * FROM user_permissions WHERE $user_level = 1");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query to get submenus
$submenuStmt = $pdo->query("SELECT * FROM submenus WHERE parent_id IS NOT NULL");
$submenus = [];
while ($submenuRow = $submenuStmt->fetch(PDO::FETCH_ASSOC)) {
    $submenus[$submenuRow['parent_id']][] = $submenuRow;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    body {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100vh;
        margin: 0;
    }

    h1 {
        margin-top: -200px;
    }

    ul {
        list-style-type: none;
        padding: 0;
        text-align: center;
    }

    li {
        display: inline-block;
        margin: 0 15px;
        position: relative;
    }

    a {
        text-decoration: none;
        color: #333;
        font-weight: bold;
        font-size: 18px;
        transition: color 0.3s ease, background-color 0.3s ease; /* Adicionado para animação suave */
        padding: 5px 10px; /* Adicionado para melhorar a área de interação */
        border-radius: 5px; /* Adicionado para bordas arredondadas */
    }

    a:hover {
        color: white; /* Cor do texto ao passar o mouse */
        background-color: #555; /* Cor de fundo ao passar o mouse */
    }

    .submenu {
        font-size: 5px;
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background: white;
        white-space: nowrap;
        padding: 5px;
        list-style: none;
        min-width: 150px;
        z-index: 1000; /* Garante que o submenu fique acima de outros elementos */
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); /* Melhora a visibilidade */
    }

    .submenu a {
        text-decoration: none;
        color: #333;
        font-weight: bold;
        font-size: 15px;
        transition: color 0.3s ease, background-color 0.3s ease; /* Adicionado para animação suave */
     
    }

    .submenu a:hover {
        color: white; /* Cor do texto ao passar o mouse */
        background-color: #555; /* Cor de fundo ao passar o mouse */
    }

    li:hover .submenu {
        display: block;
    }

    .submenu li {
        display: block;
        margin: 5px 0;
    }
</style>
    <title>Menu</title>
</head>

<body>
    <h1>Control Panel</h1>
    <p>Hello <b><?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars(maskEmail($_SESSION['registered_email'] ?? '')); ?>) (<?php echo htmlspecialchars($user_level); ?>)</b></p>
    <p><?php echo getTimeRemaining(); ?></p>

    <ul>
        <?php
        $count = 0;
        if (!empty($result)) {
            foreach ($result as $row) {
                if ($row['name'] === 'Blocked Access' && $user_level !== 'block') {
                    continue;
                }
                echo '<li><a href="' . htmlspecialchars($row['link']) . '">[ ' . htmlspecialchars($row['name']) . ' ]</a>';
                if (isset($submenus[$row['id']])) {
                    echo '<ul class="submenu">';
                    foreach ($submenus[$row['id']] as $submenu) {
                        echo '<li><a href="' . htmlspecialchars($submenu['link']) . '">[' . htmlspecialchars($submenu['name']) . ']</a></li>';
                    }
                    echo '</ul>';
                }
                echo '</li>';
                $count++;
                if ($count % 5 == 0) {
                    echo '<br><br>';
                }
            }
        } else {
            echo '<li>Access restricted</li>';
        }
        ?>
        <li><a href="<?php echo url('auth/menu'); ?>">[ Back ]</a></li>
    </ul>
</body>
</html>