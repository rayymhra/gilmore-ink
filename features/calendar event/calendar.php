<?php
// Include database connection
include "../../includes/koneksi.php";
session_start();

$user_id = $_SESSION['user_id'];

// Get the current month and year
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get the first day of the month
$first_day = date('Y-m-01', strtotime("$year-$month-01"));
$last_day = date('Y-m-t', strtotime($first_day));
$days_in_month = date('t', strtotime($first_day));

// Fetch events for the current month
// Fetch events for the current month
$query = $conn->prepare("SELECT * FROM events WHERE user_id = ? AND date BETWEEN ? AND ?");
$query->bind_param("sss", $user_id, $first_day, $last_day);
$query->execute();
$result = $query->get_result();

// Organize events by date
$events_by_date = [];
while ($event = $result->fetch_assoc()) {
    $events_by_date[$event['date']][] = $event;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
        <h1>Calendar - <?php echo date('F Y', strtotime($first_day)); ?></h1>
        <div class="d-flex justify-content-between mb-3">
            <a href="?month=<?php echo $month - 1; ?>&year=<?php echo $year; ?>" class="btn btn-primary">Previous</a>
            <a href="?month=<?php echo $month + 1; ?>&year=<?php echo $year; ?>" class="btn btn-primary">Next</a>
        </div>

        <!-- Calendar Table -->
        <table class="table table-bordered text-center">
            <thead class="table-light">
                <tr>
                    <th>Sunday</th>
                    <th>Monday</th>
                    <th>Tuesday</th>
                    <th>Wednesday</th>
                    <th>Thursday</th>
                    <th>Friday</th>
                    <th>Saturday</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Start the calendar from the first day of the month
                $start_day = date('w', strtotime($first_day));
                $current_day = 1;

                // Print rows for the weeks
                for ($week = 0; $week < 6; $week++) {
                    echo "<tr>";

                    // Print each day in the week
                    for ($day = 0; $day < 7; $day++) {
                        if ($week === 0 && $day < $start_day || $current_day > $days_in_month) {
                            echo "<td></td>"; // Empty cell
                        } else {
                            $current_date = date('Y-m-d', strtotime("$year-$month-$current_day"));
                            echo "<td class='calendar-cell' data-date='$current_date'>";
                            echo "<strong>$current_day</strong>";
                            if (isset($events_by_date[$current_date])) {
                                echo "<ul class='list-unstyled'>";
                                foreach ($events_by_date[$current_date] as $event) {
                                    echo "<li class='badge bg-info mt-1'>{$event['title']}</li>";
                                }
                                echo "</ul>";
                            }
                            echo "</td>";
                            $current_day++;
                        }
                    }

                    echo "</tr>";
                    if ($current_day > $days_in_month) break;
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for Adding Event -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">Add Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="eventForm">
                        <input type="hidden" id="eventDate" name="date">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="Work">Work</option>
                                <option value="Personal">Personal</option>
                                <option value="Study">Study</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Event</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show modal on cell click
        $('.calendar-cell').on('click', function () {
            const date = $(this).data('date');
            $('#eventDate').val(date);
            $('#eventModal').modal('show');
        });

        // Submit form via AJAX
        $('#eventForm').on('submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: 'add_event.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    alert('Event added successfully!');
                    location.reload();
                },
                error: function () {
                    alert('Failed to add event.');
                }
            });
        });
    </script>
</body>
</html>
