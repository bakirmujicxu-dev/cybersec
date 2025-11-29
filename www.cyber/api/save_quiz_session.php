<?php
// api/save_quiz_session.php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../funkcije/veza_do_baze.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION["user_id"];
$correct = (int) ($_POST["correct"] ?? 0);
$incorrect = (int) ($_POST["incorrect"] ?? 0);
$total_xp = (int) ($_POST["total_xp"] ?? 0);
$category = $_POST["category"] ?? "all";
$difficulty = $_POST["difficulty"] ?? "all";

try {
    // Create quiz_sessions table if not exists
    $veza->exec("CREATE TABLE IF NOT EXISTS cyber_quiz_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        correct INT NOT NULL,
        incorrect INT NOT NULL,
        total_xp INT NOT NULL,
        category VARCHAR(50),
        difficulty VARCHAR(20),
        completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $veza->prepare("
        INSERT INTO cyber_quiz_sessions (user_id, correct, incorrect, total_xp, category, difficulty)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $correct,
        $incorrect,
        $total_xp,
        $category,
        $difficulty,
    ]);

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
