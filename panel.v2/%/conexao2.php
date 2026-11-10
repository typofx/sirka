<?php
// Define defaults to prevent warnings if env.php is missing
$host = defined('DB_HOST') ? DB_HOST : 'localhost';
$name = defined('DB_NAME') ? DB_NAME : '';
$user = defined('DB_USER') ? DB_USER : '';
$pass = defined('DB_PASS') ? DB_PASS : '';
$char = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$name;charset=$char";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("PDO Connection failed: " . $e->getMessage());
}

// Legacy support for mysqli code (defensive check)
$conn = null;
if (class_exists('mysqli')) {
    try {
        $conn = new mysqli($host, $user, $pass, $name);
        if ($conn->connect_error) {
            error_log("Mysqli connection failed: " . $conn->connect_error);
            $conn = null;
        }
    } catch (Exception $e) {
        error_log("Mysqli instantiation error: " . $e->getMessage());
        $conn = null;
    }
}
?>
