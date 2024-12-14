<?php
include "../../includes/koneksi.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo "User not logged in.";
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $title = isset($_POST['title']) ? trim($_POST['title']) : null;
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    $link = isset($_POST['link']) ? trim($_POST['link']) : null;
    $label = isset($_POST['label']) ? trim($_POST['label']) : null;

    if (empty($title)) {
        http_response_code(400);
        echo "Title is required.";
        exit;
    }

    $upload_dir = '../../uploads/';
    $pdf_path = null;
    $cover_path = null;

    if (!empty($_FILES['pdf']['name'])) {
        $pdf_name = time() . '_' . basename($_FILES['pdf']['name']);
        $pdf_path = $upload_dir . $pdf_name;
        if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $pdf_path)) {
            http_response_code(500);
            echo "Failed to upload PDF.";
            exit;
        }
    }

    if (!empty($_FILES['cover']['name'])) {
        $cover_name = time() . '_' . basename($_FILES['cover']['name']);
        $cover_path = $upload_dir . $cover_name;
        if (!move_uploaded_file($_FILES['cover']['tmp_name'], $cover_path)) {
            http_response_code(500);
            echo "Failed to upload cover.";
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO books (user_id, title, description, link, pdf_path, label, cover_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt->execute([$user_id, $title, $description, $link, $pdf_path, $label, $cover_path])) {
        http_response_code(500);
        echo "Database error: " . $stmt->error;
        exit;
    }

    echo "Book added successfully!";
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TextBooks Organizer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Add a Book</h2>
    <form id="bookForm" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description"></textarea>
        </div>
        <div class="mb-3">
            <label for="label" class="form-label">Label/Subject</label>
            <input type="text" class="form-control" id="label" name="label">
        </div>
        <div class="mb-3">
            <label for="link" class="form-label">Book Link</label>
            <input type="url" class="form-control" id="link" name="link">
        </div>
        <div class="mb-3">
            <label for="pdf" class="form-label">Upload PDF</label>
            <input type="file" class="form-control" id="pdf" name="pdf" accept="application/pdf">
        </div>
        <div class="mb-3">
            <label for="cover" class="form-label">Book Cover</label>
            <input type="file" class="form-control" id="cover" name="cover" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary">Add Book</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script>
    $('#bookForm').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        $.ajax({
            url: 'add_textbooks.php', // Same file handling the request
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                alert('Book added successfully!');
                location.reload();
            },
            error: function(xhr) {
                alert('Failed to add book: ' + xhr.responseText);
            }
        });
    });
</script>
</body>
</html>
