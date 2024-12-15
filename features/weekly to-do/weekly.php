<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Please log in first.");
}

$user_id = $_SESSION['user_id'];

// Function to calculate week range
function getWeekRange($start_date = null) {
    $start_date = $start_date ?: date('Y-m-d');
    $start_of_week = date('Y-m-d', strtotime('monday this week', strtotime($start_date)));
    $end_of_week = date('Y-m-d', strtotime('sunday this week', strtotime($start_of_week)));
    return [$start_of_week, $end_of_week];
}

// Get week range for navigation and filtering
$start_date = $_GET['start_date'] ?? date('Y-m-d');
[$start_of_week, $end_of_week] = getWeekRange($start_date);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['title'], $_POST['task_date'], $_POST['priority'])) {
        $title = $_POST['title'];
        $description = $_POST['description'] ?? null;
        $task_date = $_POST['task_date'];
        $task_time = $_POST['task_time'] ?: null; // Optional time
        $priority = $_POST['priority'];

        // Validate priority
        $allowed_priorities = ['high', 'medium', 'low'];
        if (!in_array($priority, $allowed_priorities)) {
            die("Invalid priority value.");
        }

        // Insert task using prepared statements
        $stmt = $conn->prepare("INSERT INTO weekly_tasks (user_id, title, description, task_date, task_time, priority) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $user_id, $title, $description, $task_date, $task_time, $priority);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch tasks for the selected week
$stmt = $conn->prepare("SELECT * FROM weekly_tasks 
                        WHERE user_id = ? 
                        AND task_date BETWEEN ? AND ?
                        ORDER BY task_date, task_time");
$stmt->bind_param("iss", $user_id, $start_of_week, $end_of_week);
$stmt->execute();
$tasks = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly To-Do List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h1 class="text-center">Weekly To-Do List</h1>

        <!-- Form for adding tasks -->
        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" name="title" class="form-control" placeholder="Task Title" required>
                </div>
                <div class="col-md-6">
                    <input type="text" name="description" class="form-control" placeholder="Description (Optional)">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4">
                    <input type="date" name="task_date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <input type="time" name="task_time" class="form-control" placeholder="Time (Optional)">
                </div>
                <div class="col-md-4">
                    <select name="priority" class="form-select" required>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Add Task</button>
        </form>

        <!-- Navigate between weeks -->
        <div class="d-flex justify-content-between mb-3">
            <form method="GET" action="">
                <input type="hidden" name="start_date" value="<?= date('Y-m-d', strtotime($start_of_week . ' -7 days')) ?>">
                <button type="submit" class="btn btn-primary">Previous Week</button>
            </form>
            <h4 class="text-center">
                Week: <?= $start_of_week ?> - <?= $end_of_week ?>
            </h4>
            <form method="GET" action="">
                <input type="hidden" name="start_date" value="<?= date('Y-m-d', strtotime($start_of_week . ' +7 days')) ?>">
                <button type="submit" class="btn btn-primary">Next Week</button>
            </form>
        </div>

        <!-- Weekly Tasks Table -->
        <h2>Tasks for the Week</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Priority</th>
                    <th>Done</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $tasks->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['task_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['task_time'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge 
                            <?= $row['priority'] === 'high' ? 'bg-danger' : ($row['priority'] === 'medium' ? 'bg-warning' : 'bg-success') ?>">
                                <?= ucfirst($row['priority']) ?>
                            </span>
                        </td>
                        <td>
                            <input type="checkbox" class="form-check-input task-done-checkbox"
                                data-task-id="<?= $row['id'] ?>"
                                <?= $row['done'] ? 'checked' : '' ?>>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".task-done-checkbox").forEach(function (checkbox) {
                checkbox.addEventListener("change", function () {
                    const taskId = this.dataset.taskId;
                    const doneStatus = this.checked ? 1 : 0;

                    fetch("update_task.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                        },
                        body: new URLSearchParams({
                            update_done_status: true,
                            task_id: taskId,
                            done: doneStatus
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log("Task status updated successfully.");
                        } else {
                            console.error("Failed to update task:", data.error || "Unknown error.");
                        }
                    })
                    .catch(error => console.error("AJAX error:", error));
                });
            });
        });
    </script>
</body>

</html>
