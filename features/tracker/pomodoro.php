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
    <style>
        .timer-display {
            font-size: 2rem;
            font-weight: bold;
        }

        .timer-button {
            font-size: 1.5rem;
            padding: 10px 20px;
        }
    </style>
</head>

<body>
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

            <button type="submit" class="btn btn-primary mt-3">Start Timer</button>
        </form>

        <!-- Timer Display -->
        <div class="text-center">
            <div id="timer-display" class="timer-display">25:00</div>
            <button id="start-button" class="btn btn-success timer-button">Start</button>
            <button id="finish-button" class="btn btn-danger timer-button" disabled>Finish</button>
            <button id="reset-button" class="btn btn-warning timer-button" disabled>Reset</button>
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
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
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