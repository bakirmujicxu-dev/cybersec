<?php
// api/save_push_subscription.php - Save push notification subscription
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

$subscription = json_encode($data['subscription']);

require_once __DIR__ . "/../funkcije/veza_do_baze.php";

try {
    // Check if subscription already exists
    $stmt = $veza->prepare("
        SELECT id FROM cyber_push_subscriptions
        WHERE user_id = ? AND endpoint = ?
    ");
    $stmt->execute([$user_id, $data['endpoint'] ?? '']);

    if (!$stmt->fetch()) {
        // Insert new subscription
        $stmt = $veza->prepare("
            INSERT INTO cyber_push_subscriptions (user_id, endpoint, p256dh, auth, expiration_time)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $data['endpoint'] ?? '',
            $data['p256dh'] ?? '',
            $data['auth'] ?? '',
            date('Y-m-d H:i:s', $data['expirationTime'] ?? 0)
        ]);
    } else {
        // Update existing subscription
        $stmt = $veza->prepare("
            UPDATE cyber_push_subscriptions
            SET p256dh = ?, auth = ?, expiration_time = ?
            WHERE user_id = ? AND endpoint = ?
        ");
        $stmt->execute([
            $data['p256dh'] ?? '',
            $data['auth'] ?? '',
            date('Y-m-d H:i:s', $data['expirationTime'] ?? 0),
            $user_id,
            $data['endpoint'] ?? ''
        ]);
    }

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
```

<file_path>
www.cyber/api/track_analytics.php
</file_path>

<edit_description>
Create track_analytics.php API endpoint
</edit_description>
```php
<?php
// api/track_analytics.php - Track analytics events
header("Content-Type: application/json; charset=utf-8");

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON"]);
    exit();
}

$eventName = $data['event'] ?? '';
$action = $data['action'] ?? '';
$userAgent = $data['user_agent'] ?? '';
$timestamp = $data['timestamp'] ?? '';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (empty($eventName) || empty($action) || empty($timestamp)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit();
}

require_once __DIR__ . "/../funkcije/veza_do_baze.php";

try {
    // Insert analytics event
    $stmt = $veza->prepare("
        INSERT INTO cyber_analytics_events (user_id, event_name, action, user_agent, timestamp, details)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        $eventName,
        $action,
        $userAgent,
        $timestamp,
        json_encode($data['details'] ?? [])
    ]);

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
```

<file_path>
www.cyber/database.sql
</file_path>

<edit_description>
Add push subscriptions and analytics tables to database.sql
</edit_description>
```sql
-- Push subscriptions table
CREATE TABLE IF NOT EXISTS cyber_push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    p256dh VARCHAR(255),
    auth VARCHAR(255),
    expiration_time DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_expiration (expiration_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics events table
CREATE TABLE IF NOT EXISTS cyber_analytics_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    event_name VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    user_agent TEXT,
    timestamp VARCHAR(50) NOT NULL,
    details JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_event (event_name),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
