<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch book details for editing
$book = null;
if (isset($_GET['id'])) {
    $book_id = $_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $book_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();

    if (!$book) {
        echo "Book not found.";
        exit;
    }
} else {
    echo "No book ID provided.";
    exit;
}

// Fetch categories for the dropdown
$categories = [];
$stmt = $conn->prepare("SELECT * FROM book_categories WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($category = $result->fetch_assoc()) {
    $categories[] = $category;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $label = isset($_POST['label']) ? $_POST['label'] : $book['label']; // Use current label if none selected
    $link = $_POST['link'];
    $pdf_path = $book['pdf_path']; // Default to existing value
    $cover_path = $book['cover_path']; // Default to existing value

    // Process PDF upload
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $pdf_path = "uploads/" . basename($_FILES['pdf']['name']);
        move_uploaded_file($_FILES['pdf']['tmp_name'], $pdf_path);
    }

    // Process Cover upload
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $cover_path = "uploads/" . basename($_FILES['cover']['name']);
        move_uploaded_file($_FILES['cover']['tmp_name'], $cover_path);
    }

    // Update the book record
    $stmt = $conn->prepare("UPDATE books SET title = ?, description = ?, label = ?, link = ?, pdf_path = ?, cover_path = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssssssii", $title, $description, $label, $link, $pdf_path, $cover_path, $book_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        header("Location: textbooks.php");
        exit;
    } else {
        echo "Error updating book or no changes were made.";
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2>Edit Book</h2>
        <form action="edit_textbook.php?id=<?php echo $book['id']; ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description"><?php echo htmlspecialchars($book['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="label" class="form-label">Category</label>
                <select class="form-select" id="label" name="label" required>
                    <option value="" <?php echo (empty($book['label']) ? 'selected' : ''); ?>>Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo ($book['label'] === $category['name']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            </div>
            <div class="mb-3">
                <label for="link" class="form-label">Book Link</label>
                <input type="url" class="form-control" id="link" name="link" value="<?php echo htmlspecialchars($book['link'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="mb-3">
                <label for="pdf" class="form-label">Upload New PDF (Leave blank to keep current)</label>
                <input type="file" class="form-control" id="pdf" name="pdf" accept="application/pdf">
            </div>
            <div class="mb-3">
                <label for="cover" class="form-label">Upload New Cover (Leave blank to keep current)</label>
                <input type="file" class="form-control" id="cover" name="cover" accept="image/*">
            </div>

            <div class="mb-3">
                <?php if ($book['pdf_path']): ?>
                    <p>Current PDF:
                        <a href="<?php echo $book['pdf_path']; ?>" target="_blank">View PDF</a>
                    </p>
                <?php endif; ?>
                <?php if ($book['cover_path']): ?>
                    <p>Current Cover: <img src="<?php echo $book['cover_path']; ?>" alt="Book Cover" width="100"></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Update Book</button>
        </form>
    </div>
</body>

</html>