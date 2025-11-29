<?php
// api/save_user_preferences.php - Save user preferences
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

require_once __DIR__ . "/../funkcije/veza_do_baze.php";

try {
    // Start transaction
    $veza->beginTransaction();

    // Save each preference
    foreach ($data as $key => $value) {
        // Update or insert preference
        $stmt = $veza->prepare("
            INSERT INTO cyber_user_preferences (user_id, preference_key, preference_value)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            preference_value = VALUES(preference_value),
            updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$user_id, $key, $value]);
    }

    // Commit transaction
    $veza->commit();

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    // Rollback on error
    $veza->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
```

<file_path>
www.cyber/api/get_user_preferences.php
</file_path>

<edit_description>
Fix JSON syntax in get_user_preferences.php
</edit_description>
```

```php
<?php
// api/get_user_preferences.php - Get user preferences
session_start();
header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id'];

require_once __DIR__ . "/../funkcije/veza_do_baze.php";

try {
    // Get all preferences for user
    $stmt = $veza->prepare("
        SELECT preference_key, preference_value
        FROM cyber_user_preferences
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);

    $preferences = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $preferences[$row['preference_key']] = $row['preference_value'];
    }

    echo json_encode($preferences);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
```

<file_path>
www.cyber/api/save_interactive_completion.php
</file_path>

<edit_description>
Fix JSON syntax in save_interactive_completion.php
</edit_description>
```

```php
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
```

<file_path>
www.cyber/api/get_daily_challenges.php
</file_path>

<edit_description>
Fix JSON syntax in get_daily_challenges.php
</edit_description>
```

```php
<?php
// api/get_daily_challenges.php - Get daily challenges for PWA
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../funkcije/veza_do_baze.php";

try {
    // Get today's and upcoming challenges
    $stmt = $veza->query("
        SELECT
            dc.*,
            c.name as category_name,
            c.icon as category_icon,
            CASE
                WHEN dc.date = CURDATE() THEN 'today'
                WHEN dc.date = CURDATE() + INTERVAL 1 DAY THEN 'tomorrow'
                ELSE 'upcoming'
            END as status
        FROM cyber_daily_challenges dc
        JOIN cyber_categories c ON dc.category_id = c.id
        WHERE dc.date BETWEEN CURDATE() AND CURDATE() + INTERVAL 6 DAY
        ORDER BY dc.date ASC
        LIMIT 7
    ");
    $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "challenges" => $challenges
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>
```

<file_path>
www.cyber/profile.php
</file_path>

<edit_description>
Fix JSON syntax in profile.php
</edit_description>
```

```php
<?php
// profile.php - User profile page
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/konfiguracija/konfiguracija_softver.php';
require_once __DIR__ . '/funkcije/veza_do_baze.php';
require_once __DIR__ . '/funkcije/helpers.php';

$user_id = $_SESSION['user_id'];

// Get user information
$stmt = $veza->prepare("SELECT * FROM cyber_users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user progress
$stmt = $veza->prepare("
    SELECT
        c.*,
        COALESCE(up.questions_answered, 0) as questions_answered,
        COALESCE(up.questions_correct, 0) as questions_correct,
        COALESCE(up.scenarios_completed, 0) as scenarios_completed,
        COALESCE(up.modules_completed, 0) as modules_completed,
        COALESCE(up.category_xp, 0) as category_xp
    FROM cyber_categories c
    LEFT JOIN cyber_user_progress up ON c.id = up.category_id AND up.user_id = ?
    ORDER BY c.id
");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user rewards
$stmt = $veza->prepare("
    SELECT r.*
    FROM cyber_rewards r
    JOIN cyber_user_rewards ur ON r.id = ur.reward_id
    WHERE ur.user_id = ?
    ORDER BY r.reward_type, r.requirement_value
");
$stmt->execute([$user_id]);
$user_rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user streak information
$stmt = $veza->prepare("
    SELECT current_streak, longest_streak, last_activity_date
    FROM cyber_user_streaks
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$streak = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent activity
$stmt = $veza->prepare("
    SELECT activity_type, details, xp_earned, created_at
    FROM cyber_user_activity_log
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_questions = array_sum(array_column($categories, 'questions_answered'));
$total_correct = array_sum(array_column($categories, 'questions_correct'));
$total_scenarios = array_sum(array_column($categories, 'scenarios_completed'));
$total_modules = array_sum(array_column($categories, 'modules_completed'));
$accuracy = $total_questions > 0 ? round(($total_correct / $total_questions) * 100) : 0;

// Get user preferences
$stmt = $veza->prepare("
    SELECT preference_key, preference_value
    FROM cyber_user_preferences
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$preferences = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $preferences[$row['preference_key']] = $row['preference_value'];
}

// Get available rewards (not unlocked)
$stmt = $veza->prepare("
    SELECT r.*
    FROM cyber_rewards r
    WHERE r.id NOT IN (
        SELECT reward_id FROM cyber_user_rewards WHERE user_id = ?
    )
    ORDER BY r.reward_type, r.requirement_value
");
$stmt->execute([$user_id]);
$available_rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate XP needed for next level
$xp_for_next_level = xp_for_next_level($user['level']);
$progress_to_next = min(100, ($user['total_xp'] % 100));

// Get today's daily challenge
$stmt = $veza->prepare("
    SELECT *
    FROM cyber_daily_challenges
    WHERE date = CURDATE()
    LIMIT 1
");
$stmt->execute([]);
$daily_challenge = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user completed today's challenge
$challenge_completed = false;
if ($daily_challenge) {
    $stmt = $veza->prepare("
        SELECT id
        FROM cyber_daily_challenge_completions
        WHERE user_id = ? AND challenge_id = ?
    ");
    $stmt->execute([$user_id, $daily_challenge['id']]);
    $challenge_completed = $stmt->fetch() !== false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo NAZIV_APLIKACIJE; ?></title>
    <link rel="stylesheet" href="assets/cyber_style.css">
    <link rel="stylesheet" href="assets/profile_style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#1f2937">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body>
    <div class="matrix-bg"></div>
    <div class="cyber-grid"></div>

    <nav class="cyber-nav">
        <div class="nav-brand">
            <a href="index.php" class="back-link">← BACK</a>
            <span class="brand-text">PROFILE</span>
        </div>
        <div class="nav-user">
            <span class="user-level">LVL <?php echo $user['level']; ?></span>
            <span class="user-xp"><?php echo $user['total_xp']; ?> XP</span>
            <span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span>
            <a href="logout.php" class="btn-logout">EXIT</a>
        </div>
    </nav>

    <main class="main-container profile-container">
        <!-- Profile Header -->
        <section class="profile-header">
            <div class="profile-avatar">
                <div class="avatar-circle">
                    <?php
                    $avatar = $preferences['avatar'] ?? 'default';
                    $avatar_icons = [
                        'default' => '👤',
                        'hacker' => '👨‍💻',
                        'expert' => '👨‍💼',
                        'agent' => '🕵️',
                        'ninja' => '🥷',
                        'wizard' => '🧙'
                    ];
                    echo $avatar_icons[$avatar] ?? '👤';
                    ?>
                </div>
                <div class="level-indicator">
                    <span class="level-number"><?php echo $user['level']; ?></span>
                </div>
            </div>
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h1>
                <p class="profile-title">Cyber Security Learner</p>
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $user['total_xp']; ?></span>
                        <span class="stat-label">Total XP</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $user['level']; ?></span>
                        <span class="stat-label">Level</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $total_questions; ?></span>
                        <span class="stat-label">Questions</span>
                    </div>
                </div>
            </div>
            <div class="profile-actions">
                <button class="btn-cyber" id="settings-btn">⚙️ Settings</button>
                <button class="btn-cyber" id="achievements-btn">🏆 Achievements</button>
            </div>
        </section>

        <!-- Level Progress -->
        <section class="progress-section">
            <div class="terminal-window">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">LEVEL PROGRESS</span>
                </div>
                <div class="terminal-body">
                    <div class="level-progress">
                        <div class="progress-bar large">
                            <div class="progress-fill" style="width: <?php echo $progress_to_next; ?>%"></div>
                        </div>
                        <div class="progress-text">
                            <?php echo $user['total_xp']; ?> / <?php echo $xp_for_next_level; ?> XP
                        </div>
                        <div class="progress-text">
                            <?php echo 100 - $progress_to_next; ?> XP to Level <?php echo $user['level'] + 1; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Daily Challenge -->
        <section class="daily-challenge-section">
            <div class="terminal-window">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">DAILY CHALLENGE</span>
                </div>
                <div class="terminal-body">
                    <?php if ($daily_challenge): ?>
                        <div class="challenge-card">
                            <div class="challenge-header">
                                <span class="challenge-difficulty <?php echo $daily_challenge['difficulty']; ?>">
                                    <?php echo get_difficulty_emoji($daily_challenge['difficulty']); ?>
                                    <?php echo ucfirst($daily_challenge['difficulty']); ?>
                                </span>
                                <span class="challenge-xp">+<?php echo $daily_challenge['xp_reward']; ?> XP</span>
                            </div>
                            <h3 class="challenge-title"><?php echo htmlspecialchars($daily_challenge['title']); ?></h3>
                            <p class="challenge-description"><?php echo htmlspecialchars($daily_challenge['description']); ?></p>
                            <div class="challenge-actions">
                                <?php if ($challenge_completed): ?>
                                    <span class="completed-badge">✓ COMPLETED</span>
                                <?php else: ?>
                                    <?php if ($daily_challenge['challenge_type'] == 'quiz'): ?>
                                        <button class="btn-cyber" onclick="startDailyChallenge('quiz', <?php echo $daily_challenge['id']; ?>)">Start Quiz</button>
                                    <?php elseif ($daily_challenge['challenge_type'] == 'scenario'): ?>
                                        <button class="btn-cyber" onclick="location.href='scenarios.php?daily=<?php echo $daily_challenge['id']; ?>'">Start Scenario</button>
                                    <?php elseif ($daily_challenge['challenge_type'] == 'interactive'): ?>
                                        <button class="btn-cyber" onclick="location.href='interactive.php?challenge=<?php echo $daily_challenge['id']; ?>'">Start Interactive</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>No daily challenge available today. Check back tomorrow!</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Streak Counter -->
        <section class="streak-section">
            <div class="terminal-window">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">LEARNING STREAK</span>
                </div>
                <div class="terminal-body">
                    <div class="streak-container">
                        <div class="streak-display">
                            <div class="streak-number"><?php echo $streak['current_streak'] ?? 0; ?></div>
                            <div class="streak-label">Current Streak</div>
                        </div>
                        <div class="streak-display">
                            <div class="streak-number"><?php echo $streak['longest_streak'] ?? 0; ?></div>
                            <div class="streak-label">Longest Streak</div>
                        </div>
                    </div>
                    <div class="streak-flame">🔥</div>
                </div>
            </div>
        </section>

        <!-- Category Progress -->
        <section class="categories-section">
            <div class="terminal-window">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">CATEGORY PROGRESS</span>
                </div>
                <div class="terminal-body">
                    <div class="categories-grid">
                        <?php foreach ($categories as $cat): ?>
                        <div class="category-progress-card">
                            <div class="category-header">
                                <span class="category-icon"><?php echo $cat['icon']; ?></span>
                                <span class="category-name"><?php echo htmlspecialchars($cat['name']); ?></span>
                            </div>
                            <div class="category-stats">
                                <div class="stat">
                                    <span class="stat-value"><?php echo $cat['category_xp']; ?></span>
                                    <span class="stat-label">XP</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-value"><?php echo $cat['questions_answered']; ?></span>
                                    <span class="stat-label">Questions</span>
                                </div>
                            </div>
                            <div class="progress-bar">
                                <?php
                                $max_xp = 500;
                                $progress = min(100, ($cat['category_xp'] / $max_xp) * 100);
                                ?>
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent Activity -->
        <section class="activity-section">
            <div class="terminal-window">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">RECENT ACTIVITY</span>
                </div>
                <div class="terminal-body">
                    <div class="activity-list">
                        <?php if (empty($recent_activity)): ?>
                            <p>No recent activity. Start learning to see your progress here!</p>
                        <?php else: ?>
                            <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <span class="activity-type"><?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?></span>
                                <span class="activity-details"><?php echo htmlspecialchars($activity['details']); ?></span>
                                <span class="activity-xp">+<?php echo $activity['xp_earned']; ?> XP</span>
                                <span class="activity-time"><?php echo time_ago($activity['created_at']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Rewards/Achievements -->
        <section class="rewards-section">
            <div class="terminal-window">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">ACHIEVEMENTS</span>
                </div>
                <div class="terminal-body">
                    <div class="rewards-container">
                        <?php if (empty($user_rewards)): ?>
                            <p>No achievements yet. Complete activities to unlock rewards!</p>
                        <?php else: ?>
                            <?php foreach ($user_rewards as $reward): ?>
                            <div class="reward-badge <?php echo $reward['reward_type']; ?>" style="background-color: <?php echo $reward['color']; ?>20; border-color: <?php echo $reward['color']; ?>;">
                                <span class="reward-icon"><?php echo $reward['icon']; ?></span>
                                <div class="reward-info">
                                    <div class="reward-name"><?php echo htmlspecialchars($reward['name']); ?></div>
                                    <div class="reward-description"><?php echo htmlspecialchars($reward['description']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Settings Modal -->
    <div class="modal" id="settings-modal">
        <div class="modal-content terminal-window">
            <div class="terminal-header">
                <span class="terminal-dot red"></span>
                <span class="terminal-dot yellow"></span>
                <span class="terminal-dot green"></span>
                <span class="terminal-title">SETTINGS</span>
                <button class="close-btn" onclick="closeModal('settings-modal')">×</button>
            </div>
            <div class="modal-body terminal-body">
                <form id="settings-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="theme">Theme</label>
                        <select id="theme" name="theme">
                            <option value="default" <?php echo ($preferences['theme'] ?? 'default') == 'default' ? 'selected' : ''; ?>>Default</option>
                            <option value="dark" <?php echo ($preferences['theme'] ?? 'default') == 'dark' ? 'selected' : ''; ?>>Dark</option>
                            <option value="matrix" <?php echo ($preferences['theme'] ?? 'default') == 'matrix' ? 'selected' : ''; ?>>Matrix</option>
                            <option value="neon" <?php echo ($preferences['theme'] ?? 'default') == 'neon' ? 'selected' : ''; ?>>Neon</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="avatar">Avatar</label>
                        <select id="avatar" name="avatar">
                            <option value="default" <?php echo ($preferences['avatar'] ?? 'default') == 'default' ? 'selected' : ''; ?>>Default</option>
                            <option value="hacker" <?php echo ($preferences['avatar'] ?? 'default') == 'hacker' ? 'selected' : ''; ?>>Hacker</option>
                            <option value="expert" <?php echo ($preferences['avatar'] ?? 'default') == 'expert' ? 'selected' : ''; ?>>Security Expert</option>
                            <option value="agent" <?php echo ($preferences['avatar'] ?? 'default') == 'agent' ? 'selected' : ''; ?>>Cyber Agent</option>
                            <option value="ninja" <?php echo ($preferences['avatar'] ?? 'default') == 'ninja' ? 'selected' : ''; ?>>Ninja</option>
                            <option value="wizard" <?php echo ($preferences['avatar'] ?? 'default') == 'wizard' ? 'selected' : ''; ?>>Wizard</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="notifications">Push Notifications</label>
                        <div class="toggle-switch">
                            <input type="checkbox" id="notifications" name="notifications" <?php echo ($preferences['notifications'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </div>
                    </div>
                    <button type="submit" class="btn-cyber">Save Settings</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Achievements Modal -->
    <div class="modal" id="achievements-modal">
        <div class="modal-content terminal-window large">
            <div class="terminal-header">
                <span class="terminal-dot red"></span>
                <span class="terminal-dot yellow"></span>
                <span class="terminal-dot green"></span>
                <span class="terminal-title">ALL ACHIEVEMENTS</span>
                <button class="close-btn" onclick="closeModal('achievements-modal')">×</button>
            </div>
            <div class="modal-body terminal-body">
                <div class="achievements-grid">
                    <?php
                    $all_rewards = array_merge($user_rewards, $available_rewards);
                    foreach ($all_rewards as $reward):
                        $unlocked = in_array($reward['id'], array_column($user_rewards, 'id'));
                    ?>
                    <div class="achievement-card <?php echo $unlocked ? 'unlocked' : 'locked'; ?>" style="<?php echo $unlocked ? '' : 'opacity: 0.5;'; ?>">
                        <div class="achievement-badge" style="background-color: <?php echo $reward['color']; ?>20; border-color: <?php echo $reward['color']; ?>;">
                            <span class="achievement-icon"><?php echo $reward['icon']; ?></span>
                        </div>
                        <div class="achievement-info">
                            <div class="achievement-name"><?php echo htmlspecialchars($reward['name']); ?></div>
                            <div class="achievement-description"><?php echo htmlspecialchars($reward['description']); ?></div>
                            <div class="achievement-requirement">
                                <?php
                                switch($reward['requirement_type']) {
                                    case 'level': echo 'Level ' . $reward['requirement_value']; break;
                                    case 'streak': echo $reward['requirement_value'] . ' day streak'; break;
                                    case 'achievement': echo 'Complete ' . $reward['requirement_value'] . ' achievements'; break;
                                    case 'xp': echo $reward['requirement_value'] . ' XP'; break;
                                }
                                ?>
                            </div>
                        </div>
                        <?php if ($unlocked): ?>
                            <div class="achievement-status">UNLOCKED</div>
                        <?php else: ?>
                            <div class="achievement-status">LOCKED</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/cyber_app.js"></script>
    <script src="assets/profile_app.js"></script>
    <script src="assets/pwa_app.js"></script>
</body>
</html>
```

<file_path>
www.cyber/interactive.php
</file_path>

<edit_description>
Fix JSON syntax in interactive.php
</edit_description>
```

```php
<?php
// interactive.php - Interactive learning elements page
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/konfiguracija/konfiguracija_softver.php';
require_once __DIR__ . '/funkcije/veza_do_baze.php';
require_once __DIR__ . '/funkcije/helpers.php';

$user_id = $_SESSION['user_id'];

// Get interactive elements
$stmt = $veza->prepare("
    SELECT ie.*, c.name as category_name, c.icon as category_icon
    FROM cyber_interactive_elements ie
    JOIN cyber_categories c ON ie.category_id = c.id
    ORDER BY ie.difficulty, ie.id
");
$stmt->execute([]);
$elements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get daily challenge if specified
$dailyChallenge = null;
if (isset($_GET['challenge'])) {
    $challengeId = (int) $_GET['challenge'];
    $stmt = $veza->prepare("
        SELECT dc.*, c.name as category_name, c.icon as category_icon
        FROM cyber_daily_challenges dc
        JOIN cyber_categories c ON dc.category_id = c.id
        WHERE dc.id = ?
    ");
    $stmt->execute([$challengeId]);
    $dailyChallenge = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get user statistics
$stmt = $veza->prepare("SELECT total_xp, level FROM cyber_users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive Learning - <?php echo NAZIV_APLIKACIJE; ?></title>
    <link rel="stylesheet" href="assets/cyber_style.css">
    <link rel="stylesheet" href="assets/interactive_style.css">
    <link rel="manifest" href="manifest.json">
</head>
<body>
    <div class="matrix-bg"></div>
    <div class="cyber-grid"></div>

    <nav class="cyber-nav">
        <div class="nav-brand">
            <a href="index.php" class="back-link">← BACK</a>
            <span class="brand-text">INTERACTIVE LEARNING</span>
        </div>
        <div class="nav-user">
            <span class="user-level">LVL <?php echo $user['level']; ?></span>
            <span class="user-xp"><?php echo $user['total_xp']; ?> XP</span>
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="logout.php" class="btn-logout">EXIT</a>
        </div>
    </nav>

    <main class="main-container">
        <!-- Daily Challenge Section -->
        <?php if ($dailyChallenge): ?>
        <section class="daily-challenge-banner">
            <div class="terminal-window">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">DAILY CHALLENGE</span>
                </div>
                <div class="terminal-body">
                    <div class="challenge-info">
                        <div class="challenge-header">
                            <span class="challenge-category">
                                <?php echo $dailyChallenge['category_icon']; ?> <?php echo htmlspecialchars($dailyChallenge['category_name']); ?>
                            </span>
                            <span class="challenge-difficulty <?php echo $dailyChallenge['difficulty']; ?>">
                                <?php echo get_difficulty_emoji($dailyChallenge['difficulty']); ?>
                                <?php echo ucfirst($dailyChallenge['difficulty']); ?>
                            </span>
                            <span class="challenge-xp">+<?php echo $dailyChallenge['xp_reward']; ?> XP</span>
                        </div>
                        <h3 class="challenge-title"><?php echo htmlspecialchars($dailyChallenge['title']); ?></h3>
                        <p class="challenge-description"><?php echo htmlspecialchars($dailyChallenge['description']); ?></p>
                        <div class="challenge-actions">
                            <button class="btn-cyber" onclick="startChallenge('<?php echo $dailyChallenge['challenge_type']; ?>', <?php echo $dailyChallenge['id']; ?>)">
                                Start Challenge
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Interactive Elements Section -->
        <section class="interactive-section">
            <div class="terminal-window">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">INTERACTIVE ELEMENTS</span>
                </div>
                <div class="terminal-body">
                    <div class="elements-filter">
                        <select id="category-filter" class="filter-select">
                            <option value="all">All Categories</option>
                            <?php
                            $categories = $veza->query("SELECT * FROM cyber_categories ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($categories as $cat):
                            ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['icon']; ?> <?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="difficulty-filter" class="filter-select">
                            <option value="all">All Difficulties</option>
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                        <select id="type-filter" class="filter-select">
                            <option value="all">All Types</option>
                            <option value="drag_drop">Drag & Drop</option>
                            <option value="simulation">Simulation</option>
                            <option value="memory_game">Memory Game</option>
                            <option value="code_challenge">Code Challenge</option>
                        </select>
                    </div>

                    <div class="elements-grid">
                        <?php foreach ($elements as $element): ?>
                        <div class="interactive-element-card"
                             data-category="<?php echo $element['category_id']; ?>"
                             data-difficulty="<?php echo $element['difficulty']; ?>"
                             data-type="<?php echo $element['element_type']; ?>">
                            <div class="element-header">
                                <span class="element-category">
                                    <?php echo $element['category_icon']; ?>
                                    <?php echo htmlspecialchars($element['category_name']); ?>
                                </span>
                                <span class="element-difficulty <?php echo $element['difficulty']; ?>">
                                    <?php echo get_difficulty_emoji($element['difficulty']); ?>
                                </span>
                            </div>
                            <h3 class="element-title"><?php echo htmlspecialchars($element['title']); ?></h3>
                            <p class="element-description"><?php echo htmlspecialchars($element['description']); ?></p>
                            <div class="element-footer">
                                <span class="element-type"><?php echo formatElementType($element['element_type']); ?></span>
                                <span class="element-xp">+<?php echo $element['xp_reward']; ?> XP</span>
                                <span class="element-time"><?php echo $element['time_limit']; ?>s</span>
                            </div>
                            <button class="btn-cyber element-btn" onclick="startInteractiveElement(<?php echo $element['id']; ?>, '<?php echo $element['element_type']; ?>')">
                                START
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Interactive Element Modal -->
        <div class="modal" id="element-modal">
            <div class="modal-content terminal-window large">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title" id="modal-title">INTERACTIVE ELEMENT</span>
                    <button class="close-btn" onclick="closeModal('element-modal')">×</button>
                </div>
                <div class="modal-body terminal-body">
                    <div id="element-content"></div>
                </div>
            </div>
        </div>

        <!-- Result Modal -->
        <div class="modal" id="result-modal">
            <div class="modal-content terminal-window">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">RESULT</span>
                    <button class="close-btn" onclick="closeModal('result-modal')">×</button>
                </div>
                <div class="modal-body terminal-body">
                    <div id="result-content"></div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/cyber_app.js"></script>
    <script src="assets/interactive_app.js"></script>
    <script>
        // Store interactive elements data for JavaScript
        const elements = <?php echo json_encode($elements); ?>;
        const dailyChallenge = <?php echo json_encode($dailyChallenge); ?>;
        const userId = <?php echo $user_id; ?>;
    </script>
</body>
</html>

<?php
// Helper function to format element type
function formatElementType($type) {
    switch ($type) {
        case 'drag_drop':
            return 'Drag & Drop';
        case 'simulation':
            return 'Simulation';
        case 'memory_game':
            return 'Memory Game';
        case 'code_challenge':
            return 'Code Challenge';
        case 'quiz':
            return 'Quiz';
        default:
            return ucfirst(str_replace('_', ' ', $type));
    }
}
?>
