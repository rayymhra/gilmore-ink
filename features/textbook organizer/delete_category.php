<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $category_id = $_GET['id'];

    // Check if the category exists and belongs to the logged-in user
    $stmt = $conn->prepare("SELECT * FROM book_categories WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $category_id, $user_id);
    $stmt->execute();
    $category = $stmt->get_result()->fetch_assoc();

    if ($category) {
        // Delete the category
        $stmt = $conn->prepare("DELETE FROM book_categories WHERE id = ?");
        if (!$stmt->execute([$category_id])) {
            echo "Error deleting category: " . $stmt->error;
            exit;
        }
        header("Location: textbooks.php"); // Redirect back to category management page
        exit;
    } else {
        echo "Category not found or you do not have permission to delete it.";
        exit;
    }
}
?>
