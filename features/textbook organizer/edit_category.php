<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['category_id']) && isset($_POST['category_name'])) {
        $category_id = $_POST['category_id'];
        $new_name = trim($_POST['category_name']);

        if (empty($new_name)) {
            echo "Category name is required.";
            exit;
        }

        // Update the category name in the database
        $stmt = $conn->prepare("UPDATE book_categories SET name = ? WHERE id = ? AND user_id = ?");
        if (!$stmt->execute([$new_name, $category_id, $user_id])) {
            echo "Database error: " . $stmt->error;
            exit;
        }

        header("Location: textbooks.php"); // Redirect to the category management page
        exit;
    }
}
?>
