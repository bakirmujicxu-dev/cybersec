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
