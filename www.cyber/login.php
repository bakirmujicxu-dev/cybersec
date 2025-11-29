<?php
// login.php
session_start();
require_once __DIR__ . '/konfiguracija/konfiguracija_softver.php';
require_once __DIR__ . '/funkcije/veza_do_baze.php';
require_once __DIR__ . '/funkcije/init_database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username)) {
        $error = 'Username is required';
    } else {
        try {
            $stmt = $veza->prepare("SELECT id, username, password FROM cyber_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (!empty($user['password'])) {
                    if (empty($password) || !password_verify($password, $user['password'])) {
                        $error = 'Invalid username or password';
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        header('Location: index.php');
                        exit;
                    }
                } else {
                    if (!empty($password)) {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $up = $veza->prepare("UPDATE cyber_users SET password = ? WHERE id = ?");
                        $up->execute([$hashed, $user['id']]);
                    }
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    header('Location: index.php');
                    exit;
                }
            } else {
                $stmt = $veza->prepare("INSERT INTO cyber_users (username, password) VALUES (?, ?)");
                $hashed = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
                $stmt->execute([$username, $hashed]);

                $new_id = $veza->lastInsertId();
                $_SESSION['user_id'] = $new_id;
                $_SESSION['username'] = $username;

                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo NAZIV_APLIKACIJE; ?></title>
    <link rel="stylesheet" href="assets/cyber_style.css">
</head>
<body>
    <div class="matrix-bg"></div>
    <div class="cyber-grid"></div>

    <div class="login-container">
        <div class="terminal-window">
            <div class="terminal-header">
                <span class="terminal-dot red"></span>
                <span class="terminal-dot yellow"></span>
                <span class="terminal-dot green"></span>
                <span class="terminal-title">CYBERGUARD TRAINING SYSTEM v2.0</span>
            </div>

            <div class="terminal-body">
                <div class="glitch-text" data-text="CYBERGUARD">CYBERGUARD</div>
                <p class="terminal-subtitle">// SECURE ACCESS REQUIRED</p>

                <?php if ($error): ?>
                    <div class="error-msg">
                        <span class="error-icon">⚠</span>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <div class="input-group">
                        <label class="input-label">
                            <span class="label-icon">👤</span> USERNAME
                        </label>
                        <input
                            type="text"
                            name="username"
                            class="cyber-input"
                            placeholder="Enter username..."
                            required
                            autofocus
                        >
                    </div>

                    <div class="input-group">
                        <label class="input-label">
                            <span class="label-icon">🔒</span> PASSWORD
                        </label>
                        <input
                            type="password"
                            name="password"
                            class="cyber-input"
                            placeholder="Enter password (optional)..."
                        >
                    </div>

                    <button type="submit" class="cyber-btn primary">
                        <span class="btn-text">ACCESS SYSTEM</span>
                        <span class="btn-glitch">ACCESS SYSTEM</span>
                    </button>
                </form>

                <p class="login-note">
                    <span class="blink">▶</span> New users will be registered automatically
                </p>
            </div>
        </div>
    </div>
</body>
</html>
