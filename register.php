<?php
// include 'includes/koneksi.php';

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $username = $_POST['username'];
//     $email = $_POST['email'];
//     $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

//     // Check if email or username already exists
//     $query = $conn->prepare("SELECT id FROM user WHERE email = ? OR username = ?");
//     $query->bind_param("ss", $email, $username);
//     $query->execute();
//     $query->store_result();

//     if ($query->num_rows > 0) {
//         $error = "Email or username is already taken.";
//     } else {
//         // Insert new user
//         $query = $conn->prepare("INSERT INTO user (username, email, password) VALUES (?, ?, ?)");
//         $query->bind_param("sss", $username, $email, $password);
//         if ($query->execute()) {
//             header("Location: login.php?success=1");
//             exit();
//         } else {
//             $error = "Error registering user.";
//         }
//     }
// }
?>

<?php
include "includes/koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Encrypt password
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Insert new user into the user table
    $query = "INSERT INTO user (username, password, email) VALUES ('$username', '$password', '$email')";
    $result = mysqli_query($conn, $query);

    if ($result) {
        // Get the new user's ID
        $user_id = mysqli_insert_id($conn);

        // Insert default values into the dashboard table
        $default_cover = 'assets/default-cover.jpg';
        $default_icon = 'assets/default-icon.png';
        $default_title = 'Student Planner';

        $dashboard_query = "INSERT INTO dashboard (user_id, title, cover_path, icon_path) 
                            VALUES ('$user_id', '$default_title', '$default_cover', '$default_icon')";
        $dashboard_result = mysqli_query($conn, $dashboard_query);

        if ($dashboard_result) {
            echo "User registered successfully, and dashboard initialized!";
        } else {
            echo "Error initializing dashboard: " . mysqli_error($conn);
        }
    } else {
        echo "Error registering user: " . mysqli_error($conn);
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
