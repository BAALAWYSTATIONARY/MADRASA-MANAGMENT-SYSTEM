<?php
session_start();
include '../auth/db.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

// Get user info
$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Madrasa Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f8fbff 0%, #eef4ff 100%); color: #0f172a; min-height: 100vh; }

        #sidebarToggle { position: fixed; top: 20px; left: 20px; z-index: 1200; width: 46px; height: 46px; border: none; border-radius: 50%; background: #0f4c81; color: #fff; cursor: pointer; box-shadow: 0 10px 24px rgba(15, 76, 129, 0.24); font-size: 20px; }
        #sidebarToggle:hover { background: #0b3b66; }

        .sidebar { width: 280px; background: linear-gradient(180deg, #0b3b66 0%, #092a52 100%); color: white; padding: 24px 18px; position: fixed; left: 0; top: 0; min-height: 100vh; overflow-y: auto; transform: translateX(-100%); transition: transform 0.25s ease; z-index: 1100; box-shadow: 0 20px 60px rgba(2, 12, 27, 0.28); }
        body.sidebar-open .sidebar { transform: translateX(0); }
        body.sidebar-open #sidebarToggle { left: 296px; }
        body.sidebar-open .sidebar-backdrop { opacity: 1; visibility: visible; }

        .sidebar-backdrop { position: fixed; inset: 0; background: rgba(2, 12, 27, 0.38); z-index: 1000; opacity: 0; visibility: hidden; transition: all 0.25s ease; }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.12); }
        .brand-badge { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.16); font-weight: 700; letter-spacing: 1px; }
        .brand p { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #b7c9db; margin-top: 2px; }
        .sidebar-nav { list-style: none; }
        .sidebar-nav li { margin: 8px 0; }
        .sidebar-nav a { color: #dfe9f3; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; transition: all 0.2s; font-size: 14px; }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.08); color: #ffffff; }

        main { flex: 1; padding: 32px 32px 32px 84px; transition: padding 0.2s ease; }
        .welcome-card { background: linear-gradient(135deg, #0f4c81 0%, #1d6fb8 100%); color: #fff; border-radius: 18px; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; box-shadow: 0 16px 44px rgba(15, 76, 129, 0.18); }
        .welcome-card h2 { color: #fff; margin-bottom: 6px; font-size: 26px; }
        .welcome-card p { color: rgba(255,255,255,0.86); }
        .welcome-card .pill { background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.24); padding: 10px 14px; border-radius: 999px; font-weight: 600; }
        .cards-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .dash-card { background: #fff; border-radius: 14px; padding: 20px; box-shadow: 0 10px 28px rgba(15,23,42,0.06); border: 1px solid rgba(15,23,42,0.05); }
        .dash-card h3 { margin-bottom: 8px; font-size: 16px; color: #0b2350; }
        .dash-card p { color: #6b7280; font-size: 13px; margin-bottom: 12px; line-height: 1.55; }
        .dash-card .btn { display: inline-block; padding: 9px 14px; background: #0033cc; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; box-shadow: 0 6px 14px rgba(3,24,255,0.12); }
        .dash-card .btn:hover { background: #0022aa; }

        @media (max-width: 900px) { main { padding: 24px 20px 24px 72px; } .cards-grid { grid-template-columns: 1fr; } }
        @media (max-width: 640px) { main { padding: 84px 16px 24px 16px; } #sidebarToggle { top: 16px; left: 16px; } body.sidebar-open #sidebarToggle { left: 16px; } }
    </style>
</head>
<body>
    <button id="sidebarToggle" aria-label="Toggle sidebar">☰</button>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <div class="sidebar">
        <div class="brand">
            <div class="brand-badge">MS</div>
            <div>
                <center><h2 style="font-size: 16px; margin-bottom: 2px;">Madrasa System</h2></center>
                <p><?php echo htmlspecialchars($role); ?></p>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php">Dashboard</a></li>
            <?php if($role == 'Admin'): ?>
                <li><a href="manage_madrasa.php">Manage Madrasa</a></li>
                <li><a href="teacher_registration.php">Add Teacher</a></li>
                <li><a href="../student/student_registration.php">Add Student</a></li>
                <li><a href="view_students.php">View Students</a></li>
                <li><a href="view_teachers.php">View Teachers</a></li>
                <li><a href="../teacher/reports.php">View Reports</a></li>
            <?php elseif($role == 'Teacher'): ?>
                <li><a href="../student/student_registration.php">Add Student</a></li>
                <li><a href="../attendence/attendence.php">Attendance & Memorization</a></li>
                <li><a href="../teacher/exam_entry.php">Exam Entry</a></li>
                <li><a href="../teacher/reports.php">Reports</a></li>
            <?php elseif($role == 'Student'): ?>
                <li><a href="../student/my_progress.php">My Progress</a></li>
                <li><a href="../student/view_results.php">My Results</a></li>
            <?php endif; ?>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </div>

    <main>
        <section class="welcome-card">
            <div>
                <p style="font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; color: rgba(255,255,255,0.8);">Dashboard Overview</p>
                <center><h2>Welcome, <?php echo htmlspecialchars($username); ?></h2></center>
            </div>
            <div class="pill">Role: <?php echo htmlspecialchars($role); ?></div>
        </section>

        <div class="dashboard-container">
            <?php if($role == 'Admin'): ?>
            <div class="cards-grid">
                <div class="dash-card">
                    <h3>Add Student</h3>
                    <p>Register a new student and assign them to a parent and teacher.</p>
                    <a class="btn" href="../student/student_registration.php">Add Student</a>
                </div>

                <div class="dash-card">
                    <h3>Add Teacher</h3>
                    <p>Add a new teacher to allow login and manage classes.</p>
                    <a class="btn" href="teacher_registration.php">Add Teacher</a>
                </div>

                <div class="dash-card">
                    <h3>View Students</h3>
                    <p>Browse the list of all registered students and their assignments.</p>
                    <a class="btn" href="view_students.php">View Students</a>
                </div>

                <div class="dash-card">
                    <h3>Manage Madrasa</h3>
                    <p>Update madrasa profile and system-wide settings.</p>
                    <a class="btn" href="manage_madrasa.php">Manage Madrasa</a>
                </div>
            </div>
            <?php elseif($role == 'Teacher'): ?>
            <div class="cards-grid">
                <div class="dash-card">
                    <h3>Add Student</h3>
                    <p>Register and assign students to your class.</p>
                    <a class="btn" href="../student/student_registration.php">Add Student</a>
                </div>

                <div class="dash-card">
                    <h3>Attendance & Memorization</h3>
                    <p>Record attendance and memorization details for students.</p>
                    <a class="btn" href="../attendence/attendence.php">Attendance</a>
                </div>

                <div class="dash-card">
                    <h3>Exam Entry</h3>
                    <p>Create exams and enter student marks.</p>
                    <a class="btn" href="../teacher/exam_entry.php">Exam Entry</a>
                </div>

                <div class="dash-card">
                    <h3>Reports</h3>
                    <p>View attendance and memorization reports for students.</p>
                    <a class="btn" href="../teacher/reports.php">Reports</a>
                </div>
            </div>
            <?php elseif($role == 'Student'): ?>
            <div class="cards-grid">
                <div class="dash-card">
                    <h3>My Progress</h3>
                    <p>View your attendance and memorization progress.</p>
                    <a class="btn" href="../student/my_progress.php">My Progress</a>
                </div>

                <div class="dash-card">
                    <h3>My Results</h3>
                    <p>See your exam results and scores.</p>
                    <a class="btn" href="../student/view_results.php">My Results</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    (function(){
        var btn = document.getElementById('sidebarToggle');
        var backdrop = document.getElementById('sidebarBackdrop');
        var body = document.body;
        try {
            if(localStorage.getItem('sidebarHidden') === '1') body.classList.add('sidebar-open');
        } catch(e){}

        function toggleSidebar(){
            body.classList.toggle('sidebar-open');
            try { localStorage.setItem('sidebarHidden', body.classList.contains('sidebar-open') ? '1' : '0'); } catch(e){}
        }

        btn.addEventListener('click', toggleSidebar);
        backdrop.addEventListener('click', toggleSidebar);
    })();
    </script>
</body>
</html>
