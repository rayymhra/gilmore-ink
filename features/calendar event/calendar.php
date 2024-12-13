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
$query = $pdo->prepare("SELECT * FROM events WHERE user_id = ? AND date BETWEEN ? AND ?");
$query->execute([$user_id, $first_day, $last_day]);
$events = $query->fetchAll(PDO::FETCH_ASSOC);

// Organize events by date
$events_by_date = [];
foreach ($events as $event) {
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
    <div class="container">
        <h1 class="mt-4">Calendar - <?php echo date('F Y', strtotime($first_day)); ?></h1>
        <div class="calendar-grid">
            <?php
            for ($day = 1; $day <= $days_in_month; $day++) {
                $current_date = date('Y-m-d', strtotime("$year-$month-$day"));
                ?>
                <div class="calendar-cell" data-date="<?php echo $current_date; ?>">
                    <span><?php echo $day; ?></span>
                    <?php if (isset($events_by_date[$current_date])): ?>
                        <ul>
                            <?php foreach ($events_by_date[$current_date] as $event): ?>
                                <li><?php echo $event['title']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>
        </div>
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
                url: 'modules/add_event.php',
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
