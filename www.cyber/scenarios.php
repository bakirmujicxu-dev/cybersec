<?php
// scenarios.php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . "/konfiguracija/konfiguracija_softver.php";
require_once __DIR__ . "/funkcije/veza_do_baze.php";

$user_id = $_SESSION["user_id"];

// Get all scenarios with category info
$stmt = $veza->query("
    SELECT s.*, c.name as category_name, c.icon as category_icon, c.color as category_color
    FROM cyber_scenarios s
    JOIN cyber_categories c ON s.category_id = c.id
    ORDER BY s.difficulty, s.id
");
$scenarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scenario Mode - <?php echo NAZIV_APLIKACIJE; ?></title>
    <link rel="stylesheet" href="assets/cyber_style.css">
</head>
<body>
    <div class="matrix-bg"></div>
    <div class="cyber-grid"></div>

    <nav class="cyber-nav">
        <div class="nav-brand">
            <a href="index.php" class="back-link">← BACK</a>
            <span class="brand-text">SCENARIO MODE</span>
        </div>
        <div class="nav-user">
            <span class="user-name"><?php echo htmlspecialchars(
                $_SESSION["username"],
            ); ?></span>
            <a href="logout.php" class="btn-logout">EXIT</a>
        </div>
    </nav>

    <main class="main-container">
        <div id="scenarioList" class="scenario-list">
            <div class="terminal-window large">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">AVAILABLE MISSIONS</span>
                </div>

                <div class="terminal-body">
                    <h2 class="section-title">SELECT A SCENARIO</h2>

                    <div class="scenarios-grid">
                        <?php foreach ($scenarios as $scenario): ?>
                        <div class="scenario-card" data-scenario="<?php echo $scenario[
                            "id"
                        ]; ?>">
                            <div class="scenario-header">
                                <span class="scenario-icon"><?php echo $scenario[
                                    "category_icon"
                                ]; ?></span>
                                <span class="scenario-difficulty difficulty-<?php echo $scenario[
                                    "difficulty"
                                ]; ?>">
                                    <?php echo strtoupper(
                                        $scenario["difficulty"],
                                    ); ?>
                                </span>
                            </div>
                            <h3 class="scenario-title"><?php echo htmlspecialchars(
                                $scenario["title"],
                            ); ?></h3>
                            <p class="scenario-desc"><?php echo htmlspecialchars(
                                $scenario["description"],
                            ); ?></p>
                            <div class="scenario-footer">
                                <span class="scenario-category"><?php echo htmlspecialchars(
                                    $scenario["category_name"],
                                ); ?></span>
                                <span class="scenario-reward">+<?php echo $scenario[
                                    "xp_reward"
                                ]; ?> XP</span>
                            </div>
                            <button class="cyber-btn small" onclick="startScenario(<?php echo $scenario[
                                "id"
                            ]; ?>)">
                                START MISSION
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="scenarioPlay" class="scenario-play hidden">
            <div class="scenario-container">
                <div class="scenario-progress">
                    <span class="progress-text">Step <span id="currentStep">1</span> / <span id="totalSteps">5</span></span>
                    <div class="progress-bar">
                        <div class="progress-fill" id="stepProgress" style="width: 20%"></div>
                    </div>
                </div>

                <div class="scenario-story">
                    <div class="story-header">
                        <h2 id="scenarioTitle" class="story-title">Scenario Title</h2>
                        <button id="quitScenario" class="cyber-btn small danger">QUIT</button>
                    </div>

                    <div class="story-content" id="storyContent">
                        <p>Loading story...</p>
                    </div>

                    <div class="story-choices" id="storyChoices">
                        <!-- Choices will be inserted here by JavaScript -->
                    </div>
                </div>

                <div id="choiceFeedback" class="choice-feedback hidden">
                    <div class="feedback-content">
                        <div class="feedback-icon" id="feedbackIcon">✓</div>
                        <p class="feedback-text" id="feedbackText"></p>
                        <button id="continueBtn" class="cyber-btn primary">CONTINUE</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="scenarioComplete" class="scenario-complete hidden">
            <div class="terminal-window large">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">MISSION COMPLETE</span>
                </div>

                <div class="terminal-body">
                    <h2 class="results-title glitch-text" data-text="MISSION ACCOMPLISHED">MISSION ACCOMPLISHED</h2>

                    <div class="completion-message" id="completionMessage">
                        <p>Great job, detective! You've successfully completed this scenario.</p>
                    </div>

                    <div class="results-grid">
                        <div class="result-card">
                            <div class="result-icon">🎯</div>
                            <div class="result-value" id="scenarioScore">100%</div>
                            <div class="result-label">Score</div>
                        </div>
                        <div class="result-card">
                            <div class="result-icon">⚡</div>
                            <div class="result-value" id="scenarioXP">50</div>
                            <div class="result-label">XP Earned</div>
                        </div>
                    </div>

                    <div class="results-actions">
                        <button onclick="location.reload()" class="cyber-btn primary">NEXT MISSION</button>
                        <button onclick="location.href='index.php'" class="cyber-btn ghost">RETURN HOME</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/scenario_app.js"></script>
</body>
</html>
