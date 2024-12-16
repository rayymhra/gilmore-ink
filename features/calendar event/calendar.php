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
</head>

<body>
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
                                    $category = isset($event['category']) ? $event['category'] : 'Uncategorized'; // Default if category is null
                                    echo "<li class='badge bg-info mt-1 edit-event' 
                                          data-id='{$event['id']}' 
                                          data-title='{$event['title']}' 
                                          data-date='{$event['date']}' 
                                          data-category='{$category}'>
                                          {$event['title']} ({$category})
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
                            <label for="addCategory" class="form-label">Category</label>
                            <select class="form-control" id="addCategory" name="category">
                                <option value="">-- Select Category --</option>
                            </select>
                            <!-- <input type="text" class="form-control mt-2" id="addCustomCategory" placeholder="Add a new category"> -->
                            <!-- <button type="button" class="btn btn-sm btn-primary mt-2" id="addCategoryBtn">Add Category</button> -->
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
                            <label for="editCategory" class="form-label">Category</label>
                            <select class="form-control" id="editCategory" name="category">
                                <option value="">-- Select Category --</option>
                            </select>
                            <!-- <input type="text" class="form-control mt-2" id="editCustomCategory" placeholder="Add a new category"> -->
                            <!-- <button type="button" class="btn btn-sm btn-primary mt-2" id="editCategoryBtn">Add Category</button> -->
                        </div>



                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

   <!-- Category Management Section -->
<div class="category-management mt-4">
    <h5>Manage Categories</h5>
    
    <!-- Add New Category -->
    <button id="add-category-btn" class="btn btn-primary" data-toggle="modal" data-target="#addCategoryModal">Add New Category</button>
    
    <!-- Category List (Edit & Delete options) -->
    <ul id="category-list" class="list-unstyled mt-2">
        <!-- Categories will be populated here via JS -->
    </ul>
</div>

<!-- Modal for Adding a Category -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addCategoryForm">
                    <div class="form-group">
                        <label for="categoryName">Category Name</label>
                        <input type="text" class="form-control" id="categoryName" name="categoryName" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Editing a Category -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editCategoryForm">
                    <input type="hidden" id="editCategoryId" name="id">
                    <div class="form-group">
                        <label for="editCategoryName">Category Name</label>
                        <input type="text" class="form-control" id="editCategoryName" name="categoryName" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>


    <script>
        $(document).ready(function() {
            // Open add event modal when clicking a calendar cell
            $(document).on('click', '.calendar-cell', function(e) {
                // Prevent triggering the modal if clicking an individual event
                if ($(e.target).hasClass('edit-event')) return;

                const selectedDate = $(this).data('date');
                $('#addDate').val(selectedDate);
                $('#addEventModal').modal('show');
            });

            // Open edit event modal when clicking an individual event
            $(document).on('click', '.edit-event', function(e) {
                e.stopPropagation();
                const eventId = $(this).data('id');
                const eventTitle = $(this).data('title');
                const eventDate = $(this).data('date');
                const eventLabel = $(this).data('label');

                $('#editEventId').val(eventId);
                $('#editTitle').val(eventTitle);
                $('#editDate').val(eventDate);
                $('#editLabel').val(eventLabel);
                $('#editEventModal').modal('show');
            });

            // Submit add event form via AJAX
            $('#addEventForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'add_event.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        console.log("Response from add_event.php:", response);
                        alert('Event added successfully!');
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error("Error adding event:", xhr.responseText);
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
                        console.log("Response from edit_event.php:", response);
                        alert('Event updated successfully!');
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error("Error updating event:", xhr.responseText);
                        alert('Failed to update event.');
                    }
                });
            });

//             // Function to fetch categories
//             function fetchCategories() {
//     $.ajax({
//         url: 'fetch_categories.php',
//         method: 'GET',
//         success: function(response) {
//             console.log(response); // The response should now contain id and name
//             const categories = response;
//             const categoryList = $('#category-list');
//             categoryList.empty(); // Clear previous categories

//             categories.forEach(function(category) {
//                 categoryList.append(`
//                     <li>
//                         <span class="category-name">${category.name}</span>
//                         <button class="edit-category-btn btn btn-link" data-id="${category.id}">Edit</button>
//                         <button class="delete-category-btn btn btn-link text-danger" data-id="${category.id}">Delete</button>
//                     </li>
//                 `);
//             });
//         },
//         error: function(xhr, status, error) {
//             console.error("Error fetching categories:", xhr.responseText);
//         }
//     });
// }



//             // Fetch categories on page load
//             fetchCategories();

//             // Add New Category
//             $('#add-category-btn').on('click', function() {
//                 const newCategoryName = prompt("Enter New Category Name:");
//                 if (newCategoryName) {
//                     $.ajax({
//                         url: 'add_category.php',
//                         method: 'POST',
//                         data: {
//                             name: newCategoryName
//                         },
//                         success: function(response) {
//                             alert(response);
//                             fetchCategories(); // Refresh category list
//                         },
//                         error: function(xhr, status, error) {
//                             console.error("Error adding category:", xhr.responseText);
//                         }
//                     });
//                 }
//             });

    

//             // Edit Category
//             $(document).on('click', '.edit-category-btn', function() {
//     const categoryId = $(this).data('id');
//     const categoryName = $(this).siblings('.category-name').text();
//     const newCategoryName = prompt("Edit Category Name:", categoryName);

//     if (newCategoryName && newCategoryName !== categoryName) {
//         $.ajax({
//             url: 'edit_category.php',
//             method: 'POST',
//             data: {
//                 id: categoryId,
//                 name: newCategoryName
//             },
//             success: function(response) {
//                 alert(response);
//                 fetchCategories(); // Refresh category list
//             },
//             error: function(xhr, status, error) {
//                 console.error("Error editing category:", xhr.responseText);
//             }
//         });
//     }
// });


//             // Delete Category
//             $(document).on('click', '.delete-category-btn', function() {
//     const categoryId = $(this).data('id');

//     const confirmation = confirm("Are you sure you want to delete this category?");
//     if (confirmation) {
//         $.ajax({
//             url: 'delete_category.php',
//             method: 'POST',
//             data: {
//                 id: categoryId
//             },
//             success: function(response) {
//                 alert(response);
//                 fetchCategories(); // Refresh category list
//             },
//             error: function(xhr, status, error) {
//                 console.error("Error deleting category:", xhr.responseText);
//             }
//         });
//     }
// });

//         });




//         // For debugging
// $.ajax({
//     url: 'edit_category.php',
//     method: 'POST',
//     data: {
//         id: categoryId,
//         name: newCategoryName
//     },
//     success: function(response) {
//         console.log(response); // Log the response to debug
//         alert(response);
//         fetchCategories(); // Refresh category list
//     },
//     error: function(xhr, status, error) {
//         console.error("Error editing category:", xhr.responseText);
//     }
});

    </script>

<script>
       $(document).ready(function() {
    // Function to fetch categories
    function fetchCategories() {
        $.ajax({
            url: 'fetch_categories.php',
            method: 'GET',
            success: function(response) {
                const categories = JSON.parse(response);
                const categoryList = $('#category-list');
                categoryList.empty(); // Clear previous categories

                categories.forEach(function(category) {
                    categoryList.append(`
                        <li>
                            <span class="category-name">${category.name}</span>
                            <button class="edit-category-btn btn btn-link" data-id="${category.id}" data-name="${category.name}">Edit</button>
                            <button class="delete-category-btn btn btn-link text-danger" data-id="${category.id}">Delete</button>
                        </li>
                    `);
                });
            },
            error: function(xhr, status, error) {
                console.error("Error fetching categories:", xhr.responseText);
            }
        });
    }

    // Fetch categories on page load
    fetchCategories();

    // Add New Category
    $('#add-category-btn').on('click', function() {
        $('#addCategoryModal').modal('show');
    });

    // Submit Add Category form via AJAX
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();
        const categoryName = $('#categoryName').val();

        if (!categoryName.trim()) {
            alert('Category name cannot be empty!');
            return;
        }

        $.ajax({
            url: 'add_category.php',
            method: 'POST',
            data: { name: categoryName },
            success: function(response) {
                console.log('Category added successfully:', response);
                alert('Category added successfully!');
                $('#addCategoryModal').modal('hide');
                fetchCategories(); // Refresh category list
            },
            error: function(xhr, status, error) {
                console.error("Error adding category:", xhr.responseText);
                alert('Failed to add category.');
            }
        });
    });

    // Edit Category
    $(document).on('click', '.edit-category-btn', function() {
        const categoryId = $(this).data('id');
        const categoryName = $(this).data('name');
        $('#editCategoryId').val(categoryId);
        $('#editCategoryName').val(categoryName);
        $('#editCategoryModal').modal('show');
    });

    // Submit Edit Category form via AJAX
    $('#editCategoryForm').on('submit', function(e) {
        e.preventDefault();
        const categoryId = $('#editCategoryId').val();
        const categoryName = $('#editCategoryName').val();

        $.ajax({
            url: 'edit_category.php',
            method: 'POST',
            data: { id: categoryId, categoryName: categoryName },
            success: function(response) {
                alert('Category updated successfully!');
                $('#editCategoryModal').modal('hide');
                fetchCategories(); // Refresh category list
            },
            error: function(xhr, status, error) {
                console.error("Error editing category:", xhr.responseText);
            }
        });
    });

    // Delete Category
    $(document).on('click', '.delete-category-btn', function() {
        const categoryId = $(this).data('id');
        if (confirm('Are you sure you want to delete this category?')) {
            $.ajax({
                url: 'delete_category.php',
                method: 'POST',
                data: { id: categoryId },
                success: function(response) {
                    alert('Category deleted successfully!');
                    fetchCategories(); // Refresh category list
                },
                error: function(xhr, status, error) {
                    console.error("Error deleting category:", xhr.responseText);
                }
            });
        }
    });
});

    </script>
</body>

</html>