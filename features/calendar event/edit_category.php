<?php
// Include database connection
include "../../includes/koneksi.php";

// Check if category ID and new name are provided
if (isset($_POST['id']) && isset($_POST['name'])) {
    $categoryId = $_POST['id'];
    $categoryName = $_POST['name'];

    // Update the category name in the database
    $query = "UPDATE categories SET name = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $categoryName, $categoryId);

    if ($stmt->execute()) {
        echo "Category updated successfully!";
    } else {
        echo "Failed to update category.";
    }
} else {
    echo "Invalid request.";
}
?>
