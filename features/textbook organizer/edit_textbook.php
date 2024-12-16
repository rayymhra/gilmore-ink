<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $book_id = $_GET['id'];

    // Fetch book details
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
        <form action="edit_textbooks.php?book_id=<?php echo $book['id']; ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description"><?php echo htmlspecialchars($book['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="label" class="form-label">Label/Subject</label>
                <input type="text" class="form-control" id="label" name="label" value="<?php echo htmlspecialchars($book['label'], ENT_QUOTES, 'UTF-8'); ?>">
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