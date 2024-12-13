<?php
include 'includes/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email or username already exists
    $query = $conn->prepare("SELECT id FROM user WHERE email = ? OR username = ?");
    $query->bind_param("ss", $email, $username);
    $query->execute();
    $query->store_result();

    if ($query->num_rows > 0) {
        $error = "Email or username is already taken.";
    } else {
        // Insert new user
        $query = $conn->prepare("INSERT INTO user (username, email, password) VALUES (?, ?, ?)");
        $query->bind_param("sss", $username, $email, $password);
        if ($query->execute()) {
            header("Location: login.php?success=1");
            exit();
        } else {
            $error = "Error registering user.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>
    <?php if (isset($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="POST">
        <label>Username:</label>
        <input type="text" name="username" required><br>
        <label>Email:</label>
        <input type="email" name="email" required><br>
        <label>Password:</label>
        <input type="password" name="password" required><br>
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</body>
</html>
