<?php
// api/get_module.php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../funkcije/veza_do_baze.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$module_id = (int) ($_GET["id"] ?? 0);

if (!$module_id) {
    echo json_encode(["error" => "Missing module ID"]);
    exit();
}

try {
    $stmt = $veza->prepare("
        SELECT m.*, c.name as category_name
        FROM cyber_modules m
        JOIN cyber_categories c ON m.category_id = c.id
        WHERE m.id = ?
    ");
    $stmt->execute([$module_id]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$module) {
        echo json_encode(["error" => "Module not found"]);
        exit();
    }

    echo json_encode($module);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
