<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Please log in first.");
}

$user_id = $_SESSION['user_id'];

// Add or edit notes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_note'])) {
    $note_id = !empty($_POST['note_id']) ? intval($_POST['note_id']) : null;
    $title = !empty($_POST['title']) ? $_POST['title'] : null;
    $content = !empty($_POST['content']) ? $_POST['content'] : null;
    $label = isset($_POST['label']) && $_POST['label'] !== "" ? $_POST['label'] : null; // Single label or null

    // Insert note
    if ($note_id) {
        $stmt = $conn->prepare("UPDATE notes SET title = ?, content = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $title, $content, $note_id, $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $title, $content);
    }
    $stmt->execute();
    $note_id = $note_id ?: $conn->insert_id;
    $stmt->close();

    // Insert or update note-label association
    $conn->query("DELETE FROM note_labels WHERE note_id = $note_id");
    if ($label) {
        $stmt = $conn->prepare("INSERT INTO note_labels (note_id, label_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $note_id, $label);
        $stmt->execute();

        // Redirect to avoid resubmitting data on refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch notes with labels
$notes = $conn->query("
    SELECT n.id, n.title, n.content, l.name AS label
    FROM notes n
    LEFT JOIN note_labels nl ON n.id = nl.note_id
    LEFT JOIN labels l ON nl.label_id = l.id
    WHERE n.user_id = $user_id
");

// Fetch all labels
$labels = $conn->query("SELECT id, name FROM labels WHERE user_id = $user_id");

// Add a new label
// Add a new label
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_label'])) {
    $new_label = !empty($_POST['new_label']) ? $_POST['new_label'] : null;

    if ($new_label) {
        // Check if the label already exists
        $stmt = $conn->prepare("SELECT id FROM labels WHERE user_id = ? AND name = ?");
        $stmt->bind_param("is", $user_id, $new_label);
        $stmt->execute();
        $stmt->store_result();

        // If the label doesn't exist, insert it
        if ($stmt->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO labels (user_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $new_label);
            $stmt->execute();
        }
        $stmt->close();
    }
}

// Delete a label
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_label'])) {
    $label_id = isset($_POST['label_id']) ? intval($_POST['label_id']) : null;

    if ($label_id) {
        // Delete the label from the labels table
        $stmt = $conn->prepare("DELETE FROM labels WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $label_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Edit label
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_label'])) {
    $label_id = isset($_POST['label_id']) ? intval($_POST['label_id']) : null;
    $new_label_name = isset($_POST['new_label_name']) ? $_POST['new_label_name'] : null;

    if ($label_id && $new_label_name) {
        $stmt = $conn->prepare("UPDATE labels SET name = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $new_label_name, $label_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}


// Delete a note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_note'])) {
    $note_id = isset($_POST['note_id']) ? intval($_POST['note_id']) : null;

    if ($note_id) {
        // Delete the note from the notes table
        $stmt = $conn->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $note_id, $user_id);
        $stmt->execute();
        $stmt->close();

        // Optionally, delete associated labels from the note_labels table
        $conn->query("DELETE FROM note_labels WHERE note_id = $note_id");

        // Redirect to avoid resubmitting data on refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$title = !empty($_POST['title']) ? $_POST['title'] : 'Untitled';
$content = !empty($_POST['content']) ? $_POST['content'] : '';


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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











        h1,
        h2,
        h3 {
            font-weight: 600;
            color: #333;
        }

        .card {
            border: 1px solid #BFAF9C;
            border-radius: 10px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card-body {
            padding: 20px;
        }

        .card-title {
            font-size: 1.2rem;
            color: #333;
        }

        .card-text {
            font-size: 1rem;
            color: #555;
        }

        .btn-primary {
            background-color: #A1896E;
            border-color: #A1896E;
        }

        .btn-primary:hover {
            background-color: #AB967D;
            border-color: #AB967D;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #D3C8BB;
            background-color: #F4F4F9;
        }

        .form-control:focus {
            border-color: #A1896E;
            box-shadow: 0 0 0 0.2rem rgba(161, 137, 110, 0.25);
        }

        .list-group-item {
            background-color: #F4F4F9;
            border: 1px solid #D3C8BB;
        }

        .btn-success {
            background-color: #AB967D;
            border-color: #AB967D;
        }

        .btn-success:hover {
            background-color: #BFAF9C;
            border-color: #BFAF9C;
        }

        .edit-note {
            background-color: #D3C8BB;
            color: #333;
        }

        .edit-note:hover {
            background-color: #A1896E;
            color: #fff;
        }

        .btn-warning {
            border-color: #A1896E;
        }

        h3 {
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        .list-group-item {
            background-color: #F9F9F9;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 10px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .list-group-item .label-name {
            font-weight: 500;
            color: #333;
            flex: 1;
        }

        .list-group-item form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .list-group-item form .form-control-sm {
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .list-group-item form .form-control-sm:focus {
            border-color: #A1896E;
            box-shadow: 0 0 0 0.2rem rgba(161, 137, 110, 0.25);
        }

        .list-group-item form button {
            border-radius: 5px;
        }

        .list-group-item form button.btn-primary {
            background-color: #A1896E;
            border-color: #A1896E;
        }

        .list-group-item form button.btn-primary:hover {
            background-color: #AB967D;
            border-color: #AB967D;
        }

        .list-group-item form button.btn-danger {
            background-color: #E57373;
            border-color: #E57373;
        }

        .list-group-item form button.btn-danger:hover {
            background-color: #EF5350;
            border-color: #EF5350;
        }

        .btn-danger {
            background-color: #E57373;
            border-color: #E57373;
        }

        .btn-danger:hover {
            background-color: #EF5350;
            border-color: #EF5350;
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
            <a class="nav-link" href="../weekly to-do/weekly.php">
                <i class="fas fa-tasks"></i>
                <span>Weekly To-do</span>
            </a>
            <a class="nav-link active" href="../notes/notes.php">
                <i class="fas fa-sticky-note"></i>
                <span>Notes</span>
            </a>
            <a class="nav-link" href="../textbook organizer/textbooks.php">
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
            <h1 class="text-center">Notes App</h1>
            <div class="row">
                <div class="col-8">
                    <!-- Notes List -->
                    <h2>Your Notes</h2>
                    <div class="row">
                        <?php while ($note = $notes->fetch_assoc()) { ?>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($note['title'] ?? "Untitled"); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($note['content'] ?? ""); ?></p>
                                        <p class="card-text">
                                            <small class="text-muted">Label: <?php echo htmlspecialchars($note['label'] ?? "None"); ?></small>
                                        </p>
                                        <button class="btn btn-sm btn-warning edit-note"
                                            data-note-id="<?php echo $note['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($note['title']); ?>"
                                            data-content="<?php echo htmlspecialchars($note['content']); ?>">
                                            Edit
                                        </button>
                                        <!-- Delete Button -->
                                        <form action="notes.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                            <button type="submit" name="delete_note" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="col-4">
                    <!-- Add/Edit Note Form -->
                    <form method="POST" class="mb-4">
                        <h2>Add Notes</h2>
                        <input type="hidden" name="note_id" id="note_id">
                        <div class="mb-3">
                            <input type="text" name="title" id="title" class="form-control" placeholder="Title" required>
                        </div>
                        <div class="mb-3">
                            <textarea name="content" id="content" class="form-control" placeholder="Your note..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <!-- Single Label Dropdown -->
                            <label for="label" class="form-label">Select Label</label>
                            <select name="label" id="label" class="form-control">
                                <option value="">None</option> <!-- Allow null selection -->
                                <?php while ($label = $labels->fetch_assoc()) { ?>
                                    <option value="<?php echo $label['id']; ?>">
                                        <?php echo htmlspecialchars($label['name']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <!-- <div class="mb-3">
                <input type="text" name="custom_label" id="custom_label" class="form-control" placeholder="Add a custom label (Optional)">
            </div> -->
                        <button type="submit" name="save_note" class="btn btn-primary">Save Note</button>
                    </form>



                    <!-- Label Management Section -->
                    <h2>Manage Your Labels</h2>

                    <!-- Add New Label -->
                    <form method="POST" class="mb-4">
                        <div class="mb-3">
                            <input type="text" name="new_label" id="new_label" class="form-control" placeholder="New Label Name" required>
                        </div>
                        <button type="submit" name="add_label" class="btn btn-success">Add Label</button>
                    </form>

                    <!-- Edit and Delete Labels -->
                    <h3>Your Labels</h3>
                    <ul class="list-group">
                        <?php
                        // Fetch all labels for the user
                        $label_result = $conn->query("SELECT id, name FROM labels WHERE user_id = $user_id");
                        while ($label = $label_result->fetch_assoc()) { ?>
                            <li class="list-group-item d-flex flex-column mb-3 p-3" style="background-color: #F9F9F9; border-radius: 8px;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="label-name fs-5 fw-semibold text-dark"><?php echo htmlspecialchars($label['name']); ?></span>
                                </div>

                                <!-- Form for editing or deleting the label -->
                                <form method="POST" class="d-flex flex-column gap-2">
                                    <input type="hidden" name="label_id" value="<?php echo $label['id']; ?>">

                                    <!-- Input for the new label name -->
                                    <div class="d-flex">
                                        <input type="text" name="new_label_name" value="<?php echo htmlspecialchars($label['name']); ?>" class="form-control form-control-sm" style="width: 180px;">
                                    </div>

                                    <!-- Buttons for edit and delete -->
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="edit_label" class="btn btn-primary btn-sm w-50">Edit</button>
                                        <button type="submit" name="delete_label" class="btn btn-danger btn-sm w-50">Delete</button>
                                    </div>
                                </form>

                            </li>
                        <?php } ?>
                    </ul>

                </div>
            </div>





        </div>
    </div>


    <script>
        // Edit note
        document.querySelectorAll(".edit-note").forEach(button => {
            button.addEventListener("click", function() {
                document.getElementById("note_id").value = this.dataset.noteId;
                document.getElementById("title").value = this.dataset.title;
                document.getElementById("content").value = this.dataset.content;
            });
        });
    </script>
</body>

</html>