<?php
// Include database connection
include "../../includes/koneksi.php";

// Check if category ID is provided
if (isset($_POST['id'])) {
    $categoryId = $_POST['id'];

    // Delete the category from the database
    $query = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $categoryId);

    if ($stmt->execute()) {
        echo "Category deleted successfully!";
    } else {
        echo "Failed to delete category.";
    }
} else {
    echo "Invalid request.";
}
?>
