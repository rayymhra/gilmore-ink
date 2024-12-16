<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM books WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$books = $result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2>My Books</h2>
        <a href="add_textbooks.php">add new</a>
        <div class="row">
            <?php foreach ($books as $book): ?>
                <div class="col-md-4">
                    <div class="card mb-3">
                        <?php if ($book['cover_path']): ?>
                            <img src="<?php echo $book['cover_path']; ?>" class="card-img-top" alt="Book Cover">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($book['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Label:</strong> <?php echo htmlspecialchars($book['label'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php if ($book['link']): ?>
                                <a href="<?php echo htmlspecialchars($book['link'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary" target="_blank">Open Link</a>
                            <?php endif; ?>
                            <?php if ($book['pdf_path']): ?>
                                <a href="<?php echo htmlspecialchars($book['pdf_path'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary" target="_blank">View PDF</a>
                            <?php endif; ?>
                            <!-- Edit Button -->
                            <a href="edit_textbook.php?id=<?php echo $book['id']; ?>" class="btn btn-warning">Edit</a>
                            <!-- Delete Button -->
                            <a href="delete_textbook.php?id=<?php echo $book['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this book?')">Delete</a>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>