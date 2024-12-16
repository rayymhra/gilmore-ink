<?php
include "../../includes/koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $date = $_POST['date'];
    $description = $_POST['description']; // Get description from POST data
    $category = $_POST['category'];

    // Ensure the field exists in the database
    $query = $conn->prepare("UPDATE events SET title = ?, date = ?, description = ?, category = ? WHERE id = ?");
    $query->bind_param("sssss", $title, $date, $description, $category, $id);

    if ($query->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
    $query->close();
    exit;
}
?>
