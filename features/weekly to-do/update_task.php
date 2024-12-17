<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Unauthorized access.";
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle updating "done" status
if (isset($_POST['update_done_status']) && isset($_POST['task_id'], $_POST['done'])) {
    $task_id = (int)$_POST['task_id'];
    $done = (int)$_POST['done'];

    // Update the task's "done" status
    $stmt = $conn->prepare("UPDATE weekly_tasks SET done = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $done, $task_id, $user_id);

    if ($stmt->execute()) {
        echo "Task status updated successfully.";
    } else {
        echo "Failed to update task status.";
    }
    $stmt->close();
    exit;
}

// Handle inline field edits (title, description, priority)
if (isset($_POST['task_id'], $_POST['field'], $_POST['value'])) {
    $task_id = (int)$_POST['task_id'];
    $field = $_POST['field'];
    $value = $_POST['value'];

    // Whitelist fields for safety
    $allowed_fields = ['title', 'description', 'priority'];
    if (!in_array($field, $allowed_fields)) {
        http_response_code(400);
        echo "Invalid field.";
        exit;
    }

    // Update the specified field
    $query = "UPDATE weekly_tasks SET $field = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $value, $task_id, $user_id);

    if ($stmt->execute()) {
        echo "Task updated successfully.";
    } else {
        echo "Failed to update task.";
    }
    $stmt->close();
    exit;
}

// If no valid operation is provided
http_response_code(400);
echo "Invalid request.";
?>
