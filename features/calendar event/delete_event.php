<?php
include "../../includes/koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    // Ensure the event exists before trying to delete
    if (!empty($id)) {
        // Prepare and execute delete query
        $query = $conn->prepare("DELETE FROM events WHERE id = ?");
        $query->bind_param("i", $id);

        if ($query->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to delete the event."]);
        }
        $query->close();
    } else {
        echo json_encode(["success" => false, "message" => "Invalid event ID."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
