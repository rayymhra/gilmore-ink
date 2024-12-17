<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Please log in first.");
}

$user_id = $_SESSION['user_id'];

// Handle form submission for recording study time
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_pomodoro'])) {
    $study_duration = $_POST['study_duration'] * 60; // Convert to seconds
    $rest_duration = $_POST['rest_duration'] * 60; // Convert to seconds

    // Log the Pomodoro time into the database
    $stmt = $conn->prepare("INSERT INTO pomodoro_logs (user_id, study_duration, rest_duration) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $user_id, $study_duration, $rest_duration);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => true]);
    exit;
}

// Handle form submission for stopping the Pomodoro session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish_pomodoro'])) {
    $elapsed_time = intval($_POST['elapsed_time']); // Elapsed time in seconds
    $session_type = $_POST['session_type']; // Study or rest

    // Determine if this is a study or rest session
    if ($session_type === "study") {
        $study_duration = $elapsed_time;
        $rest_duration = 0;
    } else {
        $study_duration = 0;
        $rest_duration = $elapsed_time;
    }

    // Insert the session data into the database
    $stmt = $conn->prepare("INSERT INTO pomodoro_logs (user_id, study_duration, rest_duration) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $user_id, $study_duration, $rest_duration);
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
    <title>Pomodoro Timer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        
        .toggle-btn {
            font-size: 1.5rem;
            text-align: center;
            cursor: pointer;
            padding: 1rem;
            background-color: #dcdcdc;
            color: #333;
            border-bottom: 1px solid #e0e0e0;
        }
        .timer-display {
            font-size: 3rem;
            font-weight: bold;
            color: #3a3a3a;
            margin-bottom: 20px;
        }

        .timer-button {
            font-size: 1.2rem;
            padding: 12px 20px;
            margin: 10px;
            border-radius: 8px;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #A1896E; /* Muted brown button */
            border: none;
            border-radius: 8px;
            padding: 0.8rem 2rem;
            color: white;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #AB967D; /* Slightly darker shade on hover */
        }

        .btn-success {
            background-color: #A1896E;
            border: none;
            color: white;
        }

        .btn-success:hover {
            background-color: #AB967D;
        }

        .btn-danger {
            background-color: #D3C8BB;
            border: none;
            color: white;
        }

        .btn-danger:hover {
            background-color: #BFAF9C;
        }

        .btn-warning {
            background-color: #E7E1DA;
            border: none;
            color: #3a3a3a;
        }

        .btn-warning:hover {
            background-color: #D3C8BB;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #d1d1d1;
            margin-bottom: 20px;
        }

        select.form-control {
            height: 45px;
            font-size: 1.1rem;
        }

        h1 {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 30px;
            color: #A1896E;
        }

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
            <a class="nav-link active" href="../tracker/pomodoro.php">
                <i class="fas fa-hourglass-start"></i>
                <span>Pomodoro Timer</span>
            </a>
        </nav>
    </div>
    <div class="content">
        <div class="container mt-4">
            <h1 class="text-center">Pomodoro Timer</h1>

            <!-- Form for setting study and rest times -->
            <form method="POST" class="mb-4" id="pomodoro-form">
                <div class="row">
                    <div class="col-md-6">
                        <label for="study_duration">Study Duration (minutes):</label>
                        <input type="number" id="study_duration" name="study_duration" class="form-control" value="25" min="1">
                    </div>
                    <div class="col-md-6">
                        <label for="rest_duration">Rest Duration (minutes):</label>
                        <input type="number" id="rest_duration" name="rest_duration" class="form-control" value="5" min="1">
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="session_type">Choose Session Type:</label>
                        <select id="session_type" name="session_type" class="form-control">
                            <option value="study">Study</option>
                            <option value="rest">Rest</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Set Timer</button>
            </form>

            <!-- Timer Display -->
            <div class="text-center">
                <div id="timer-display" class="timer-display">25:00</div>
                <button id="start-button" class="btn btn-success timer-button">Start</button>
                <button id="finish-button" class="btn btn-danger timer-button" disabled>Finish</button>
                <button id="reset-button" class="btn btn-warning timer-button" disabled>Reset</button>
            </div>
        </div>
    </div>


    <script>
        let studyDuration = 25 * 60; // default study duration (25 minutes converted to seconds)
        let restDuration = 5 * 60; // default rest duration (5 minutes converted to seconds)
        let timer;
        let isStudyTime = true;
        let remainingTime = 0;

        const timerDisplay = document.getElementById("timer-display");
        const startButton = document.getElementById("start-button");
        const finishButton = document.getElementById("finish-button");
        const resetButton = document.getElementById("reset-button");
        const sessionTypeDropdown = document.getElementById("session_type");

        document.getElementById("pomodoro-form").addEventListener("submit", function(e) {
            e.preventDefault();
            // Convert selected minutes into seconds
            studyDuration = parseInt(document.getElementById("study_duration").value) * 60;
            restDuration = parseInt(document.getElementById("rest_duration").value) * 60;
            timerDisplay.textContent = formatTime(studyDuration);
        });

        startButton.addEventListener("click", function() {
            startButton.disabled = true;
            finishButton.disabled = false;
            resetButton.disabled = false;

            const sessionType = sessionTypeDropdown.value;
            if (sessionType === "study") {
                isStudyTime = true;
                remainingTime = studyDuration;
            } else {
                isStudyTime = false;
                remainingTime = restDuration;
            }
            startPomodoro();
        });

        resetButton.addEventListener("click", function() {
            clearInterval(timer);
            startButton.disabled = false;
            finishButton.disabled = true;
            resetButton.disabled = true;
            timerDisplay.textContent = formatTime(studyDuration);
        });

        finishButton.addEventListener("click", function() {
            clearInterval(timer);

            // Calculate the elapsed time
            const elapsedTime = (isStudyTime ? studyDuration : restDuration) - remainingTime;

            // Record the actual time studied or rested
            recordPomodoroTime(elapsedTime);

            startButton.disabled = false;
            finishButton.disabled = true;
            resetButton.disabled = true;
            timerDisplay.textContent = "Finished!";
        });


        function startPomodoro() {
            timer = setInterval(function() {
                remainingTime--;
                timerDisplay.textContent = formatTime(remainingTime);

                if (remainingTime <= 0) {
                    clearInterval(timer);
                    isStudyTime = !isStudyTime;
                    remainingTime = isStudyTime ? studyDuration : restDuration;
                    startPomodoro();
                    recordPomodoroTime();
                }
            }, 1000);
        }

        function formatTime(seconds) {
            let minutes = Math.floor(seconds / 60);
            let secondsLeft = seconds % 60;
            return `${String(minutes).padStart(2, '0')}:${String(secondsLeft).padStart(2, '0')}`;
        }

        function recordPomodoroTime(elapsedTime) {
            const sessionType = sessionTypeDropdown.value; // Study or rest

            fetch("", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({
                        finish_pomodoro: true,
                        session_type: sessionType,
                        elapsed_time: elapsedTime // Send elapsed time
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("Pomodoro session recorded successfully.");
                    } else {
                        console.error("Failed to record Pomodoro session.");
                    }
                })
                .catch(error => console.error("AJAX error:", error));
        }
    </script>
</body>

</html>