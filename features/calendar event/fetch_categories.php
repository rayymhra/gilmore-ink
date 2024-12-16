<?php
// include "../../includes/koneksi.php"; // Update path as needed
// session_start();

// $user_id = $_SESSION['user_id'];

// // Fetch categories from the database
// $query = $conn->prepare("SELECT DISTINCT name FROM categories WHERE user_id = ?");
// $query->bind_param("s", $user_id);
// $query->execute();
// $result = $query->get_result();

// $categories = [];
// while ($row = $result->fetch_assoc()) {
//     $categories[] = $row;
// }

// // Return JSON response
// header('Content-Type: application/json');
// echo json_encode($categories);

?>

<?php
include "../../includes/koneksi.php";

$query = "SELECT * FROM categories";
$result = $conn->query($query);

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

echo json_encode($categories);
?>

