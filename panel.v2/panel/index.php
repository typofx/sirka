<? require_once __DIR__ . '/../%/is_logged.php'?>

<?php
if ($user_level !== 'root' || $active_layers < 2) {
    redirect('auth/menu');
}

// 2. Redirect to panel menu
redirect('panel/menu');
