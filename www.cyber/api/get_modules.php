<?php
// api/get_modules.php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../funkcije/veza_do_baze.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$category_id = (int) ($_GET["category_id"] ?? 0);

if (!$category_id) {
    echo json_encode(["error" => "Missing category_id"]);
    exit();
}

try {
    // Get category name
    $stmt = $veza->prepare("SELECT name FROM cyber_categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        echo json_encode(["error" => "Category not found"]);
        exit();
    }

    // Get modules
    $stmt = $veza->prepare("
        SELECT * FROM cyber_modules
        WHERE category_id = ?
        ORDER BY module_order, id
    ");
    $stmt->execute([$category_id]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "category_name" => $category["name"],
        "modules" => $modules,
    ]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
