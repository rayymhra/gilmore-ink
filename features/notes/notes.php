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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    
    <div class="container mt-4">
        <h1 class="text-center">Notes App</h1>

        <!-- Add/Edit Note Form -->
        <form method="POST" class="mb-4">
            <input type="hidden" name="note_id" id="note_id">
            <div class="mb-3">
                <input type="text" name="title" id="title" class="form-control" placeholder="Title">
            </div>
            <div class="mb-3">
                <textarea name="content" id="content" class="form-control" placeholder="Your note..."></textarea>
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

        <!-- Notes List -->
        <h2>Your Notes</h2>
        <div class="row">
            <?php while ($note = $notes->fetch_assoc()) { ?>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($note['title'] ?: "Untitled"); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($note['content'] ?? ""); ?></p>
                            <p class="card-text">
                                <small class="text-muted">Label: <?php echo htmlspecialchars($note['label'] ?: "None"); ?></small>
                            </p>
                            <button class="btn btn-sm btn-warning edit-note" 
                                    data-note-id="<?php echo $note['id']; ?>" 
                                    data-title="<?php echo htmlspecialchars($note['title']); ?>" 
                                    data-content="<?php echo htmlspecialchars($note['content']); ?>">
                                Edit
                            </button>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

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
                <li class="list-group-item d-flex justify-content-between">
                    <?php echo htmlspecialchars($label['name']); ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="label_id" value="<?php echo $label['id']; ?>">
                        <input type="text" name="new_label_name" value="<?php echo htmlspecialchars($label['name']); ?>" class="form-control form-control-sm" style="width: 200px; display:inline-block;">
                        <button type="submit" name="edit_label" class="btn btn-primary btn-sm">Edit</button>
                        <button type="submit" name="delete_label" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </li>
            <?php } ?>
        </ul>
    </div>

    <script>
        // Edit note
        document.querySelectorAll(".edit-note").forEach(button => {
            button.addEventListener("click", function () {
                document.getElementById("note_id").value = this.dataset.noteId;
                document.getElementById("title").value = this.dataset.title;
                document.getElementById("content").value = this.dataset.content;
            });
        });
    </script>
</body>
</html>
