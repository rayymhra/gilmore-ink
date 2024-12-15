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
    $labels = !empty($_POST['labels']) ? $_POST['labels'] : [];
    $custom_label = !empty($_POST['custom_label']) ? $_POST['custom_label'] : null;

    // Insert custom label if provided and doesn't exist yet
    if ($custom_label) {
        $stmt = $conn->prepare("SELECT id FROM labels WHERE user_id = ? AND name = ?");
        $stmt->bind_param("is", $user_id, $custom_label);
        $stmt->execute();
        $stmt->store_result();

        // If the custom label doesn't exist, insert it
        if ($stmt->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO labels (user_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $custom_label);
            $stmt->execute();
            $custom_label_id = $stmt->insert_id;
        } else {
            $stmt->bind_result($custom_label_id);
            $stmt->fetch();
        }
        $stmt->close();

        // Add custom label to labels array
        if ($custom_label_id) {
            $labels[] = $custom_label_id;
        }
    }

    // Insert or update the note
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

    // Update note-label associations
    $conn->query("DELETE FROM note_labels WHERE note_id = $note_id");
    foreach ($labels as $label_id) {
        $stmt = $conn->prepare("INSERT INTO note_labels (note_id, label_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $note_id, $label_id);
        $stmt->execute();
    }
}

// Fetch notes with labels
$notes = $conn->query("
    SELECT n.id, n.title, n.content, GROUP_CONCAT(l.name) AS labels
    FROM notes n
    LEFT JOIN note_labels nl ON n.id = nl.note_id
    LEFT JOIN labels l ON nl.label_id = l.id
    WHERE n.user_id = $user_id
    GROUP BY n.id
");

// Fetch all labels
$labels = $conn->query("SELECT id, name FROM labels WHERE user_id = $user_id");
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
                <select name="labels[]" id="labels" class="form-control" multiple>
                    <?php while ($label = $labels->fetch_assoc()) { ?>
                        <option value="<?php echo $label['id']; ?>">
                            <?php echo htmlspecialchars($label['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="mb-3">
                <input type="text" name="custom_label" id="custom_label" class="form-control" placeholder="Add a custom label (Optional)">
            </div>
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
                                <small class="text-muted">Labels: <?php echo htmlspecialchars($note['labels'] ?: "None"); ?></small>
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

