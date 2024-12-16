<?php
session_start();
include "../../includes/koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $date = $_POST['date'] ?? '';
    $description = $_POST['description'] ?? ''; // Get description from POST data
    $user_id = $_SESSION['user_id'] ?? null;
    $category = $_POST['category'] ?? $_POST['customCategory'];

    // Validate inputs
    if (!empty($title) && !empty($date) && !empty($user_id)) {
        $label = $_POST['label'] ?? null;

        // Updated query to include description
        $query = $conn->prepare("INSERT INTO events (user_id, title, description, date, category) VALUES (?, ?, ?, ?, ?)");
        $query->bind_param("sssss", $user_id, $title, $description, $date, $label);

        if ($query->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Event added successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database insertion failed: ' . $query->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input data. Please check your session or inputs.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
