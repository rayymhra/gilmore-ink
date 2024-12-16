<?php
include "../../includes/koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $date = $_POST['date'];

    // Update the event in the database
    $stmt = $conn->prepare("UPDATE events SET title = ?, date = ? WHERE id = ?");
    $stmt->bind_param("ssi", $title, $date, $id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
    $stmt->close();
    exit;
}
?>
