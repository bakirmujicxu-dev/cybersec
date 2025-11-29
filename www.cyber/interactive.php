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
