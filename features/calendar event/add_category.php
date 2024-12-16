<?php
include '../../includes/koneksi.php';
session_start();

$user_id = $_SESSION['user_id']; // Replace with your user session variable
$name = trim($_POST['name']);

if (!empty($name)) {
    $query = $conn->prepare("INSERT INTO categories (name, user_id) VALUES (?, ?)");
    $query->bind_param("si", $name, $user_id);
    if ($query->execute()) {
        echo 'Success';
    } else {
        http_response_code(400);
        echo 'Error';
    }
} else {
    http_response_code(400);
    echo 'Invalid category name.';
}
?>
