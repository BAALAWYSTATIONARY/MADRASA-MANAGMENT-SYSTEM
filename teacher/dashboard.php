<?php
session_start();
include '../auth/db.php';

// Only teachers can access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Teacher') {
    header("Location: ../index.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Fetch students for this madrasa
$students = [];
$total_students = 0;
$studentQuery = "SELECT id, full_name, student_class, parent_contact FROM students";
$params = [];
$columnStmt = $conn->query("SHOW COLUMNS FROM students LIKE 'madrasa_id'");
if ($columnStmt && $columnStmt->rowCount() > 0) {
    $studentQuery .= " WHERE madrasa_id = :madrasa_id";
    $params[':madrasa_id'] = $_SESSION['madrasa_id'];
}
$studentQuery .= " ORDER BY full_name ASC";
$stmt = $conn->prepare($studentQuery);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_students = count($students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Madrasa Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f3f5; display: flex; min-height: 100vh; }

        .sidebar { width: 250px; background: #0b3a70; color: white; padding: 30px 20px; position: fixed; left: 0; top: 0; min-height: 100vh; overflow-y: auto; transition: transform 0.25s ease; }
        .sidebar h2 { margin-bottom: 26px; font-size: 16px; font-weight: 600; color: #fff; }
        .sidebar-nav { list-style: none; }
        .sidebar-nav li { margin: 16px 0; }
        .sidebar-nav a { color: #dfe9f3; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 6px; transition: all 0.2s; font-size: 14px; }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.12); color: #fff; }

        main { margin-left: 250px; flex: 1; padding: 36px; }
        h2 { color: #0b3a70; margin-bottom: 24px; font-weight: 700; font-size: 28px; }

        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { border-radius: 12px; padding: 24px; color: white; box-shadow: 0 8px 20px rgba(0,0,0,0.12); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-4px); }
        .stat-card.students { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card h3 { font-size: 14px; margin-bottom: 12px; font-weight: 600; opacity: 0.95; }
        .stat-card .value { font-size: 42px; font-weight: 700; }
        .stat-card .label { font-size: 12px; margin-top: 10px; opacity: 0.88; }

        .content-card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 6px 18px rgba(16,24,40,0.06); border: 1px solid rgba(16,24,40,0.04); margin-bottom: 20px; }
        .content-card h3 { color: #0b3a70; margin-bottom: 20px; font-size: 18px; font-weight: 700; }

        .btn-section { display: flex; gap: 12px; margin-bottom: 20px; }
        .btn { padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-block; transition: all 0.2s; border: none; cursor: pointer; }
        .btn-primary { background: #667eea; color: #fff; }
        .btn-primary:hover { background: #5568d3; }

        table { width: 100%; border-collapse: collapse; }
        table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        table th { padding: 14px; text-align: left; font-weight: 600; font-size: 13px; }
        table td { padding: 14px; border-bottom: 1px solid #e0e0e0; font-size: 13px; }
        table tbody tr:hover { background: #f9f9f9; }

        .btn-edit { background: #0052cc; color: #fff; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 12px; display: inline-block; }
        .btn-edit:hover { background: #003d99; }
        .btn-delete { background: #dc3545; color: #fff; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 12px; display: inline-block; margin-left: 6px; }
        .btn-delete:hover { background: #c82333; }

        #sidebarToggle { position: fixed; left: 16px; top: 16px; z-index: 9999; background: #0b3a70; color: #fff; border: none; padding: 10px 12px; border-radius: 6px; cursor: pointer; box-shadow: 0 6px 18px rgba(11,58,112,0.25); font-size: 18px; transition: all 0.2s; }
        #sidebarToggle:hover { background: #053a52; }
        .sidebar-hidden .sidebar { transform: translateX(-100%); }
        .sidebar-hidden main { margin-left: 0; }

        .no-data { color: #999; font-style: italic; padding: 20px; text-align: center; background: #f9f9f9; border-radius: 8px; }
        @media (max-width: 900px) { .cards-grid { grid-template-columns: 1fr; } .sidebar { position: relative; width: 100%; min-height: auto; } main { margin-left: 0; padding: 20px; } }
    </style>
</head>
<body>
    <button id="sidebarToggle" aria-label="Toggle sidebar">☰</button>

    <div class="sidebar">
        <h2><?php echo htmlspecialchars($username); ?></h2>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="../student/student_registration.php">➕ Add Student</a></li>
            <li><a href="../attendence/attendence.php">📍 Attendance & Memorization</a></li>
            <li><a href="exam_entry.php">📝 Exam Entry</a></li>
            <li><a href="reports.php">📊 Reports</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <main>
        <h2>👨‍🏫 Teacher Dashboard</h2>

        <?php if (isset($_GET['msg'])): ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                ✅ <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                ❌ <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="cards-grid">
            <div class="stat-card students">
                <h3>👥 My Students</h3>
                <div class="value"><?php echo $total_students; ?></div>
                <div class="label">Total Students</div>
            </div>
        </div>

        <div class="content-card">
            <h3>📚 My Students List</h3>
            <div class="btn-section">
                <a href="../student/student_registration.php" class="btn btn-primary">➕ Add New Student</a>
            </div>

            <?php if (count($students) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Parent Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['student_class']); ?></td>
                            <td><?php echo htmlspecialchars($student['parent_contact'] ?? '—'); ?></td>
                            <td>
                                <a class="btn-edit" href="student_detail.php?id=<?php echo $student['id']; ?>">View Details</a>
                                <a class="btn-delete" href="delete_student.php?id=<?php echo $student['id']; ?>" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">📭 No students assigned yet. Add one to get started!</p>
            <?php endif; ?>
        </div>
    </main>

    <script>
    (function(){
        var btn = document.getElementById('sidebarToggle');
        var body = document.body;
        try { if(localStorage.getItem('sidebarHidden') === '1') body.classList.add('sidebar-hidden'); } catch(e){}
        btn.addEventListener('click', function(){
            body.classList.toggle('sidebar-hidden');
            try { localStorage.setItem('sidebarHidden', body.classList.contains('sidebar-hidden') ? '1' : '0'); } catch(e){}
        });
    })();
    </script>
</body>
</html>
