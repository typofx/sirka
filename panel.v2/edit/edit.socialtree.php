<?php 
$currentDir = __DIR__;
while ($currentDir !== dirname($currentDir) && !file_exists($currentDir . '/%/is_logged.php')) {
    $currentDir = dirname($currentDir);
}
require_once $currentDir . '/%/is_logged.php';
?>

<?php
// 1. Production-ready error reporting (Log to file, hide from browser)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$email = $_SESSION['registered_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect the inputs
    $s0 = $_POST['socialMedia'] ?? '';
    $s1 = $_POST['socialMedia1'] ?? '';
    $s2 = $_POST['socialMedia2'] ?? '';
    $s3 = $_POST['socialMedia3'] ?? '';
    $s4 = $_POST['socialMedia4'] ?? '';
    $s5 = $_POST['socialMedia5'] ?? '';
    $s6 = $_POST['socialMedia6'] ?? '';
    $s7 = $_POST['socialMedia7'] ?? '';
    $s8 = $_POST['socialMedia8'] ?? '';
    $s9 = $_POST['socialMedia9'] ?? '';
    $s10 = $_POST['socialMedia10'] ?? '';

    $action = $_POST['action'] ?? 'save';

    $checkStmt = $pdo->prepare("SELECT id FROM team WHERE email = ?");
    $checkStmt->execute([$email]);
    $exists = $checkStmt->rowCount() > 0;

    if ($exists) {
        $updateStmt = $pdo->prepare("UPDATE team SET 
            teamSocialMedia0 = ?, teamSocialMedia1 = ?, teamSocialMedia2 = ?, 
            teamSocialMedia3 = ?, teamSocialMedia4 = ?, teamSocialMedia5 = ?, 
            teamSocialMedia6 = ?, teamSocialMedia7 = ?, teamSocialMedia8 = ?, 
            teamSocialMedia9 = ?, teamSocialMedia10 = ?,
            last_modified = NOW(), last_modified_user = ? 
            WHERE email = ?");
        
        $updateStmt->execute([$s0, $s1, $s2, $s3, $s4, $s5, $s6, $s7, $s8, $s9, $s10, $username, $email]);
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO team (
            email, teamName,
            teamSocialMedia0, teamSocialMedia1, teamSocialMedia2, 
            teamSocialMedia3, teamSocialMedia4, teamSocialMedia5, 
            teamSocialMedia6, teamSocialMedia7, teamSocialMedia8, 
            teamSocialMedia9, teamSocialMedia10,
            last_modified, last_modified_user
        ) VALUES (
            ?, ?, 
            ?, ?, ?, 
            ?, ?, ?, 
            ?, ?, ?, 
            ?, ?,
            NOW(), ?
        )");
        $insertStmt->execute([$email, $username, $s0, $s1, $s2, $s3, $s4, $s5, $s6, $s7, $s8, $s9, $s10, $username]);
    }

    if ($action === 'save_close') {
        redirect('auth/menu');
    } else {
        header("Location: ?success=1");
    }
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM team WHERE email = ?");
$stmt->execute([$email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $userProfileName = $row['teamName'];
    $lastModified = $row['last_modified'];
    $last_modifiedUser = $row['last_modified_user'];
    $userUuidDb = $row['uuid'];
} else {
    $row = []; // Initialize as empty array to prevent access errors
    $userProfileName = $username;
    $lastModified = null;
    $last_modifiedUser = null;
    $userUuidDb = null;
}

// Session Timer Logic
$session_timeout = 43200;
$remaining_seconds = max(0, $session_timeout - (time() - ($_SESSION['session_start_time'] ?? time())));

$script_nonce = '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Team Member</title>
    <!-- Use full URL to avoid any path issues -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/socialtree.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/fontawesome.css">
    <script <?php echo $script_nonce ? "nonce=\"$script_nonce\"" : ""; ?> src="<?php echo BASE_URL; ?>assets/jquery.min.js"></script>
</head>
<body>
    <div class="main-container">
        <div style="font-size: 12px; color: #666; margin-bottom: 20px;"><?php echo getTimeRemaining(); ?></div>

        <h2>Social Tree</h2>
    

        <?php if (isset($_GET['success'])): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px; text-align: center; margin-bottom: 15px; border-radius: 5px; border: 1px solid #c3e6cb;">
                Profile saved successfully!
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
            <div class="section-title"><?php echo $userProfileName; ?> Profiles</div>

            <div class="grid-container">
                <?php 
                $labels = ["WhatsApp", "Instagram", "Telegram", "Facebook", "GitHub", "Email", "Twitter", "LinkedIn", "Twitch", "Medium", "Google"];
                $icons = [
                    "fa-brands fa-whatsapp", "fa-brands fa-instagram", "fa-brands fa-telegram", 
                    "fa-brands fa-facebook", "fa-brands fa-github", "fa-solid fa-envelope", 
                    "fa-brands fa-x-twitter", "fa-brands fa-linkedin", "fa-brands fa-twitch", 
                    "fa-brands fa-medium", "fa-brands fa-google"
                ];
                for($i=0; $i<11; $i++): 
                    $id_name = ($i == 0) ? "socialMedia" : "socialMedia$i";
                ?>
                <div class="form-group">
                    <label for="<?php echo $id_name; ?>"><?php echo $labels[$i]; ?>:</label>
                    <div class="input-with-icon">
                        <i class="<?php echo $icons[$i]; ?>"></i>
                        <input type="text" id="<?php echo $id_name; ?>" name="<?php echo $id_name; ?>" value="<?php echo htmlspecialchars($row["teamSocialMedia$i"] ?? ''); ?>">
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <div class="footer-actions">
                <a href="<?php echo url('auth/menu'); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" name="action" value="save" class="btn btn-primary">Save</button>
                <button type="submit" name="action" value="save_close" class="btn btn-primary" style="background-color: #4CAF50; border-color: #4CAF50;">Save & Close</button>
            </div>
        </form>
    </div>
    
    
    
    <script <?php echo $script_nonce ? "nonce=\"$script_nonce\"" : ""; ?>>
        // Clear URL Query Parameters after load
        if (window.history.replaceState && window.location.search !== '') {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>