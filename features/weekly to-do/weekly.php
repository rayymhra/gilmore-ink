<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Please log in first.");
}

$user_id = $_SESSION['user_id'];

// Function to calculate week range
function getWeekRange($start_date = null)
{
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

        /* Custom Weekly Table */
        .weekly-table {
            border-collapse: separate;
            border-spacing: 0 8px;
            /* Add spacing between rows */
            width: 100%;
        }

        .weekly-table thead th {
            background-color: #E7E1DA;
            /* Light neutral */
            color: #333;
            font-weight: 600;
            padding: 12px;
            text-align: center;
            border: none;
        }

        .weekly-table tbody tr {
            background-color: #FAF9F7;
            /* Slightly off-white */
            transition: all 0.2s ease;
        }

        .weekly-table tbody tr:hover {
            background-color: #D3C8BB;
            /* Hover color */
            transform: translateY(-3px);
        }

        .weekly-table td {
            padding: 12px;
            text-align: center;
            vertical-align: middle;
            border: none;
            box-shadow: 0 0 3px rgba(0, 0, 0, 0.1);
        }

        /* Priority Badges */
        .priority-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .priority-high {
            background-color: #A1896E;
            /* Deep brown */
            color: #FFF;
        }

        .priority-medium {
            background-color: #AB967D;
            /* Light brown */
            color: #FFF;
        }

        .priority-low {
            background-color: #BFAF9C;
            /* Subtle neutral */
            color: #FFF;
        }

        /* Checkbox Styling */
        .form-check-input {
            transform: scale(1.2);
        }

        /* Buttons */
        .btn-primary {
            background-color: #A1896E;
            border-color: #A1896E;
        }

        .btn-primary:hover {
            background-color: #AB967D;
            border-color: #AB967D;
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
            <a class="nav-link active" href="../weekly to-do/weekly.php">
                <i class="fas fa-tasks"></i>
                <span>Weekly To-do</span>
            </a>
            <a class="nav-link" href="../notes/notes.php">
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

            <!-- Weekly Navigation -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <form method="GET" action="">
                    <input type="hidden" name="start_date" value="<?= date('Y-m-d', strtotime($start_of_week . ' -7 days')) ?>">
                    <button type="submit" class="btn btn-primary">&larr; Previous Week</button>
                </form>
                <h4>Week: <?= $start_of_week ?> - <?= $end_of_week ?></h4>
                <form method="GET" action="">
                    <input type="hidden" name="start_date" value="<?= date('Y-m-d', strtotime($start_of_week . ' +7 days')) ?>">
                    <button type="submit" class="btn btn-primary">Next Week &rarr;</button>
                </form>
            </div>

            <!-- Weekly Tasks Table -->
            <table class="weekly-table">
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
        <tr data-task-id="<?= $row['id'] ?>">
            <td><?= htmlspecialchars($row['task_date'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['task_time'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
            <td contenteditable="true" class="editable" data-field="title">
                <?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td contenteditable="true" class="editable" data-field="description">
                <?= htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td>
                <select class="priority-edit form-select" data-field="priority">
                    <option value="high" <?= $row['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                    <option value="medium" <?= $row['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="low" <?= $row['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                </select>
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
    </div>


    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Update done status checkbox
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
                    .then(response => response.text())
                    .then(data => console.log(data))
                    .catch(error => console.error("Error:", error));
            });
        });

        // Update inline editable fields
        document.querySelectorAll(".editable").forEach(function (editable) {
            editable.addEventListener("blur", function () {
                const taskId = this.closest("tr").dataset.taskId;
                const field = this.dataset.field;
                const value = this.innerText.trim();

                fetch("update_task.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: new URLSearchParams({
                        task_id: taskId,
                        field: field,
                        value: value
                    }),
                })
                    .then(response => response.text())
                    .then(data => console.log(data))
                    .catch(error => console.error("Error:", error));
            });
        });

        // Update priority dropdown
        document.querySelectorAll(".priority-edit").forEach(function (select) {
            select.addEventListener("change", function () {
                const taskId = this.closest("tr").dataset.taskId;
                const field = this.dataset.field;
                const value = this.value;

                fetch("update_task.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: new URLSearchParams({
                        task_id: taskId,
                        field: field,
                        value: value
                    }),
                })
                    .then(response => response.text())
                    .then(data => console.log(data))
                    .catch(error => console.error("Error:", error));
            });
        });
    });
</script>

</body>

</html>