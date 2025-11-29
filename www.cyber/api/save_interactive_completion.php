<?php
// api/save_interactive_completion.php - Save interactive element completion
session_start();
header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON"]);
    exit();
}

$element_id = (int) ($data['element_id'] ?? 0);
$xp_earned = (int) ($data['xp_earned'] ?? 0);
$completion_time = (int) ($data['completion_time'] ?? 0);

if (!$element_id || $xp_earned < 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid parameters"]);
    exit();
}

require_once __DIR__ . "/../funkcije/veza_do_baze.php";
require_once __DIR__ . "/../funkcije/helpers.php";

try {
    // Start transaction
    $veza->beginTransaction();

    // Save interactive completion
    $stmt = $veza->prepare("
        INSERT INTO cyber_interactive_completions (user_id, element_id, score, xp_earned, completion_time)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $element_id, 100, $xp_earned, $completion_time]);

    // Get element details for XP calculation
    $stmt = $veza->prepare("
        SELECT ie.xp_reward, ie.category_id
        FROM cyber_interactive_elements ie
        WHERE ie.id = ?
    ");
    $stmt->execute([$element_id]);
    $element = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($element) {
        // Update user total XP
        $stmt = $veza->prepare("
            UPDATE cyber_users
            SET total_xp = total_xp + ?,
                level = FLOOR((total_xp + ?) / 100) + 1
            WHERE id = ?
        ");
        $stmt->execute([$xp_earned, $xp_earned, $user_id]);

        // Update user progress for category
        $stmt = $veza->prepare("
            INSERT INTO cyber_user_progress (user_id, category_id, interactive_completed, category_xp, last_activity)
            VALUES (?, ?, 1, ?, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
            interactive_completed = interactive_completed + 1,
            category_xp = category_xp + ?,
            last_activity = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$user_id, $element['category_id'], $xp_earned, $xp_earned]);

        // Update user streak
        $stmt = $veza->prepare("
            INSERT INTO cyber_user_streaks (user_id, current_streak, longest_streak, last_activity_date)
            VALUES (?, 1, 1, CURDATE())
            ON DUPLICATE KEY UPDATE
            current_streak = IF(DATE(last_activity_date) = CURDATE() - INTERVAL 1 DAY, current_streak + 1,
                               IF(DATE(last_activity_date) = CURDATE(), current_streak, 1)),
            longest_streak = GREATEST(longest_streak,
                               IF(DATE(last_activity_date) = CURDATE() - INTERVAL 1 DAY, current_streak + 1,
                               IF(DATE(last_activity_date) = CURDATE(), current_streak, 1))),
            last_activity_date = CURDATE()
        ");
        $stmt->execute([$user_id]);

        // Get new user stats
        $stmt = $veza->prepare("SELECT total_xp, level FROM cyber_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Log activity
        $stmt = $veza->prepare("
            INSERT INTO cyber_user_activity_log (user_id, activity_type, details, xp_earned)
            VALUES (?, 'interactive', ?, ?)
        ");
        $stmt->execute([$user_id, "Completed interactive element: $element_id", $xp_earned]);

        // Check for new achievements
        checkAchievements($veza, $user_id);
    }

    // Commit transaction
    $veza->commit();

    echo json_encode([
        "success" => true,
        "xp_earned" => $xp_earned,
        "new_xp" => $user['total_xp'] ?? 0,
        "new_level" => $user['level'] ?? 1
    ]);
} catch (PDOException $e) {
    // Rollback on error
    $veza->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

// Helper function to check and award achievements
function checkAchievements($veza, $user_id) {
    try {
        // Get user stats
        $stmt = $veza->prepare("SELECT total_xp, level FROM cyber_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get interactive completions count
        $stmt = $veza->prepare("
            SELECT COUNT(*) as count
            FROM cyber_interactive_completions
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $interactive_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Check for level achievements
        $achievements = [
            ['level_5', 'Cyber Defender', 5],
            ['level_10', 'Cyber Expert', 10],
            ['level_15', 'Cyber Master', 15],
            ['level_20', 'Cyber Legend', 20],
        ];

        foreach ($achievements as [$key, $name, $required_level]) {
            if ($user['level'] >= $required_level) {
                $stmt = $veza->prepare("
                    SELECT id FROM cyber_rewards
                    WHERE requirement_type = 'level' AND requirement_value = ? AND reward_type = 'badge'
                ");
                $stmt->execute([$required_level]);
                $reward = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($reward) {
                    awardAchievement($veza, $user_id, $reward['id'], $name, "Reached level $required_level");
                }
            }
        }

        // Check for interactive completion achievements
        if ($interactive_count >= 10) {
            $stmt = $veza->prepare("
                SELECT id FROM cyber_rewards
                WHERE requirement_type = 'achievement' AND requirement_value = 10 AND reward_type = 'badge'
            ");
            $stmt->execute([]);
            $reward = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($reward) {
                awardAchievement($veza, $user_id, $reward['id'], 'Interactive Explorer', 'Completed 10 interactive elements');
            }
        }

        if ($interactive_count >= 50) {
            $stmt = $veza->prepare("
                SELECT id FROM cyber_rewards
                WHERE requirement_type = 'achievement' AND requirement_value = 50 AND reward_type = 'badge'
            ");
            $stmt->execute([]);
            $reward = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($reward) {
                awardAchievement($veza, $user_id, $reward['id'], 'Interactive Expert', 'Completed 50 interactive elements');
            }
        }

        if ($interactive_count >= 100) {
            $stmt = $veza->prepare("
                SELECT id FROM cyber_rewards
                WHERE requirement_type = 'achievement' AND requirement_value = 100 AND reward_type = 'badge'
            ");
            $stmt->execute([]);
            $reward = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($reward) {
                awardAchievement($veza, $user_id, $reward['id'], 'Interactive Master', 'Completed 100 interactive elements');
            }
        }

    } catch (PDOException $e) {
        error_log("Achievement check error: " . $e->getMessage());
    }
}

// Helper function to award achievement
function awardAchievement($veza, $user_id, $reward_id, $name, $description) {
    try {
        // Check if already awarded
        $stmt = $veza->prepare("
            SELECT id FROM cyber_user_rewards
            WHERE user_id = ? AND reward_id = ?
        ");
        $stmt->execute([$user_id, $reward_id]);

        if (!$stmt->fetch()) {
            // Award the reward
            $stmt = $veza->prepare("
                INSERT INTO cyber_user_rewards (user_id, reward_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$user_id, $reward_id]);

            // Log achievement
            $stmt = $veza->prepare("
                INSERT INTO cyber_user_activity_log (user_id, activity_type, details)
                VALUES (?, 'achievement', ?)
            ");
            $stmt->execute([$user_id, "Unlocked achievement: $name"]);

            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Achievement award error: " . $e->getMessage());
        return false;
    }
}
?>
