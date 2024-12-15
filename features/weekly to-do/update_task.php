<?php
include "../../includes/koneksi.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_done_status'])) {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        header('Content-Type: application/json');
        echo json_encode(["error" => "User not authenticated"]);
        exit;
    }

    $task_id = intval($_POST['task_id']);
    $done_status = intval($_POST['done']);

    // Update task status using prepared statements
    $stmt = $conn->prepare("UPDATE weekly_tasks SET done = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $done_status, $task_id, $user_id);
    $response = $stmt->execute();

    header('Content-Type: application/json');
    echo json_encode($response ? ["success" => true] : ["error" => $stmt->error]);
    $stmt->close();
    exit;
}
?>
