<?php
// api/get_quiz_questions.php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../funkcije/veza_do_baze.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$category = $_GET["category"] ?? "all";
$difficulty = $_GET["difficulty"] ?? "all";

try {
    $sql = "SELECT q.*, c.name as category_name, c.icon as category_icon
            FROM cyber_questions q
            JOIN cyber_categories c ON q.category_id = c.id
            WHERE 1=1";

    $params = [];

    if ($category !== "all") {
        $sql .= " AND q.category_id = ?";
        $params[] = $category;
    }

    if ($difficulty !== "all") {
        $sql .= " AND q.difficulty = ?";
        $params[] = $difficulty;
    }

    $sql .= " ORDER BY RAND() LIMIT 20";

    $stmt = $veza->prepare($sql);
    $stmt->execute($params);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($questions);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
