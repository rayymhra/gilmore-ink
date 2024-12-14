<?php
include "includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// Initialize paths
$cover_path = null;
$icon_path = null;

// Handle cover image upload
if (isset($_FILES['cover'])) {
    $cover_name = time() . '_cover_' . basename($_FILES['cover']['name']);
    $cover_path = 'uploads/' . $cover_name;
    move_uploaded_file($_FILES['cover']['tmp_name'], $cover_path);
}

// Handle icon image upload
if (isset($_FILES['icon'])) {
    $icon_name = time() . '_icon_' . basename($_FILES['icon']['name']);
    $icon_path = 'uploads/' . $icon_name;
    move_uploaded_file($_FILES['icon']['tmp_name'], $icon_path);
}

// Update title
if (isset($data['title'])) {
    $title = mysqli_real_escape_string($conn, $data['title']);
    mysqli_query($conn, "UPDATE dashboard SET title = '$title' WHERE user_id = '$user_id'");
}

// Update paths
if ($cover_path || $icon_path) {
    $query = "UPDATE dashboard SET ";
    if ($cover_path) $query .= "cover_path = '$cover_path', ";
    if ($icon_path) $query .= "icon_path = '$icon_path', ";
    $query = rtrim($query, ', ') . " WHERE user_id = '$user_id'";
    mysqli_query($conn, $query);
}

echo json_encode(['success' => true, 'cover_path' => $cover_path, 'icon_path' => $icon_path]);
?>
