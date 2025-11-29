<?php
// funkcije/veza_do_baze.php
require_once __DIR__ . "/../konfiguracija/konfiguracija_softver.php";

$host = DB_HOST;
$db = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $veza = new PDO($dsn, $user, $pass);
    $veza->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "Connection failed: " . $e->getMessage()]);
    exit();
}
?>
