<?php
// api/complete_module.php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../funkcije/veza_do_baze.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION["user_id"];
$module_id = (int) ($_POST["module_id"] ?? 0);
$category_id = (int) ($_POST["category_id"] ?? 0);

if (!$module_id || !$category_id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing module_id or category_id"]);
    exit();
}

try {
    // Get module XP
    $stmt = $veza->prepare("SELECT xp_reward FROM cyber_modules WHERE id = ?");
    $stmt->execute([$module_id]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$module) {
        http_response_code(404);
        echo json_encode(["error" => "Module not found"]);
        exit();
    }

    $xp = $module["xp_reward"];

    // Check if already completed
    $veza->exec("CREATE TABLE IF NOT EXISTS cyber_module_completions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        module_id INT NOT NULL,
        completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
        FOREIGN KEY (module_id) REFERENCES cyber_modules(id) ON DELETE CASCADE,
        UNIQUE KEY unique_completion (user_id, module_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $veza->prepare(
        "SELECT id FROM cyber_module_completions WHERE user_id = ? AND module_id = ?",
    );
    $stmt->execute([$user_id, $module_id]);

    if ($stmt->fetch()) {
        echo json_encode(["success" => true, "already_completed" => true]);
        exit();
    }

    // Record completion
    $stmt = $veza->prepare(
        "INSERT INTO cyber_module_completions (user_id, module_id) VALUES (?, ?)",
    );
    $stmt->execute([$user_id, $module_id]);

    // Update user progress
    $stmt = $veza->prepare("
        INSERT INTO cyber_user_progress (user_id, category_id, modules_completed, category_xp)
        VALUES (?, ?, 1, ?)
        ON DUPLICATE KEY UPDATE
            modules_completed = modules_completed + 1,
            category_xp = category_xp + ?
    ");
    $stmt->execute([$user_id, $category_id, $xp, $xp]);

    // Update user total XP
    $stmt = $veza->prepare(
        "UPDATE cyber_users SET total_xp = total_xp + ? WHERE id = ?",
    );
    $stmt->execute([$xp, $user_id]);

    // Check for level up
    $stmt = $veza->prepare(
        "SELECT total_xp, level FROM cyber_users WHERE id = ?",
    );
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $new_level = floor($user["total_xp"] / 100) + 1;
    if ($new_level > $user["level"]) {
        $stmt = $veza->prepare("UPDATE cyber_users SET level = ? WHERE id = ?");
        $stmt->execute([$new_level, $user_id]);

        echo json_encode([
            "success" => true,
            "level_up" => true,
            "new_level" => $new_level,
        ]);
        exit();
    }

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
