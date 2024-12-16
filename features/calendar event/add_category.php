<?php
// include '../../includes/koneksi.php';
// session_start();

// $user_id = $_SESSION['user_id']; // Replace with your user session variable
// $name = trim($_POST['name']);

// if (!empty($name)) {
//     $query = $conn->prepare("INSERT INTO categories (name, user_id) VALUES (?, ?)");
//     $query->bind_param("si", $name, $user_id);
//     if ($query->execute()) {
//         echo 'Success';
//     } else {
//         http_response_code(400);
//         echo 'Error';
//     }
// } else {
//     http_response_code(400);
//     echo 'Invalid category name.';
// }
?>
<?php
include "../../includes/koneksi.php";
session_start();

if (isset($_POST['name'])) {
    $categoryName = $_POST['name'];
    $userId = $_SESSION['user_id']; // Assuming you store the logged-in user's ID in session

    // Insert the category into the database
    $query = $conn->prepare("INSERT INTO categories (name, user_id) VALUES (?, ?)");
    $query->bind_param("si", $categoryName, $userId);

    if ($query->execute()) {
        echo "Category added successfully!";
    } else {
        echo "Error adding category.";
    }
} else {
    echo "No category name provided.";
}
?>

