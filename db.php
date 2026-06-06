<?php
$host = "localhost";     
$user = "root";                  
$pass = "";     
$db_name = "lostnfound";

$conn = new mysqli($host, $user, $pass, $db_name);

if ($conn->connect_error) 
    {
    die("Database Connection Failed: " . $conn->connect_error);
}

try {
    $pdo = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $user, $pass, [
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