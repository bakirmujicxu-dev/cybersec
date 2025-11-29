<?php
// index.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/konfiguracija/konfiguracija_softver.php';
require_once __DIR__ . '/funkcije/veza_do_baze.php';

$user_id = $_SESSION['user_id'];

// Get user stats - with error handling
$stmt = $veza->prepare("SELECT total_xp, level FROM cyber_users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user exists
if (!$user) {
    // User doesn't exist in database, log them out
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get categories with progress
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo NAZIV_APLIKACIJE; ?></title>
    <link rel="stylesheet" href="assets/cyber_style.css">
</head>
<body>
    <div class="matrix-bg"></div>
    <div class="cyber-grid"></div>

    <nav class="cyber-nav">
        <div class="nav-brand">
            <span class="brand-icon">🛡️</span>
            <span class="brand-text">CYBERGUARD</span>
        </div>
        <div class="nav-user">
            <span class="user-level">LVL <?php echo $user['level']; ?></span>
            <span class="user-xp"><?php echo $user['total_xp']; ?> XP</span>
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="logout.php" class="btn-logout">EXIT</a>
        </div>
    </nav>

    <main class="main-container">
        <section class="hero-section">
            <h1 class="hero-title glitch-text" data-text="CYBER SECURITY TRAINING">
                CYBER SECURITY TRAINING
            </h1>
            <p class="hero-subtitle">// Master the art of digital defense</p>
        </section>

        <section class="mode-selector">
            <div class="mode-card" onclick="location.href='quiz.php'">
                <div class="mode-icon">🔍</div>
                <h3 class="mode-title">QUIZ MODE</h3>
                <p class="mode-desc">Test your knowledge with flashcards</p>
                <div class="mode-stats">
                    <?php
                    $total_answered = array_sum(array_column($categories, 'questions_answered'));
                    $total_correct = array_sum(array_column($categories, 'questions_correct'));
                    $accuracy = $total_answered > 0 ? round(($total_correct / $total_answered) * 100) : 0;
                    ?>
                    <span class="stat"><?php echo $total_answered; ?> answered</span>
                    <span class="stat"><?php echo $accuracy; ?>% accuracy</span>
                </div>
                <button class="cyber-btn">START QUIZ</button>
            </div>

            <div class="mode-card" onclick="location.href='scenarios.php'">
                <div class="mode-icon">🕵️</div>
                <h3 class="mode-title">SCENARIO MODE</h3>
                <p class="mode-desc">Solve real-world cyber challenges</p>
                <div class="mode-stats">
                    <?php
                    $total_scenarios = array_sum(array_column($categories, 'scenarios_completed'));
                    ?>
                    <span class="stat"><?php echo $total_scenarios; ?> completed</span>
                    <span class="stat">Detective level</span>
                </div>
                <button class="cyber-btn">START MISSION</button>
            </div>

            <div class="mode-card" onclick="location.href='training.php'">
                <div class="mode-icon">📚</div>
                <h3 class="mode-title">TRAINING</h3>
                <p class="mode-desc">Learn through structured modules</p>
                <div class="mode-stats">
                    <?php
                    $total_modules = array_sum(array_column($categories, 'modules_completed'));
                    ?>
                    <span class="stat"><?php echo $total_modules; ?> modules</span>
                    <span class="stat">In progress</span>
                </div>
                <button class="cyber-btn">VIEW MODULES</button>
            </div>
        </section>

        <section class="categories-section">
            <h2 class="section-title">
                <span class="title-line"></span>
                CATEGORIES
                <span class="title-line"></span>
            </h2>

            <div class="categories-grid">
                <?php foreach ($categories as $cat): ?>
                <div class="category-card">
                    <div class="category-header">
                        <span class="category-icon"><?php echo $cat['icon']; ?></span>
                        <h3 class="category-name"><?php echo htmlspecialchars($cat['name']); ?></h3>
                    </div>
                    <p class="category-desc"><?php echo htmlspecialchars($cat['description']); ?></p>
                    <div class="category-progress">
                        <div class="progress-bar">
                            <?php
                            $max_xp = 500;
                            $progress = min(100, ($cat['category_xp'] / $max_xp) * 100);
                            ?>
                            <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <span class="progress-text"><?php echo $cat['category_xp']; ?> / <?php echo $max_xp; ?> XP</span>
                    </div>
                    <div class="category-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $cat['questions_answered']; ?></span>
                            <span class="stat-label">Questions</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $cat['scenarios_completed']; ?></span>
                            <span class="stat-label">Scenarios</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $cat['modules_completed']; ?></span>
                            <span class="stat-label">Modules</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script src="assets/cyber_app.js"></script>
</body>
</html>
