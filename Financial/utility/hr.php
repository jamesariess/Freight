<?php

$host ="localhost";
$user = "root";
$password = "";
$db = "hr";


try {
    $pdo2 = new PDO("mysql:host=$host;dbname=$db", $user, $password);
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

?>