<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "User not logged in.";
    exit;
}

if (isset($_GET['id'])) {
    $book_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Delete book from database
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $book_id, $user_id);

    if ($stmt->execute()) {
        header('Location: textbooks.php');
        exit;
    } else {
        echo "Failed to delete book.";
    }
} else {
    echo "No book ID provided.";
}
?>
