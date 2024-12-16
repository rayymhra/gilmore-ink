<?php
include "../../includes/koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $date = $_POST['date'];
    $label = $_POST['label'];
    $category = $_POST['category'] ?? $_POST['customCategory'];


    // Update the event in the database
    $stmt = $conn->prepare("UPDATE events SET title = ?, date = ?, label = ? WHERE id = ?");
    $stmt->bind_param("sssi", $title, $date, $label, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
    $stmt->close();
    exit;
}
