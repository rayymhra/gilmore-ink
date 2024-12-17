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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #f4f4f9;
            /* Lighter neutral background */
            position: fixed;
            top: 0;
            left: 0;
            transition: all 0.3s;
            overflow-y: auto;
            border-right: 1px solid #e0e0e0;
            /* Light border for subtle separation */
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .toggle-btn {
            font-size: 1.5rem;
            text-align: center;
            cursor: pointer;
            padding: 1rem;
            background-color: #dcdcdc;
            /* Subtle gray background */
            color: #333;
            /* Darker text for contrast */
            border-bottom: 1px solid #e0e0e0;
        }

        .content {
            flex: 1;
            margin-left: 255px;
            padding: 0;
            transition: margin-left 0.3s;
        }

        .content.collapsed {
            margin-left: 80px;
        }

        .nav {
            padding: 1rem 0;
            list-style: none;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            font-weight: 500;
            color: #555;
            /* Neutral dark gray */
            text-decoration: none;
            transition: background-color 0.3s, color 0.3s;
        }

        .nav-link i {
            margin-right: 1rem;
        }

        .nav-link:hover {
            background-color: #e0e0e0;
            /* Light hover effect */
            color: #333;
            /* Dark text on hover */
        }

        .nav-link.active {
            background-color: #dcdcdc;
            /* Active link with subtle gray */
            color: #333;
            /* Dark text for active link */
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
        }

        .sidebar.collapsed .nav-link i {
            margin: 0;
        }

        .sidebar.collapsed .nav-link span {
            display: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: absolute;
                z-index: 1000;
                width: 70%;
                left: -250px;
            }

            .sidebar.active {
                left: 0;
            }

            .content {
                margin-left: 0;
            }

            .content.collapsed {
                margin-left: 0;
            }
        }

        .card {
            border: none;
            border-radius: 10px;
            background-color: #FFFFFF;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: bold;
        }

        .card-text {
            color: #6E6E6E;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .btn {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .btn-primary {
            background-color: #A1896E;
            border: none;
        }

        .btn-primary:hover {
            background-color: #8F7D5A;
        }

        .btn-secondary {
            background-color: #BFAF9C;
            border: none;
        }

        .btn-secondary:hover {
            background-color: #9E8F72;
        }

        .btn-warning {
            background-color: #F5A623;
            border: none;
        }

        .btn-danger {
            margin-top: 5px;
            background-color: #E74C3C;
            border: none;
        }

        .container h2,
        .container h4 {
            color: #4A4A4A;
        }

        .form-control {
            border-radius: 5px;
            border: 1px solid #BFAF9C;
        }

        .form-label {
            color: #4A4A4A;
        }

        .list-group-item {
            background-color: #F5F5F5;
            border: 1px solid #E7E1DA;
        }

        .list-group-item:hover {
            background-color: #D3C8BB;
        }

        /* Add the following CSS styles */
        .btn-add-new {
            display: inline-block;
            padding: 0.8rem 1.2rem;
            font-size: 1rem;
            font-weight: 500;
            color: #A1896E;
            /* Use your color palette for text */
            background-color: #E7E1DA;
            /* Subtle off-white background */
            border: 1px solid #D3C8BB;
            /* Light border */
            border-radius: 8px;
            /* Rounded corners */
            text-decoration: none;
            /* Remove underline */
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .btn-add-new:hover {
            color: #fff;
            /* White text on hover */
            background-color: #A1896E;
            /* Darker background on hover */
            border-color: #A1896E;
            /* Border color matches the background */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            /* Add subtle shadow */
        }

        .btn-add-new:active {
            transform: scale(0.98);
            /* Slightly shrink on click for effect */
        }

        .btn-add-new:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(161, 152, 110, 0.4);
            /* Focus outline with a soft glow */
        }

        .btn-success {
            background-color: #A1896E;
            border-color: #A1896E;
            /* margin-bottom: 10px; */
        }

        .btn-success:hover {
            background-color: #8F7D5A;
            border-color: #8F7D5A;
        }
    </style>

</head>

<body>
    <div class="sidebar" id="sidebar">
        <div class="toggle-btn" onclick="">Gilmore Ink</div>
        <nav class="nav flex-column">
            <a class="nav-link" href="../../dashboard.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link" href="../calendar event/calendar.php">
                <i class="fas fa-calendar"></i>
                <span>Calendar</span>
            </a>
            <a class="nav-link " href="../weekly to-do/weekly.php">
                <i class="fas fa-tasks"></i>
                <span>Weekly To-do</span>
            </a>
            <a class="nav-link" href="../notes/notes.php">
                <i class="fas fa-sticky-note"></i>
                <span>Notes</span>
            </a>
            <a class="nav-link active" href="../textbook organizer/textbooks.php">
                <i class="fas fa-book-open"></i>
                <span>Textbooks Manager</span>
            </a>
            <a class="nav-link" href="../tracker/assignment.php">
                <i class="fas fa-clipboard-check"></i>
                <span>Assignments Tracker</span>
            </a>
            <a class="nav-link" href="../tracker/money.php">
                <i class="fas fa-wallet"></i>
                <span>Budget Tracker</span>
            </a>
            <a class="nav-link" href="../tracker/pomodoro.php">
                <i class="fas fa-hourglass-start"></i>
                <span>Pomodoro Timer</span>
            </a>
        </nav>
    </div>

    <div class="content">
        <div class="container mt-4">
            <h2>My Books</h2>
            <div class="row">
                <div class="col-8">
                    <a href="add_textbooks.php" class="btn-add-new">add new</a>

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
                <div class="col-4">
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
                </div>
            </div>

        </div>

    </div>


</body>

</html>