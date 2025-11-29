<?php
// quiz.php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . "/konfiguracija/konfiguracija_softver.php";
require_once __DIR__ . "/funkcije/veza_do_baze.php";

$user_id = $_SESSION["user_id"];

// Get categories for filter
$stmt = $veza->query("SELECT * FROM cyber_categories ORDER BY id");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Mode - <?php echo NAZIV_APLIKACIJE; ?></title>
    <link rel="stylesheet" href="assets/cyber_style.css">
</head>
<body>
    <div class="matrix-bg"></div>
    <div class="cyber-grid"></div>

    <nav class="cyber-nav">
        <div class="nav-brand">
            <a href="index.php" class="back-link">← BACK</a>
            <span class="brand-text">QUIZ MODE</span>
        </div>
        <div class="nav-user">
            <span class="user-name"><?php echo htmlspecialchars(
                $_SESSION["username"],
            ); ?></span>
            <a href="logout.php" class="btn-logout">EXIT</a>
        </div>
    </nav>

    <main class="main-container">
        <div id="quizSetup" class="quiz-setup">
            <div class="terminal-window large">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">QUIZ CONFIGURATION</span>
                </div>

                <div class="terminal-body">
                    <h2 class="section-title">SELECT CATEGORY</h2>

                    <div class="category-select">
                        <button class="category-btn active" data-category="all">
                            <span class="cat-icon">🎯</span>
                            <span class="cat-name">ALL CATEGORIES</span>
                        </button>
                        <?php foreach ($categories as $cat): ?>
                        <button class="category-btn" data-category="<?php echo $cat[
                            "id"
                        ]; ?>">
                            <span class="cat-icon"><?php echo $cat[
                                "icon"
                            ]; ?></span>
                            <span class="cat-name"><?php echo htmlspecialchars(
                                $cat["name"],
                            ); ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <h2 class="section-title" style="margin-top: 2rem;">SELECT DIFFICULTY</h2>

                    <div class="difficulty-select">
                        <button class="difficulty-btn active" data-difficulty="all">
                            <span class="diff-icon">⚡</span>
                            <span class="diff-name">ALL LEVELS</span>
                        </button>
                        <button class="difficulty-btn easy" data-difficulty="easy">
                            <span class="diff-icon">🟢</span>
                            <span class="diff-name">EASY</span>
                        </button>
                        <button class="difficulty-btn medium" data-difficulty="medium">
                            <span class="diff-icon">🟡</span>
                            <span class="diff-name">MEDIUM</span>
                        </button>
                        <button class="difficulty-btn hard" data-difficulty="hard">
                            <span class="diff-icon">🔴</span>
                            <span class="diff-name">HARD</span>
                        </button>
                    </div>

                    <button id="startQuiz" class="cyber-btn primary large">
                        <span class="btn-text">START QUIZ</span>
                    </button>
                </div>
            </div>
        </div>

        <div id="quizArea" class="quiz-area hidden">
            <div class="quiz-header">
                <div class="quiz-info">
                    <span class="quiz-stat">Question <span id="currentQuestion">1</span> / <span id="totalQuestions">10</span></span>
                    <span class="quiz-stat">Score: <span id="currentScore">0</span> XP</span>
                </div>
                <div class="quiz-actions">
                    <button id="quitQuiz" class="cyber-btn small danger">QUIT</button>
                </div>
            </div>

            <div class="flashcard-container">
                <div class="flashcard" id="flashcard">
                    <div class="flashcard-inner">
                        <div class="flashcard-front">
                            <div class="card-category" id="cardCategory">PHISHING</div>
                            <h2 class="card-question" id="cardQuestion">Loading question...</h2>
                            <div class="card-difficulty" id="cardDifficulty">MEDIUM</div>
                        </div>
                        <div class="flashcard-back">
                            <div class="card-answer" id="cardAnswer">Loading answer...</div>
                        </div>
                    </div>
                </div>

                <div class="quiz-controls">
                    <button id="knowBtn" class="cyber-btn success" disabled>
                        ✓ I KNOW THIS
                    </button>
                    <button id="dontKnowBtn" class="cyber-btn danger" disabled>
                        ✗ I DON'T KNOW
                    </button>
                </div>

                <button id="nextBtn" class="cyber-btn ghost" disabled>
                    NEXT QUESTION →
                </button>
            </div>

            <div class="quiz-progress">
                <div class="progress-bar">
                    <div class="progress-fill correct" id="progressCorrect" style="width: 0%"></div>
                    <div class="progress-fill incorrect" id="progressIncorrect" style="width: 0%"></div>
                </div>
                <div class="progress-stats">
                    <span class="stat correct">✓ <span id="correctCount">0</span></span>
                    <span class="stat incorrect">✗ <span id="incorrectCount">0</span></span>
                    <span class="stat accuracy">Accuracy: <span id="accuracy">0</span>%</span>
                </div>
            </div>
        </div>

        <div id="quizResults" class="quiz-results hidden">
            <div class="terminal-window large">
                <div class="terminal-header">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="terminal-title">QUIZ COMPLETE</span>
                </div>

                <div class="terminal-body">
                    <h2 class="results-title glitch-text" data-text="MISSION COMPLETE">MISSION COMPLETE</h2>

                    <div class="results-grid">
                        <div class="result-card">
                            <div class="result-icon">✓</div>
                            <div class="result-value" id="finalCorrect">0</div>
                            <div class="result-label">Correct</div>
                        </div>
                        <div class="result-card">
                            <div class="result-icon">✗</div>
                            <div class="result-value" id="finalIncorrect">0</div>
                            <div class="result-label">Incorrect</div>
                        </div>
                        <div class="result-card">
                            <div class="result-icon">📊</div>
                            <div class="result-value" id="finalAccuracy">0%</div>
                            <div class="result-label">Accuracy</div>
                        </div>
                        <div class="result-card">
                            <div class="result-icon">⚡</div>
                            <div class="result-value" id="finalXP">0</div>
                            <div class="result-label">XP Earned</div>
                        </div>
                    </div>

                    <div class="results-actions">
                        <button onclick="location.reload()" class="cyber-btn primary">TRY AGAIN</button>
                        <button onclick="location.href='index.php'" class="cyber-btn ghost">RETURN HOME</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/quiz_app.js"></script>
</body>
</html>
