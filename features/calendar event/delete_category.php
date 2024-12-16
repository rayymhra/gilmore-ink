<?php
// include "../../includes/koneksi.php";

// if (isset($_POST['id'])) {
//     $id = $_POST['id'];

//     // Ensure the category exists before deleting
//     $query = "DELETE FROM categories WHERE id = ?";
//     $stmt = $conn->prepare($query);
//     $stmt->bind_param('i', $id); // 'i' means integer
//     if ($stmt->execute()) {
//         echo "Category deleted successfully!";
//     } else {
//         echo "Failed to delete category!";
//     }
// } else {
//     echo "Invalid request!";
// }
?>

<?php
include "../../includes/koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();

    echo json_encode(['status' => 'success']);
}
?>
