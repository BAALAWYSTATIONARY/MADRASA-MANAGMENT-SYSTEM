<?php
session_start();
include '../auth/db.php';

// Only students can access
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Student') {
    header("Location: ../index.php");
    exit();
}

// Get student id - try matching by username
$student_username = $_SESSION['username'];
$userStmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE username = :username LIMIT 1");
$userStmt->execute([':username' => $student_username]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

$display_name = $user && !empty($user['full_name']) ? $user['full_name'] : $student_username;
$student = null;
$student_id = null;

if ($user) {
    $stmt = $conn->prepare("SELECT id, full_name FROM students WHERE full_name = :full_name AND madrasa_id = :madrasa_id LIMIT 1");
    $stmt->execute([':full_name' => $display_name, ':madrasa_id' => $_SESSION['madrasa_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_id = $student ? $student['id'] : null;
}

// Fetch results
$resStmt = false;
if ($student_id) {
    $resStmt = $conn->prepare("SELECT e.exam_name, e.exam_date, r.marks_obtained, r.total_marks
                               FROM results r
                               JOIN exams e ON r.exam_id = e.id
                               WHERE r.student_id = :id ORDER BY e.exam_date DESC");
    $resStmt->execute([':id' => $student_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - Madrasa Management System</title>
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

        .content-card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 6px 18px rgba(16,24,40,0.06); border: 1px solid rgba(16,24,40,0.04); margin-bottom: 20px; overflow-x: auto; }
        .content-card h3 { color: #0b3a70; margin-bottom: 20px; font-size: 18px; font-weight: 700; }

        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        table th { padding: 14px; text-align: left; font-weight: 600; font-size: 13px; }
        table td { padding: 14px; border-bottom: 1px solid #e0e0e0; font-size: 13px; }
        table tbody tr:hover { background: #f9f9f9; }

        .marks { font-weight: 700; }
        .marks.good { color: #27ae60; }
        .marks.avg { color: #f39c12; }
        .marks.low { color: #e74c3c; }

        #sidebarToggle { position: fixed; left: 16px; top: 16px; z-index: 9999; background: #0b3a70; color: #fff; border: none; padding: 10px 12px; border-radius: 6px; cursor: pointer; box-shadow: 0 6px 18px rgba(11,58,112,0.25); font-size: 18px; transition: all 0.2s; }
        #sidebarToggle:hover { background: #053a52; }
        .sidebar-hidden .sidebar { transform: translateX(-100%); }
        .sidebar-hidden main { margin-left: 0; }

        .no-data { color: #999; font-style: italic; padding: 20px; text-align: center; background: #f9f9f9; border-radius: 8px; }
        footer { text-align: center; padding: 20px; color: #999; font-size: 12px; margin-top: 20px; }
        @media (max-width: 900px) { .sidebar { position: relative; width: 100%; min-height: auto; } main { margin-left: 0; padding: 20px; } table { font-size: 11px; } table th, table td { padding: 10px; } }
    </style>
</head>
<body>
    <button id="sidebarToggle" aria-label="Toggle sidebar">☰</button>

    <div class="sidebar">
        <h2><?php echo htmlspecialchars($display_name); ?></h2>
        <ul class="sidebar-nav">
            <li><a href="my_progress.php">📊 My Progress</a></li>
            <li><a href="view_results.php">📜 My Results</a></li>
            <li><a href="change_password.php">🔐 Account Settings</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <main>
        <h2 style="display:flex; justify-content:space-between; align-items:center; gap:12px;">📜 My Exam Results <a href="../teacher/generate_student_pdf.php?student_id=<?php echo urlencode($student_id); ?>" style="background:#2563eb; color:#fff; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:600;">Download PDF</a></h2>

        <?php if (!$student_id): ?>
        <div class="content-card">
            <p style="color: #dc3545;"><strong>⚠️ Error:</strong> Your student record was not found in the system. Please contact your teacher or administrator.</p>
        </div>
        <?php else: ?>
        <div class="content-card">
            <?php if($resStmt) { $results = $resStmt->fetchAll(PDO::FETCH_ASSOC); 
                  if (count($results) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Exam Name</th>
                            <th>Date</th>
                            <th>Marks Obtained</th>
                            <th>Total Marks</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): 
                            $percentage = ($row['total_marks'] > 0) ? round(($row['marks_obtained'] / $row['total_marks']) * 100) : 0;
                            $marksClass = 'marks ';
                            if ($percentage >= 75) $marksClass .= 'good';
                            elseif ($percentage >= 50) $marksClass .= 'avg';
                            else $marksClass .= 'low';
                            $grade = 'F';
                            if ($percentage >= 80) { $grade = 'A'; }
                            elseif ($percentage >= 65) { $grade = 'B'; }
                            elseif ($percentage >= 45) { $grade = 'C'; }
                            elseif ($percentage >= 30) { $grade = 'D'; }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['exam_date']); ?></td>
                            <td class="<?php echo $marksClass; ?>"><?php echo $row['marks_obtained']; ?></td>
                            <td><?php echo $row['total_marks']; ?></td>
                            <td class="<?php echo $marksClass; ?>"><?php echo $percentage; ?>%</td>
                            <td><strong><?php echo $grade; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">📭 No exam results yet. Keep studying and prepare for upcoming exams!</p>
            <?php endif; } ?>
        </div>
        <?php endif; ?>


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
