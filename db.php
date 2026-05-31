<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'LostnFound');
define('DB_USER', 'root');
define('DB_PASS', ''); // ⚠️ IF YOUR TERMINAL MYSQL USES A PASSWORD, TYPE IT HERE!

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Database Connection Failure: " . $e->getMessage());
}

// Start the session so user logins work across pages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}