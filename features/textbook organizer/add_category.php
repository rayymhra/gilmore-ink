<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['category_name'])) {
    $category_name = $_POST['category_name'];
    
    // Insert the new category into the database
    $stmt = $conn->prepare("INSERT INTO book_categories (name, user_id) VALUES (?, ?)");
    $stmt->bind_param("si", $category_name, $user_id);
    $stmt->execute();
}

header('Location: textbooks.php');
exit;
