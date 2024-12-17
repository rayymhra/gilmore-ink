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
    <link rel="stylesheet" href="../../assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        .calendar-cell .badge {
            display: block;
            margin-top: 5px;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        h1 {
            font-weight: 600;
            font-size: 2rem;
        }

        /* Calendar */
        .table {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: center;
            border: 1px solid #D3C8BB;
        }

        .table th {
            background-color: #BFAF9C;
            color: #fff;
            font-weight: 600;
        }

        .calendar-cell {
            position: relative;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .calendar-cell:hover {
            background-color: #F9F4F0;
        }

        .calendar-cell strong {
            font-size: 1.1rem;
            color: #A1896E;
        }

        .badge .bg-info {
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 8px;
            background-color: #AB967D !important;
            color: #fff;
            margin-top: 5px;
        }

        .bg-info {
            background-color: #AB967D !important;
        }

        /* Buttons */
        .btn-primary {
            background-color: #AB967D;
            border: none;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: #A1896E;
        }

        /* Modals */
        .modal-content {
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .form-label {
            font-weight: 500;
            color: #555;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ccc;
            box-shadow: none;
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
    <?php //include '../../includes/sidebar.php'; 
    ?>
    <div class="sidebar" id="sidebar">
        <div class="toggle-btn" onclick="">Gilmore Ink</div>
        <nav class="nav flex-column">
            <a class="nav-link" href="../../dashboard.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link active" href="">
                <i class="fas fa-calendar"></i>
                <span>Calendar</span>
            </a>
            <a class="nav-link" href="../weekly to-do/weekly.php">
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
            <h1>Calendar - <?php echo date('F Y', strtotime($first_day)); ?></h1>
            <div class="d-flex justify-content-between mb-3">
                <?php
                $prev_month = $month - 1;
                $next_month = $month + 1;
                $prev_year = $year;
                $next_year = $year;

                if ($prev_month < 1) {
                    $prev_month = 12;
                    $prev_year--;
                }

                if ($next_month > 12) {
                    $next_month = 1;
                    $next_year++;
                }
                ?>
                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-primary">Previous</a>
                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-primary">Next</a>
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
                                        echo "<li class='badge bg-info mt-1 edit-event' 
                                          data-id='{$event['id']}' 
                                          data-title='{$event['title']}' 
                                          data-description='{$event['description']}'
                                          data-date='{$event['date']}'>
                                          {$event['title']}
                                         </li>";
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

        <!-- Add Event Modal -->
        <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addEventModalLabel">Add Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addEventForm">
                            <div class="mb-3">
                                <label for="addTitle" class="form-label">Title</label>
                                <input type="text" class="form-control" id="addTitle" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="addDate" class="form-label">Date</label>
                                <input type="date" class="form-control" id="addDate" name="date" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="addDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="addDescription" name="description"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Add Event</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Editing Event -->
        <div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editEventForm">
                            <input type="hidden" id="editEventId" name="id">
                            <div class="mb-3">
                                <label for="editTitle" class="form-label">Title</label>
                                <input type="text" class="form-control" id="editTitle" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="editDate" class="form-label">Date</label>
                                <input type="date" class="form-control" id="editDate" name="date" required>
                            </div>
                            <div class="mb-3">
                                <label for="editDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="editDescription" name="description"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class='btn btn-danger btn-sm delete-btn' data-id="" id="deleteEventBtn">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        $(document).ready(function() {
            // Open add event modal when clicking a calendar cell
            $(document).on('click', '.calendar-cell', function(e) {
                if ($(e.target).hasClass('edit-event')) return;
                const selectedDate = $(this).data('date');
                $('#addDate').val(selectedDate);
                $('#addEventModal').modal('show');
            });

            // Open edit event modal when clicking an event
            $(document).on('click', '.edit-event', function(e) {
                e.stopPropagation();
                const eventId = $(this).data('id');
                const eventTitle = $(this).data('title');
                const eventDescription = $(this).data('description');
                const eventDate = $(this).data('date');

                $('#editEventId').val(eventId);
                $('#editTitle').val(eventTitle);
                $('#editDescription').val(eventDescription);
                $('#editDate').val(eventDate);
                $('#deleteEventBtn').data('id', eventId); // Set event ID for delete button
                $('#editEventModal').modal('show');
            });

            // Handle delete event
            $(document).on('click', '#deleteEventBtn', function(e) {
                e.stopPropagation();
                const eventId = $(this).data('id');

                if (confirm('Are you sure you want to delete this event?')) {
                    $.ajax({
                        url: 'delete_event.php',
                        type: 'POST',
                        data: {
                            id: eventId
                        },
                        success: function(response) {
                            const data = JSON.parse(response);
                            if (data.success) {
                                alert('Event deleted successfully!');
                                location.reload();
                            } else {
                                alert('Failed to delete event.');
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('Error deleting event.');
                        }
                    });
                }
            });

            // Submit add event form via AJAX
            $('#addEventForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'add_event.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        alert('Event added successfully!');
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('Failed to add event.');
                    }
                });
            });

            // Submit edit event form via AJAX
            $('#editEventForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'edit_event.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        alert('Event updated successfully!');
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('Failed to update event.');
                    }
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>

</html>