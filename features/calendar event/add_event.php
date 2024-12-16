<?php
// // Include database connection
// include "../../includes/koneksi.php";
// session_start();

// // Check if the user is logged in
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401); // Unauthorized
//     echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
//     exit;
// }

// $user_id = $_SESSION['user_id'];

// // Retrieve POST data
// $title = isset($_POST['title']) ? trim($_POST['title']) : null;
// $description = isset($_POST['description']) ? trim($_POST['description']) : null;
// $date = isset($_POST['date']) ? trim($_POST['date']) : null;
// $category = isset($_POST['category']) ? trim($_POST['category']) : null;

// // Validate input
// if (empty($title) || empty($date)) {
//     http_response_code(400); // Bad Request
//     echo json_encode(['status' => 'error', 'message' => 'Title and Date are required.']);
//     exit;
// }

// try {
//     // Insert the event into the database
//     $stmt = $conn->prepare("INSERT INTO events (user_id, title, description, date, category) VALUES (?, ?, ?, ?, ?)");
//     $stmt->execute([$user_id, $title, $description, $date, $category]);

//     // Success response
//     echo json_encode(['status' => 'success', 'message' => 'Event added successfully.']);
// } catch (PDOException $e) {
//     http_response_code(500); // Internal Server Error
//     echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
// }


?>

<?php
session_start();
include "../../includes/koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $date = $_POST['date'] ?? '';
    $user_id = $_SESSION['user_id'] ?? null;
    $category = $_POST['category'] ?? $_POST['customCategory'];


    // Validate inputs
    if (!empty($title) && !empty($date) && !empty($user_id)) {
        $label = $_POST['label'] ?? null;

        $query = $conn->prepare("INSERT INTO events (user_id, title, date, label) VALUES (?, ?, ?, ?)");
        $query->bind_param("ssss", $user_id, $title, $date, $label);


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
