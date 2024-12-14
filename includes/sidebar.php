<div class="sidebar" id="sidebar">
    <div class="toggle-btn" onclick="toggleSidebar()">&#9776;</div>
    <nav class="nav flex-column">
        <a class="nav-link active" href="">Dashboard</a>
        <a class="nav-link" href="features/habit.php">Habit tracker</a>
        <a class="nav-link" href="#">Calendar</a>
        <a class="nav-link" href="#">Notes</a>
    </nav>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('collapsed');
    }
</script>
