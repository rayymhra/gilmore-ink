<?php
include "../../includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Please log in first.");
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $type = $_POST['type'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $date = $_POST['date'];

    $query = "INSERT INTO money (user_id, amount, type, description, date) 
              VALUES ('$user_id', '$amount', '$type', '$description', '$date')";
    mysqli_query($conn, $query);
}

// Fetch transactions grouped by month
$query = "
    SELECT 
        DATE_FORMAT(date, '%Y-%m') AS month, 
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
    FROM money 
    WHERE user_id = '$user_id'
    GROUP BY DATE_FORMAT(date, '%Y-%m')";
$monthly_summary = mysqli_query($conn, $query);

$query = "SELECT * FROM money WHERE user_id = '$user_id' ORDER BY date DESC";
$transactions = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Tracker</title>
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




        /* Table Styles */
        table {
            background-color: #fff;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 1rem;
            text-align: left;
        }

        th {
            background-color: #BFAF9C; /* Light muted gold for headers */
            color: #fff;
            font-weight: 600;
        }

        td {
            border-top: 1px solid #D3C8BB; /* Light gray border for rows */
        }

        tr:nth-child(even) {
            background-color: #F7F4F1; /* Subtle off-white for even rows */
        }

        .text-success {
            color: #6DBE45; /* Green for income */
        }

        .text-danger {
            color: #E74C3C; /* Red for expense */
        }

        /* Form Styles */
        .form-control {
            border-radius: 8px;
            border: 1px solid #D3C8BB;
            box-shadow: none;
            padding: 1rem;
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
            <a class="nav-link active" href="../tracker/money.php">
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
            <h1 class="text-center">Budget Tracker</h1>

            <!-- Form for adding transactions -->
            <form method="POST" class="mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <input type="number" name="amount" step="0.01" class="form-control" placeholder="Amount" required>
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-control" required>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="description" class="form-control" placeholder="Description (Optional)">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Add Transaction</button>
            </form>

            <!-- Monthly Summary Table -->
            <h2>Monthly Summary</h2>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Income</th>
                        <th>Total Expense</th>
                        <th>Net Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($monthly_summary)) { ?>
                        <tr>
                            <td><?php echo $row['month']; ?></td>
                            <td class="text-success">+<?php echo number_format($row['total_income'], 2); ?></td>
                            <td class="text-danger">-<?php echo number_format($row['total_expense'], 2); ?></td>
                            <td><?php echo number_format($row['total_income'] - $row['total_expense'], 2); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- All Transactions Table -->
            <h2>All Transactions</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($transactions)) { ?>
                        <tr>
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo ucfirst($row['type']); ?></td>
                            <td class="<?php echo $row['type'] === 'income' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $row['type'] === 'income' ? '+' : '-'; ?>
                                <?php echo number_format($row['amount'], 2); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>