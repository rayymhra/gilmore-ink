<?php
include "../../includes/koneksi.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo "User not logged in.";
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $book_id = $_POST['id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $link = $_POST['link'];
    $label = $_POST['label'];

    $pdf_path = null;
    $cover_path = null;

    // Handle file uploads (same logic as in add_textbooks.php)

    // Update book in the database
    $stmt = $conn->prepare("UPDATE books SET title = ?, description = ?, link = ?, label = ?, pdf_path = ?, cover_path = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssssssii", $title, $description, $link, $label, $pdf_path, $cover_path, $book_id, $user_id);

    if ($stmt->execute()) {
        echo "Book updated successfully!";
    } else {
        echo "Failed to update book.";
    }
}
?>
