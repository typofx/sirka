<?php
ob_start();
session_start();

/**
 * Front Controller
 * Central entry point for extensionless routing and modular bootstrapping.
 */
require_once __DIR__ . '/%/config.php';
require_once __DIR__ . '/%/Router.php';

$router = new Router(__DIR__);
$file = $router->resolve();

if ($file) {
    // Public authentication routes that do not require an active session
    $publicPages = [
        'auth.signin.php',
        'auth.signup.php',
        'auth.captcha.php',
        'auth.verify.php',
        'auth.authenticator.php',
        'auth.register.php'
    ];

    $filename = basename($file);
    if (!in_array($filename, $publicPages, true)) {
        require_once __DIR__ . '/%/is_logged.php';
    }

    // We chdir to the file's directory to maintain relative paths compatibility
    chdir(dirname($file));
    require_once $filename;
} else {
    header("HTTP/1.0 404 Not Found");
    echo "404 Not Found";
}
