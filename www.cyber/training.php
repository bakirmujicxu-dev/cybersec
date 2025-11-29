<?php
// training.php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . "/konfiguracija/konfiguracija_softver.php";
require_once __DIR__ . "/funkcije/veza_do_baze.php";

$user_id = $_SESSION["user_id"];

// Get categories with modules
$stmt = $veza->query("
    SELECT c.*, COUNT(m.id) as module_count
    FROM cyber_categories c
    LEFT JOIN cyber_modules m ON c.id = m.category_id
    GROUP BY c.id
    ORDER BY c.id
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Modules - <?php echo NAZIV_APLIKACIJE; ?></title>
    <link rel="stylesheet" href="assets/cyber_style.css">
</head>
<body>
    <div class="matrix-bg"></div>
    <div class="cyber-grid"></div>

    <nav class="cyber-nav">
        <div class="nav-brand">
            <a href="index.php" class="back-link">← BACK</a>
            <span class="brand-text">TRAINING MODULES</span>
        </div>
        <div class="nav-user">
            <span class="user-name"><?php echo htmlspecialchars(
                $_SESSION["username"],
            ); ?></span>
            <a href="logout.php" class="btn-logout">EXIT</a>
        </div>
    </nav>

    <main class="main-container">
        <div id="categoryList" class="training-categories">
            <div class="terminal-window large">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">TRAINING CATEGORIES</span>
                </div>

                <div class="terminal-body">
                    <h2 class="section-title">SELECT CATEGORY</h2>

                    <div class="training-grid">
                        <?php foreach ($categories as $cat): ?>
                        <div class="training-card" onclick="loadModules(<?php echo $cat[
                            "id"
                        ]; ?>)">
                            <div class="training-icon"><?php echo $cat[
                                "icon"
                            ]; ?></div>
                            <h3 class="training-title"><?php echo htmlspecialchars(
                                $cat["name"],
                            ); ?></h3>
                            <p class="training-desc"><?php echo htmlspecialchars(
                                $cat["description"],
                            ); ?></p>
                            <div class="training-footer">
                                <span class="module-count"><?php echo $cat[
                                    "module_count"
                                ]; ?> Modules</span>
                            </div>
                            <button class="cyber-btn small">VIEW MODULES</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="moduleList" class="module-list hidden">
            <div class="terminal-window large">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">AVAILABLE MODULES</span>
                </div>

                <div class="terminal-body">
                    <button onclick="showCategories()" class="back-link">← BACK TO CATEGORIES</button>

                    <h2 class="section-title" id="categoryTitle">MODULES</h2>

                    <div id="modulesContainer" class="modules-container">
                        <!-- Modules will be loaded here by JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <div id="moduleView" class="module-view hidden">
            <div class="terminal-window large">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">TRAINING MODULE</span>
                </div>

                <div class="terminal-body">
                    <button id="backToModules" class="back-link">← BACK TO MODULES</button>

                    <div class="module-header">
                        <h2 class="module-title" id="moduleTitle">Module Title</h2>
                        <div class="module-meta">
                            <span class="meta-item">⏱️ <span id="moduleDuration">10</span> min</span>
                            <span class="meta-item">⚡ +<span id="moduleXP">25</span> XP</span>
                        </div>
                    </div>

                    <div class="module-content" id="moduleContent">
                        <!-- Module content will be loaded here -->
                    </div>

                    <div class="module-actions">
                        <button id="completeModule" class="cyber-btn primary large">
                            COMPLETE MODULE
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="moduleComplete" class="module-complete hidden">
            <div class="terminal-window large">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">MODULE COMPLETE</span>
                </div>

                <div class="terminal-body">
                    <h2 class="results-title glitch-text" data-text="WELL DONE">WELL DONE</h2>

                    <p class="completion-text">You've successfully completed this training module!</p>

                    <div class="results-grid">
                        <div class="result-card">
                            <div class="result-icon">⚡</div>
                            <div class="result-value" id="earnedXP">25</div>
                            <div class="result-label">XP Earned</div>
                        </div>
                    </div>

                    <div class="results-actions">
                        <button id="nextModule" class="cyber-btn primary">NEXT MODULE</button>
                        <button onclick="showCategories()" class="cyber-btn ghost">BACK TO CATEGORIES</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/training_app.js"></script>
</body>
</html>
