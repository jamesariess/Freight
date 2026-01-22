<?php

$host = "localhost";
$dbname = "financial";
$username = "root";
$password = "";

// $host = "localhost";
// $dbname = "fina_financial";
// $username = "fina_finances";
// $password = "7rO-@mwup07Io^g0";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

?>