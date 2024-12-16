<?php
// include "../../includes/koneksi.php";


// if (isset($_POST['id']) && isset($_POST['name'])) {
//     $id = $_POST['id'];
//     $name = $_POST['name'];

//     // Validate the category name
//     if (!empty($name)) {
//         $query = "UPDATE categories SET name = ? WHERE id = ?";
//         $stmt = $conn->prepare($query);
//         $stmt->bind_param('si', $name, $id); // 'si' means string and integer
//         if ($stmt->execute()) {
//             echo "Category updated successfully!";
//         } else {
//             echo "Failed to update category!";
//         }
//     } else {
//         echo "Category name cannot be empty!";
//     }
// } else {
//     echo "Invalid request!";
// }
?>

<?php
include "../../includes/koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = $_POST['id'];
    $categoryName = $_POST['categoryName'];

    $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $categoryName, $categoryId);
    $stmt->execute();

    echo json_encode(['status' => 'success']);
}
?>

