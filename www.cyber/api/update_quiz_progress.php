<?php
// api/update_quiz_progress.php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../funkcije/veza_do_baze.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION["user_id"];
$question_id = (int) ($_POST["question_id"] ?? 0);
$is_correct = (int) ($_POST["is_correct"] ?? 0);

if (!$question_id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing question_id"]);
    exit();
}

try {
    // Get question details
    $stmt = $veza->prepare(
        "SELECT category_id, xp_reward FROM cyber_questions WHERE id = ?",
    );
    $stmt->execute([$question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {
        http_response_code(404);
        echo json_encode(["error" => "Question not found"]);
        exit();
    }

    $category_id = $question["category_id"];
    $xp = $is_correct ? $question["xp_reward"] : 0;

    // Update or create user progress for this category
    $stmt = $veza->prepare("
        INSERT INTO cyber_user_progress (user_id, category_id, questions_answered, questions_correct, category_xp)
        VALUES (?, ?, 1, ?, ?)
        ON DUPLICATE KEY UPDATE
            questions_answered = questions_answered + 1,
            questions_correct = questions_correct + ?,
            category_xp = category_xp + ?
    ");
    $stmt->execute([
        $user_id,
        $category_id,
        $is_correct,
        $xp,
        $is_correct,
        $xp,
    ]);

    // Update user total XP
    if ($is_correct) {
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
            $stmt = $veza->prepare(
                "UPDATE cyber_users SET level = ? WHERE id = ?",
            );
            $stmt->execute([$new_level, $user_id]);

            echo json_encode([
                "success" => true,
                "level_up" => true,
                "new_level" => $new_level,
            ]);
            exit();
        }
    }

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
