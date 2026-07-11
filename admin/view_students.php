<?php
session_start();
include '../auth/db.php';

// Only admin can access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

// Fetch all students for this madrasa
$stmt = $conn->prepare("SELECT id, full_name, student_class, parent_contact FROM students WHERE madrasa_id = :madrasa_id ORDER BY full_name ASC");
$stmt->execute([':madrasa_id' => $_SESSION['madrasa_id']]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View Students - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; display: flex; min-height: 100vh; }

        .sidebar { width: 250px; background: #072a52; color: #fff; padding: 30px 20px; position: fixed; left: 0; top: 0; min-height: 100vh; overflow-y: auto; }
        .sidebar h2 { margin-bottom: 26px; font-size: 16px; font-weight: 600; }
        .sidebar-nav { list-style: none; }
        .sidebar-nav li { margin: 14px 0; }
        .sidebar-nav a { color: #dfe9f3; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 6px; font-size: 14px; transition: all 0.2s; }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.06); color: #fff; }

        main { margin-left: 250px; flex: 1; padding: 36px; }
        h1 { color: #0b2350; margin-bottom: 24px; font-size: 26px; }

        .table-wrapper { background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 6px 18px rgba(16,24,40,0.06); overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }
        table thead { background: #0052cc; color: #fff; }
        table th { padding: 12px; text-align: left; font-weight: 600; font-size: 13px; }
        table td { padding: 12px; border-bottom: 1px solid #e0e0e0; font-size: 13px; }
        table tbody tr:hover { background: #f9f9f9; }

        .btn { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 12px; display: inline-block; }
        .btn-view { background: #198754; color: #fff; margin-right: 6px; }
        .btn-view:hover { background: #157347; }
        .btn-edit { background: #0052cc; color: #fff; }
        .btn-edit:hover { background: #003d99; }
        .btn-delete { background: #dc3545; color: #fff; margin-left: 6px; }
        .btn-delete:hover { background: #c82333; }

        @media (max-width: 900px) {
            .sidebar { position: relative; width: 100%; min-height: auto; }
            main { margin-left: 0; padding: 20px; }
            .table-wrapper { padding: 12px; overflow-x: scroll; }
        }

        /* Sidebar toggle support for this page */
        #sidebarToggle { position: fixed; left: 16px; top: 16px; z-index: 9999; background: #093a6b; color: #fff; border: none; padding: 8px 10px; border-radius: 6px; cursor: pointer; box-shadow: 0 6px 18px rgba(3,24,80,0.18); }
        .sidebar { transition: transform 0.25s ease; }
        .sidebar-hidden .sidebar { transform: translateX(-100%); }
        .sidebar-hidden main { margin-left: 0 !important; }
    </style>
</head>
<body>
    <button id="sidebarToggle" aria-label="Toggle sidebar">☰</button>
    <div class="sidebar">
        <h2><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="teacher_registration.php">➕ Add Teacher</a></li>
            <li><a href="../student/student_registration.php">➕ Add Student</a></li>
            <li><a href="view_students.php">👥 View Students</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <main>
        <h1>List of Registered Students</h1>
        
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Parent Name</th>
                        <th>Teacher</th>
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
                        <td>—</td>
                        <td>
                            <a class="btn btn-view" href="../teacher/student_detail.php?id=<?php echo $student['id']; ?>">View</a>
                            <a class="btn btn-edit" href="edit_student.php?id=<?php echo $student['id']; ?>">Edit</a>
                            <a class="btn btn-delete" href="delete_student.php?id=<?php echo $student['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
