<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Responsive Sidebar</title>
    <style>
        body {
            margin: 0;
            display: flex;
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #f4f4f9; /* Lighter neutral background */
            position: fixed;
            top: 0;
            left: 0;
            transition: all 0.3s;
            overflow-y: auto;
            border-right: 1px solid #e0e0e0; /* Light border for subtle separation */
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .toggle-btn {
            font-size: 1.5rem;
            text-align: center;
            cursor: pointer;
            padding: 1rem;
            background-color: #dcdcdc; /* Subtle gray background */
            color: #333; /* Darker text for contrast */
            border-bottom: 1px solid #e0e0e0;
        }

        .content {
            flex: 1;
            margin-left: 133px;
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
            color: #555; /* Neutral dark gray */
            text-decoration: none;
            transition: background-color 0.3s, color 0.3s;
        }

        .nav-link i {
            margin-right: 1rem;
        }

        .nav-link:hover {
            background-color: #e0e0e0; /* Light hover effect */
            color: #333; /* Dark text on hover */
        }

        .nav-link.active {
            background-color: #dcdcdc; /* Active link with subtle gray */
            color: #333; /* Dark text for active link */
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
            <a class="nav-link active" href="#">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link" href="features/habit.php">
                <i class="fas fa-tasks"></i>
                <span>Habit Tracker</span>
            </a>
            <a class="nav-link" href="#">
                <i class="fas fa-calendar-alt"></i>
                <span>Calendar</span>
            </a>
            <a class="nav-link" href="#">
                <i class="fas fa-sticky-note"></i>
                <span>Notes</span>
            </a>
        </nav>
    </div>

</body>

</html>
