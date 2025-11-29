<?php
// api/get_scenario.php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../funkcije/veza_do_baze.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$scenario_id = (int) ($_GET["id"] ?? 0);

if (!$scenario_id) {
    echo json_encode(["error" => "Missing scenario ID"]);
    exit();
}

try {
    // Get scenario details
    $stmt = $veza->prepare("
        SELECT s.*, c.name as category_name, c.icon as category_icon
        FROM cyber_scenarios s
        JOIN cyber_categories c ON s.category_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$scenario_id]);
    $scenario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$scenario) {
        echo json_encode(["error" => "Scenario not found"]);
        exit();
    }

    // Get scenario steps
    $stmt = $veza->prepare("
        SELECT * FROM cyber_scenario_steps
        WHERE scenario_id = ?
        ORDER BY step_number
    ");
    $stmt->execute([$scenario_id]);
    $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get choices for each step
    foreach ($steps as &$step) {
        $stmt = $veza->prepare("
            SELECT * FROM cyber_scenario_choices
            WHERE step_id = ?
            ORDER BY id
        ");
        $stmt->execute([$step["id"]]);
        $step["choices"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        "scenario" => $scenario,
        "steps" => $steps,
    ]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
