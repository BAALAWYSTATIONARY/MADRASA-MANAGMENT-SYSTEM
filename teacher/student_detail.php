<?php
session_start();
include '../auth/db.php';

// Allow both teachers and admins to access student details
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Teacher', 'Admin'], true)) {
    header("Location: ../index.php");
    exit();
}

$student_id = $_GET['id'] ?? null;

if (!$student_id) {
    header("Location: dashboard.php");
    exit();
}

// Fetch student details
$studentStmt = $conn->prepare("SELECT * FROM students WHERE id = :id AND madrasa_id = :madrasa_id");
$studentStmt->execute([':id' => $student_id, ':madrasa_id' => $_SESSION['madrasa_id']]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    $redirect = ($_SESSION['role'] === 'Admin') ? '../admin/dashboard.php' : 'dashboard.php';
    header("Location: $redirect?error=Student not found");
    exit();
}

// Fetch attendance records
$attStmt = $conn->prepare("SELECT * FROM attendance WHERE student_id = :id AND madrasa_id = :madrasa_id ORDER BY attendance_date DESC");
$attStmt->execute([':id' => $student_id, ':madrasa_id' => $_SESSION['madrasa_id']]);
$attendance = $attStmt->fetchAll(PDO::FETCH_ASSOC);

$attSummary = [
    'Present' => 0,
    'Absent' => 0,
    'Late' => 0,
];
foreach ($attendance as $record) {
    if (isset($attSummary[$record['status']])) {
        $attSummary[$record['status']]++;
    }
}

// Fetch memorization records
$memStmt = $conn->prepare("SELECT * FROM memorization_records WHERE student_id = :id AND madrasa_id = :madrasa_id ORDER BY memorization_date DESC LIMIT 20");
$memStmt->execute([':id' => $student_id, ':madrasa_id' => $_SESSION['madrasa_id']]);
$memorization = $memStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch exam results
$examStmt = $conn->prepare("SELECT e.exam_name, e.exam_date, r.marks_obtained, r.total_marks FROM results r JOIN exams e ON r.exam_id = e.id WHERE r.student_id = :id AND e.madrasa_id = :madrasa_id ORDER BY e.exam_date DESC");
$examStmt->execute([':id' => $student_id, ':madrasa_id' => $_SESSION['madrasa_id']]);
$results = $examStmt->fetchAll(PDO::FETCH_ASSOC);

$examAverage = 0;
if (count($results) > 0) {
    $totalPercentage = 0;
    foreach ($results as $result) {
        if ($result['total_marks'] > 0) {
            $totalPercentage += ($result['marks_obtained'] / $result['total_marks']) * 100;
        }
    }
    $examAverage = round($totalPercentage / count($results));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Detail - <?php echo htmlspecialchars($student['full_name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; color: #0f172a; }
        header { display: flex; justify-content: space-between; align-items: center; padding: 24px 32px; background: #ffffff; border-bottom: 1px solid #e2e8f0; }
        header h1 { font-size: 22px; }
        header nav a { color: #334155; margin-left: 18px; text-decoration: none; font-weight: 600; }
        header nav a:hover { color: #2563eb; }
        main { max-width: 1200px; margin: 32px auto; padding: 0 20px; }
        .back-link { margin-bottom: 20px; }
        .back-link a { color: #2563eb; text-decoration: none; font-weight: 600; }
        .back-link a:hover { text-decoration: underline; }
        .page-header { margin-bottom: 28px; }
        .page-header h2 { font-size: 32px; margin-bottom: 8px; }
        .page-header p { color: #64748b; font-size: 15px; }

        .info-card, .section-card { background: #ffffff; border-radius: 16px; padding: 24px; margin-bottom: 24px; box-shadow: 0 12px 40px rgba(15,23,42,0.08); }
        .info-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
        .info-item { display: flex; flex-direction: column; }
        .info-label { font-size: 13px; color: #64748b; margin-bottom: 6px; font-weight: 600; }
        .info-value { font-size: 18px; color: #0f172a; font-weight: 700; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); Border-radius: 16px; padding: 20px; color: white; }
        .stat-box.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-box.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .stat-box.blue { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
        .stat-box h3 { font-size: 13px; opacity: 0.9; margin-bottom: 8px; }
        .stat-box p { font-size: 28px; font-weight: 700; }

        .section-title { font-size: 20px; font-weight: 700; margin-bottom: 16px; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; }
        table thead { background: #f1f5f9; }
        table th { padding: 12px 14px; text-align: left; font-weight: 600; font-size: 13px; border-bottom: 2px solid #e2e8f0; }
        table td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        table tbody tr:hover { background: #f8fafc; }

        .status-present { color: #166534; font-weight: 700; }
        .status-absent { color: #b91c1c; font-weight: 700; }
        .status-late { color: #b45309; font-weight: 700; }

        .no-data { text-align: center; color: #64748b; padding: 20px; font-style: italic; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fee2e2; color: #7f1d1d; }
        .badge-blue { background: #dbeafe; color: #1e40af; }

        @media (max-width: 960px) { .info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>
</head>
<body>
<header>
    <h1>Student Profile</h1>
    <nav>
        <a href="<?php echo ($_SESSION['role'] === 'Admin') ? '../admin/dashboard.php' : 'dashboard.php'; ?>">Dashboard</a>
        <?php if ($_SESSION['role'] === 'Teacher'): ?>
            <a href="reports.php">Reports</a>
        <?php endif; ?>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<main>
    <div class="back-link">
        <a href="<?php echo ($_SESSION['role'] === 'Admin') ? '../admin/view_students.php' : 'dashboard.php'; ?>">← Back</a>
    </div>

    <div class="page-header">
        <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
        <p>Detailed student profile and academic records</p>
    </div>

    <!-- Student Info Card -->
    <div class="info-card">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Full Name</span>
                <span class="info-value"><?php echo htmlspecialchars($student['full_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Age</span>
                <span class="info-value"><?php echo $student['age']; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Class</span>
                <span class="info-value"><?php echo htmlspecialchars($student['student_class']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Parent Contact</span>
                <span class="info-value"><?php echo htmlspecialchars($student['parent_contact'] ?? '—'); ?></span>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-box green">
            <h3>Attendance - Present</h3>
            <p><?php echo $attSummary['Present']; ?></p>
        </div>
        <div class="stat-box red">
            <h3>Attendance - Absent</h3>
            <p><?php echo $attSummary['Absent']; ?></p>
        </div>
        <div class="stat-box blue">
            <h3>Memorization Records</h3>
            <p><?php echo count($memorization); ?></p>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <h3>Average Exam Score</h3>
            <p><?php echo $examAverage; ?>%</p>
        </div>
    </div>

    <!-- Attendance Details -->
    <div class="section-card">
        <h3 class="section-title">📍 Attendance History</h3>
        <?php if (count($attendance) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['attendance_date']); ?></td>
                            <td>
                                <span class="status-<?php echo strtolower($record['status']); ?>">
                                    <?php echo htmlspecialchars($record['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">No attendance records found</p>
        <?php endif; ?>
    </div>

    <!-- Memorization Records -->
    <div class="section-card">
        <h3 class="section-title">📖 Memorization Progress</h3>
        <?php if (count($memorization) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Surah</th>
                        <th>Ayah Range</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($memorization as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['memorization_date']); ?></td>
                            <td><span class="badge badge-blue"><?php echo htmlspecialchars($record['surah_name']); ?></span></td>
                            <td><?php echo $record['ayah_start']; ?> - <?php echo $record['ayah_end']; ?></td>
                            <td><?php echo htmlspecialchars($record['teacher_notes'] ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">No memorization records found</p>
        <?php endif; ?>
    </div>

    <!-- Exam Results -->
    <div class="section-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px;">
            <h3 class="section-title" style="margin-bottom: 0;">📊 Exam Results</h3>
            <a href="generate_student_pdf.php?student_id=<?php echo urlencode($student['id']); ?>" style="background:#2563eb; color:#fff; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:600;">Download PDF</a>
        </div>
        <?php if (count($results) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Exam Name</th>
                        <th>Date</th>
                        <th>Marks</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <?php
                            $percentage = ($result['total_marks'] > 0) ? round(($result['marks_obtained'] / $result['total_marks']) * 100) : 0;
                            $gradeBadge = 'badge-red';
                            $grade = 'F';
                            if ($percentage >= 80) { $grade = 'A'; $gradeBadge = 'badge-green'; }
                            elseif ($percentage >= 65) { $grade = 'B'; $gradeBadge = 'badge-blue'; }
                            elseif ($percentage >= 45) { $grade = 'C'; $gradeBadge = 'badge-blue'; }
                            elseif ($percentage >= 30) { $grade = 'D'; $gradeBadge = 'badge-blue'; }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                            <td><?php echo htmlspecialchars($result['exam_date']); ?></td>
                            <td><?php echo $result['marks_obtained']; ?> / <?php echo $result['total_marks']; ?></td>
                            <td><strong><?php echo $percentage; ?>%</strong></td>
                            <td><span class="badge <?php echo $gradeBadge; ?>"><?php echo $grade; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">No exam results found</p>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
