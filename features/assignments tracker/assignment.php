<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Please log in first.");
}

$user_id = $_SESSION['user_id'];

// Handle form submission for adding a new assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    $subject_id = !empty($_POST['subject_id']) ? $_POST['subject_id'] : null;
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $grade = !empty($_POST['grade']) ? $_POST['grade'] : null;
    $title = !empty($_POST['title']) ? $_POST['title'] : null;

    $stmt = $conn->prepare("
        INSERT INTO assignments (user_id, subject_id, due_date, grade, title) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisss", $user_id, $subject_id, $due_date, $grade, $title);
    $stmt->execute();
    $stmt->close();
}

// Handle form submission for updating the "done" status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_done_status'])) {
    $assignment_id = intval($_POST['assignment_id']);
    $done_status = intval($_POST['done']);

    $stmt = $conn->prepare("UPDATE assignments SET done = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $done_status, $assignment_id, $user_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => true]);
    exit;
}

// Fetch assignments ordered by closest deadline
$stmt = $conn->prepare("
    SELECT a.*, s.name AS subject_name 
    FROM assignments a 
    LEFT JOIN subjects s ON a.subject_id = s.id 
    WHERE a.user_id = ?
    ORDER BY 
        CASE WHEN a.due_date IS NULL THEN 1 ELSE 0 END, 
        a.due_date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$assignments = $stmt->get_result();
$stmt->close();


//edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_field'])) {
    $assignment_id = intval($_POST['assignment_id']);
    $field = $_POST['field'];
    $value = $_POST['value'];

    // Validate the field to ensure only specific columns can be updated
    $allowed_fields = ['title', 'due_date', 'subject_id', 'grade'];
    if (!in_array($field, $allowed_fields)) {
        echo json_encode(["success" => false, "error" => "Invalid field."]);
        exit;
    }

    // Prepare and execute the update query
    $stmt = $conn->prepare("UPDATE assignments SET $field = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $value, $assignment_id, $user_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => true]);
    exit;
}


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h1 class="text-center">Assignments Tracker</h1>

        <!-- Form for adding assignments -->
        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" name="title" class="form-control" placeholder="Assignment Title (Optional)">
                </div>
                <div class="col-md-6">
                    <input type="date" name="due_date" class="form-control" placeholder="Due Date (Optional)">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <select name="subject_id" class="form-control">
                        <option value="">Select Subject (Optional)</option>
                        <?php
                        $subjects = $conn->query("SELECT * FROM subjects WHERE user_id = '$user_id'");
                        while ($subject = $subjects->fetch_assoc()) {
                            echo "<option value='" . $subject['id'] . "'>" . htmlspecialchars($subject['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="text" name="grade" class="form-control" placeholder="Grade (Optional)">
                </div>
            </div>
            <button type="submit" name="add_assignment" class="btn btn-primary mt-3">Add Assignment</button>
        </form>

        <!-- Assignments Table -->
        <h2>Your Assignments</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Due Date</th>
                    <th>Subject</th>
                    <th>Grade</th>
                    <th>Done</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $assignments->fetch_assoc()) { ?>
                    <tr>
                        <!-- Editable Title -->
                        <td contenteditable="true"
                            class="editable-cell"
                            data-assignment-id="<?php echo $row['id']; ?>"
                            data-field="title">
                            <?php echo htmlspecialchars($row['title'] ?: "N/A"); ?>
                        </td>

                        <!-- Editable Due Date -->
                        <td>
                            <input type="date"
                                class="editable-input"
                                value="<?php echo htmlspecialchars($row['due_date'] ?: ""); ?>"
                                data-assignment-id="<?php echo $row['id']; ?>"
                                data-field="due_date">
                        </td>

                        <!-- Editable Subject -->
                        <td>
                            <select class="editable-select"
                                data-assignment-id="<?php echo $row['id']; ?>"
                                data-field="subject_id">
                                <option value="">Select Subject</option>
                                <?php
                                $subjects->data_seek(0); // Reset the result pointer
                                while ($subject = $subjects->fetch_assoc()) {
                                    $selected = $subject['id'] == $row['subject_id'] ? "selected" : "";
                                    echo "<option value='" . $subject['id'] . "' $selected>" . htmlspecialchars($subject['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </td>

                        <!-- Editable Grade -->
                        <td contenteditable="true"
                            class="editable-cell"
                            data-assignment-id="<?php echo $row['id']; ?>"
                            data-field="grade">
                            <?php echo htmlspecialchars($row['grade'] ?: "N/A"); ?>
                        </td>

                        <!-- Done Checkbox -->
                        <td>
                            <input type="checkbox" class="form-check-input assignment-done-checkbox"
                                data-assignment-id="<?php echo $row['id']; ?>"
                                <?php echo $row['done'] ? 'checked' : ''; ?>>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>


        </table>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".assignment-done-checkbox").forEach(function(checkbox) {
                checkbox.addEventListener("change", function() {
                    const assignmentId = this.dataset.assignmentId;
                    const doneStatus = this.checked ? 1 : 0;

                    fetch("", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded"
                            },
                            body: new URLSearchParams({
                                update_done_status: true,
                                assignment_id: assignmentId,
                                done: doneStatus
                            }),
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log("Assignment status updated successfully.");
                            } else {
                                console.error("Failed to update assignment:", data.error || "Unknown error.");
                            }
                        })
                        .catch(error => console.error("AJAX error:", error));
                });
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
    // Handle text-based edits (Title, Grade)
    document.querySelectorAll(".editable-cell").forEach(function (cell) {
        cell.addEventListener("blur", function () {
            const assignmentId = this.dataset.assignmentId;
            const field = this.dataset.field;
            const value = this.textContent.trim();

            updateField(assignmentId, field, value);
        });

        cell.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                this.blur(); // Trigger blur to save changes
            }
        });
    });

    // Handle date edits (Due Date)
    document.querySelectorAll(".editable-input").forEach(function (input) {
        input.addEventListener("change", function () {
            const assignmentId = this.dataset.assignmentId;
            const field = this.dataset.field;
            const value = this.value;

            updateField(assignmentId, field, value);
        });
    });

    // Handle select dropdown edits (Subject)
    document.querySelectorAll(".editable-select").forEach(function (select) {
        select.addEventListener("change", function () {
            const assignmentId = this.dataset.assignmentId;
            const field = this.dataset.field;
            const value = this.value;

            updateField(assignmentId, field, value);
        });
    });

    // Handle "Done" checkbox
    document.querySelectorAll(".assignment-done-checkbox").forEach(function (checkbox) {
        checkbox.addEventListener("change", function () {
            const assignmentId = this.dataset.assignmentId;
            const doneStatus = this.checked ? 1 : 0;

            updateField(assignmentId, "done", doneStatus);
        });
    });

    // Function to send updates to the server
    function updateField(assignmentId, field, value) {
        fetch("", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                update_field: true,
                assignment_id: assignmentId,
                field: field,
                value: value
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`${field} updated successfully.`);
            } else {
                console.error(`Failed to update ${field}:`, data.error || "Unknown error.");
            }
        })
        .catch(error => console.error("AJAX error:", error));
    }
});

    </script>


</body>

</html>