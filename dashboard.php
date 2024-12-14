<?php
include "includes/koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    // echo json_encode(['success' => false, 'message' => 'User not logged in']);
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false];

// Handle file uploads
if (isset($_FILES['cover']) || isset($_FILES['icon'])) {
    $upload_dir = "uploads/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Save cover image
    if (isset($_FILES['cover'])) {
        $cover_name = time() . '_cover_' . basename($_FILES['cover']['name']);
        $cover_path = $upload_dir . $cover_name;

        if (move_uploaded_file($_FILES['cover']['tmp_name'], $cover_path)) {
            $cover_db_path = "uploads/" . $cover_name;
            mysqli_query($conn, "UPDATE dashboard SET cover_path = '$cover_db_path' WHERE user_id = '$user_id'");
            $response['cover_path'] = $cover_db_path;
        }
    }

    // Save icon image
    if (isset($_FILES['icon'])) {
        $icon_name = time() . '_icon_' . basename($_FILES['icon']['name']);
        $icon_path = $upload_dir . $icon_name;

        if (move_uploaded_file($_FILES['icon']['tmp_name'], $icon_path)) {
            $icon_db_path = "uploads/" . $icon_name;
            mysqli_query($conn, "UPDATE dashboard SET icon_path = '$icon_db_path' WHERE user_id = '$user_id'");
            $response['icon_path'] = $icon_db_path;
        }
    }

    $response['success'] = true;
}

// Handle title update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $result = mysqli_query($conn, "UPDATE dashboard SET title = '$title' WHERE user_id = '$user_id'");
    $response['success'] = $result ? true : false;
}

echo json_encode($response);


$default_cover = 'assets/default-cover.jpg';
$default_icon = 'assets/default-icon.png';

// Fetch dashboard data
$query = "SELECT title, cover_path, icon_path FROM dashboard WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query);
$dashboard = mysqli_fetch_assoc($result);

$title = $dashboard['title'] ?? 'Student Planner';
$cover_path = $dashboard['cover_path'] ?? $default_cover;
$icon_path = $dashboard['icon_path'] ?? $default_icon;

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #f7f7f7;
            position: fixed;
            left: 0;
            top: 0;
            transition: all 0.3s;
            overflow: auto;
            border-right: 1px solid #ddd;
        }

        .sidebar.collapsed {
            width: 60px;
        }

        .sidebar .nav-link {
            text-align: left;
            padding-left: 20px;
        }

        .sidebar.collapsed .nav-link {
            text-align: center;
            padding-left: 0;
        }

        .sidebar .toggle-btn {
            text-align: right;
            padding: 10px;
            cursor: pointer;
        }

        .sidebar.collapsed .toggle-btn {
            text-align: center;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .content.collapsed {
            margin-left: 60px;
        }

        .cover-container {
            position: relative;
            width: 100%;
            height: 200px;
            background: url('<?php echo $cover_path; ?>') center center/cover;
        }

        .cover-container input[type="file"] {
            display: none;
        }

        .change-cover-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            padding: 5px 10px;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        .icon-title-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }

        .icon-title-container img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
        }

        .editable-title {
            font-size: 1.5rem;
            font-weight: bold;
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <!-- <div class="sidebar" id="sidebar">
        <div class="toggle-btn" onclick="toggleSidebar()">&#9776;</div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="">Dashboard</a>
            <a class="nav-link" href="features/habit.php">Habit tracker</a>
            <a class="nav-link" href="#">Calendar</a>
            <a class="nav-link" href="#">Notes</a>
        </nav>
    </div> -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Content -->
    <div class="content" id="content">
        <!-- Cover Section -->
        <div class="cover-container" id="cover" style="background-image: url('<?php echo $cover_path; ?>');">
            <input type="file" id="cover-input" accept="image/*" onchange="updateCover()">
            <button class="change-cover-btn" onclick="document.getElementById('cover-input').click();">Change Cover</button>

        </div>



        <!-- Icon and Title Section -->
        <div class="icon-title-container">
            <label for="icon-input">
                <img id="icon" src="<?php echo $icon_path; ?>" alt="Icon">
            </label>
            <input type="file" id="icon-input" accept="image/*" style="display:none" onchange="updateIcon()">

            <input type="text" id="title" class="editable-title" value="<?php echo htmlspecialchars($title); ?>" onblur="updateTitle()">
        </div>

        <!-- Main Content Area -->
        <div>
            <h3>Welcome to your Student Planner!</h3>
            <p>Customize this dashboard to suit your needs.</p>
        </div>
    </div>

    <script>
        // Update cover image
        function updateCover() {
            const input = document.getElementById('cover-input');
            if (!input.files || input.files.length === 0) {
                alert("No file selected.");
                return;
            }

            const formData = new FormData();
            formData.append('cover', input.files[0]);

            fetch('update_dashboard.php', {
                    method: 'POST',
                    body: formData,
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        document.getElementById('cover').style.backgroundImage = `url(${data.cover_path})`;
                    } else {
                        alert('Failed to update cover image.');
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the cover image.');
                });
        }


        // Update icon image
        function updateIcon() {
            const input = document.getElementById('icon-input');
            const formData = new FormData();
            formData.append('icon', input.files[0]);

            fetch('update_dashboard.php', {
                    method: 'POST',
                    body: formData,
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        document.getElementById('icon').src = data.icon_path;
                    } else {
                        alert('Failed to update icon image.');
                    }
                });
        }
    </script>
</body>

</html>