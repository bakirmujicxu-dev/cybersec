Sada kreiram dodatne helper fajlove:
````php
<?php
// funkcije/helpers.php - Helper functions

/**
 * Sanitize user input
 */
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, "UTF-8");
    return $data;
}

/**
 * Calculate user level based on XP
 */
function calculate_level($total_xp)
{
    return floor($total_xp / 100) + 1;
}

/**
 * Calculate XP needed for next level
 */
function xp_for_next_level($current_level)
{
    return $current_level * 100;
}

/**
 * Format time ago
 */
function time_ago($datetime)
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return $diff . " seconds ago";
    } elseif ($diff < 3600) {
        return floor($diff / 60) . " minutes ago";
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . " hours ago";
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . " days ago";
    } else {
        return date("Y-m-d H:i", $time);
    }
}

/**
 * Get difficulty color
 */
function get_difficulty_color($difficulty)
{
    switch ($difficulty) {
        case "easy":
            return "#10b981";
        case "medium":
            return "#fbbf24";
        case "hard":
            return "#ef4444";
        default:
            return "#3b82f6";
    }
}

/**
 * Get difficulty emoji
 */
function get_difficulty_emoji($difficulty)
{
    switch ($difficulty) {
        case "easy":
            return "🟢";
        case "medium":
            return "🟡";
        case "hard":
            return "🔴";
        default:
            return "⚪";
    }
}

/**
 * Check if user has completed achievement
 */
function check_achievements($veza, $user_id)
{
    $achievements = [];

    // Get user progress
    $stmt = $veza->prepare("SELECT * FROM cyber_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return $achievements;
    }

    // First Quiz Achievement
    $stmt = $veza->prepare(
        "SELECT COUNT(*) FROM cyber_quiz_sessions WHERE user_id = ?",
    );
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() == 1) {
        $achievements[] = [
            "type" => "first_quiz",
            "name" => "First Steps",
            "description" => "Completed your first quiz",
        ];
    }

    // Level 5 Achievement
    if ($user["level"] >= 5) {
        $achievements[] = [
            "type" => "level_5",
            "name" => "Rising Star",
            "description" => "Reached level 5",
        ];
    }

    // Level 10 Achievement
    if ($user["level"] >= 10) {
        $achievements[] = [
            "type" => "level_10",
            "name" => "Cyber Defender",
            "description" => "Reached level 10",
        ];
    }

    // 100 Questions Achievement
    $stmt = $veza->prepare("
        SELECT SUM(questions_answered) as total
        FROM cyber_user_progress
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $total_questions = $stmt->fetch(PDO::FETCH_ASSOC)["total"];
    if ($total_questions >= 100) {
        $achievements[] = [
            "type" => "questions_100",
            "name" => "Knowledge Seeker",
            "description" => "Answered 100 questions",
        ];
    }

    // All Categories Achievement
    $stmt = $veza->prepare("
        SELECT COUNT(DISTINCT category_id) as categories
        FROM cyber_user_progress
        WHERE user_id = ? AND questions_answered > 0
    ");
    $stmt->execute([$user_id]);
    $categories = $stmt->fetch(PDO::FETCH_ASSOC)["categories"];
    $total_categories = $veza
        ->query("SELECT COUNT(*) FROM cyber_categories")
        ->fetchColumn();
    if ($categories >= $total_categories) {
        $achievements[] = [
            "type" => "all_categories",
            "name" => "Jack of All Trades",
            "description" => "Explored all categories",
        ];
    }

    return $achievements;
}

/**
 * Award achievement to user
 */
function award_achievement(
    $veza,
    $user_id,
    $badge_type,
    $badge_name,
    $badge_description,
) {
    // Helper function to award achievement
    function awardAchievement($veza, $user_id, $reward_id, $name, $description) {
        try {
            // Check if already awarded
            $stmt = $veza->prepare(
                "SELECT id FROM cyber_user_rewards WHERE user_id = ? AND reward_id = ?",
            );
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

/**
* Log user activity
*/
function log_activity($veza, $user_id, $activity_type, $details = "", $xp_earned = 0) {
try {
    $stmt = $veza->prepare("
        INSERT INTO cyber_user_activity_log (user_id, activity_type, details, xp_earned)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $activity_type, $details, $xp_earned]);
    return true;
} catch (PDOException $e) {
    error_log("Activity log error: " . $e->getMessage());
    return false;
}
}

/**
* Format number with suffix (1K, 1M, etc)
*/
function format_number($num) {
if ($num >= 1000000) {
    return round($num / 1000000, 1) . "M";
} elseif ($num >= 1000) {
    return round($num / 1000, 1) . "K";
}
return $num;
}

/**
* Generate CSRF token
*/
function generate_csrf_token() {
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
return $_SESSION["csrf_token"];
}

/**
* Verify CSRF token
*/
function verify_csrf_token($token) {
return isset($_SESSION["csrf_token"]) &&
    hash_equals($_SESSION["csrf_token"], $token);
}

/**
* Check if user is admin
*/
function is_admin($veza, $user_id) {
try {
    $stmt = $veza->prepare("SELECT username FROM cyber_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user && $user["username"] === "admin";
} catch (PDOException $e) {
    return false;
}
}

// Leaderboard rank function removed - leaderboard functionality has been disabled

/**
 * Format number with suffix (1K, 1M, etc)
 */
function format_number($num)
{
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . "M";
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . "K";
    }
    return $num;
}

/**
 * Generate CSRF token
 */
function generate_csrf_token()
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token)
{
    return isset($_SESSION["csrf_token"]) &&
        hash_equals($_SESSION["csrf_token"], $token);
}

/**
 * Check if user is admin
 */
function is_admin($veza, $user_id)
{
    try {
        $stmt = $veza->prepare("SELECT username FROM cyber_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user && $user["username"] === "admin";
    } catch (PDOException $e) {
        return false;
    }
}
?>
````

Sada ažuriram konfiguraciju da koristi novu bazu:
````php
<?php
// konfiguracija/konfiguracija_softver.php
define("NAZIV_APLIKACIJE", "CyberGuard Training");
define("DB_HOST", "127.0.0.1");
define("DB_NAME", "cyberguard_db");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_CHARSET", "utf8mb4");

// Application settings
define("SESSION_LIFETIME", 3600); // 1 hour
define("MAX_LOGIN_ATTEMPTS", 5);
define("LOCKOUT_TIME", 900); // 15 minutes

// XP Settings
define("XP_PER_LEVEL", 100);
define("EASY_QUESTION_XP", 10);
define("MEDIUM_QUESTION_XP", 15);
define("HARD_QUESTION_XP", 20);
define("MODULE_BASE_XP", 25);
define("SCENARIO_BASE_XP", 50);

// Security
define("ENABLE_CSRF_PROTECTION", true);
define("SESSION_COOKIE_SECURE", false); // Set to true if using HTTPS
define("SESSION_COOKIE_HTTPONLY", true);
define("SESSION_COOKIE_SAMESITE", "Lax");
?>
````
````php
<?php
// funkcije/veza_do_baze.php
$host = DB_HOST;
$db = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$charset = DB_CHARSET;

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND =>
        "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
];

try {
    $veza = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die(json_encode(["error" => "Database connection failed"]));
}
?>
````

Kreiram README fajl sa uputstvima:
````markdown
# CyberGuard Training Platform

Cyber sigurnost trening sistem sa quiz-ovima, scenarijima, modulima i leaderboardom.

## 🚀 Instalacija

### Preduvjeti
- Laragon ili XAMPP
- PHP 7.4 ili noviji
- MySQL 5.7 ili noviji
- Web browser

### Koraci instalacije

1. **Kreirajte bazu podataka**
   - Otvorite phpMyAdmin (http://localhost/phpmyadmin)
   - Kreirajte novu bazu: `cyberguard_db`
   - Importujte `database.sql` fajl

2. **Kopirajte fajlove**
   - Kopirajte sve fajlove u `C:\laragon\www\cyberguard\` (za Laragon)
   - Ili u `htdocs\cyberguard\` (za XAMPP)

3. **Konfiguracija**
   - Otvorite `konfiguracija/konfiguracija_softver.php`
   - Provjerite database credentials:
```php
     define('DB_HOST', '127.0.0.1');
     define('DB_NAME', 'cyberguard_db');
     define('DB_USER', 'root');
     define('DB_PASS', '');
```

4. **Pristupite aplikaciji**
   - Otvorite browser
   - Idite na: `http://localhost/cyberguard`
   - Ili: `http://cyberguard.test` (ako koristite Laragon virtual hosts)

## 👤 Test Credentials

- Username: `admin`
- Password: `password`

## 📁 Struktura Projekta
````
cyberguard/
│
├── api/                          # API endpoints
│   ├── get_quiz_questions.php
│   ├── update_quiz_progress.php
│   ├── get_scenario.php
│   ├── get_modules.php
│   └── ...
│
├── assets/                       # Frontend assets
│   ├── cyber_style.css          # Main stylesheet
│   ├── cyber_app.js             # Main JavaScript
│   ├── quiz_app.js              # Quiz functionality
│   ├── scenario_app.js          # Scenarios functionality
│   ├── training_app.js          # Training functionality
│   └── leaderboard_app.js       # Leaderboard functionality
│
├── funkcije/                     # Backend functions
│   ├── veza_do_baze.php        # Database connection
│   ├── init_database.php        # Database initialization
│   └── helpers.php              # Helper functions
│
├── konfiguracija/               # Configuration
│   └── konfiguracija_softver.php
│
├── index.php                     # Homepage
├── login.php                     # Login/Register
├── logout.php                    # Logout
├── quiz.php                      # Quiz mode
├── scenarios.php                 # Scenario mode
├── training.php                  # Training modules
├── leaderboard.php               # Leaderboard
├── database.sql                  # Database dump
└── README.md                     # This file
