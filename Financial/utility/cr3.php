<?php

$host ="localhost";
$user = "root";
$password = "";
$db = "core3";


try {
    $pdo3 = new PDO("mysql:host=$host;dbname=$db", $user, $password);
    $pdo3->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

?>