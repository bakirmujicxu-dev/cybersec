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
