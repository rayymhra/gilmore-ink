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

// Fetch categories for the dropdown
$stmt = $conn->prepare("SELECT * FROM book_categories WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$categories_result = $stmt->get_result();
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
    <div class="container mt-4">
        <h2>Manage Book Categories</h2>
        <form action="add_category.php" method="POST">
            <div class="mb-3">
                <label for="category_name" class="form-label">New Category Name</label>
                <input type="text" class="form-control" id="category_name" name="category_name" required>
            </div>
            <button type="submit" class="btn btn-success">Add Category</button>
        </form>

        <h4 class="mt-4">Your Categories</h4>
        <ul class="list-group">
            <?php foreach ($categories as $category): ?>
                <li class="list-group-item">
                    <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                    <!-- Edit Button Trigger Modal -->
                    <button type="button" class="btn btn-warning btn-sm ml-2" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?php echo $category['id']; ?>">
                        Edit
                    </button>
                    <!-- Delete Button -->
                    <a href="delete_category.php?id=<?php echo $category['id']; ?>" class="btn btn-danger btn-sm ml-2" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>

                    <!-- Edit Category Modal -->
                    <div class="modal fade" id="editCategoryModal<?php echo $category['id']; ?>" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="edit_category.php" method="POST">
                                        <div class="mb-3">
                                            <label for="category_name" class="form-label">Category Name</label>
                                            <input type="text" class="form-control" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>


    </div>

</body>

</html>