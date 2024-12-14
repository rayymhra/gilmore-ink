<?php
include "../includes/koneksi.php";
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
</head>

<body>
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
</body>

</html>
