<?php
$host = "sql305.infinityfree.com"; 
$dbname = "if0_43396034_rfautos"; 
$username = "if0_43396034"; 

$password = "Komuna"; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>