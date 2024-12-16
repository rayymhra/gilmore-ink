<?php
include "includes/koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Encrypt password
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if email or username already exists
    $check_query = "SELECT id FROM user WHERE email = '$email' OR username = '$username'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $error = "Email or username is already taken.";
    } else {
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
                header("Location: login.php?success=1");
                exit();
            } else {
                $error = "Error initializing dashboard: " . mysqli_error($conn);
            }
        } else {
            $error = "Error registering user: " . mysqli_error($conn);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #E7E1DA;
            font-family: 'Inter', sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 400px;
            margin: 100px auto;
            padding: 40px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #D3C8BB;
        }

        h2 {
            font-size: 24px;
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        label {
            font-size: 14px;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            border: 1px solid #D3C8BB;
            font-size: 16px;
            color: #333;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #A1896E;
            outline: none;
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: #A1896E;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #AB967D;
        }

        p {
            text-align: center;
            font-size: 14px;
            color: #555;
        }

        a {
            color: #A1896E;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .error-message {
            color: #D9534F;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Register</h2>
        <?php if (isset($error)): ?>
            <div class="error-message" id="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </div>

    <script>
        // SweetAlert for error
        <?php if (isset($error)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#A1896E'
            });
        <?php endif; ?>
    </script>
</body>

</html>
