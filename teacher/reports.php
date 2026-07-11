<?php
session_start();
include '../auth/db.php';

// Only teachers or admins can access
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Teacher','Admin'])) {
    header("Location: ../index.php");
    exit();
}

$student_id = $_GET['student_id'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
// New: report type (single or combined) and year for monthly chart
$report_type = $_GET['report_type'] ?? 'single'; // 'single' or 'combined'
$chart_year = intval($_GET['chart_year'] ?? date('Y'));

$studentsStmt = $conn->prepare("SELECT id, full_name FROM students WHERE madrasa_id = :madrasa_id ORDER BY full_name ASC");
$studentsStmt->execute([':madrasa_id' => $_SESSION['madrasa_id']]);
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedStudent = null;
$attendanceSummary = [];
$presentDates = [];
$absentDates = [];

if ($student_id) {
    $selectedStudentStmt = $conn->prepare("SELECT id, full_name FROM students WHERE id = :id AND madrasa_id = :madrasa_id");
    $selectedStudentStmt->execute([':id' => $student_id, ':madrasa_id' => $_SESSION['madrasa_id']]);
    $selectedStudent = $selectedStudentStmt->fetch(PDO::FETCH_ASSOC);

    $dateFilter = '';
    $params = [':student_id' => $student_id];
    if ($start_date) {
        $dateFilter .= ' AND attendance_date >= :start_date';
        $params[':start_date'] = $start_date;
    }
    if ($end_date) {
        $dateFilter .= ' AND attendance_date <= :end_date';
        $params[':end_date'] = $end_date;
    }

    $summaryStmt = $conn->prepare("SELECT status, COUNT(*) AS count FROM attendance WHERE student_id = :student_id" . $dateFilter . " GROUP BY status");
    $summaryStmt->execute($params);
    while ($row = $summaryStmt->fetch(PDO::FETCH_ASSOC)) {
        $attendanceSummary[$row['status']] = $row['count'];
    }

    $datesStmt = $conn->prepare("SELECT attendance_date, status FROM attendance WHERE student_id = :student_id" . $dateFilter . " ORDER BY attendance_date ASC");
    $datesStmt->execute($params);
    while ($row = $datesStmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['status'] === 'Present') {
            $presentDates[] = $row['attendance_date'];
        } else {
            $absentDates[] = $row['attendance_date'];
        }
    }
}

// Combined report: monthly attendance counts for selected year (Present only)
$monthlyLabels = [];
$monthlyCounts = [];
if ($report_type === 'combined') {
    // initialize months
    for ($m = 1; $m <= 12; $m++) {
        $monthlyLabels[] = date('F', mktime(0, 0, 0, $m, 1));
        $monthlyCounts[$m] = 0;
    }

    $monthStmt = $conn->prepare(
        "SELECT MONTH(attendance_date) AS m, COUNT(*) AS cnt
         FROM attendance
         WHERE madrasa_id = :madrasa_id AND status = 'Present' AND YEAR(attendance_date) = :y
         GROUP BY MONTH(attendance_date)"
    );
    $monthStmt->execute([':madrasa_id' => $_SESSION['madrasa_id'], ':y' => $chart_year]);
    while ($r = $monthStmt->fetch(PDO::FETCH_ASSOC)) {
        $m = intval($r['m']);
        $monthlyCounts[$m] = intval($r['cnt']);
    }
}

// Fetch student summary counts
$summaryCounts = [];
foreach ($students as $student) {
    $attStmt = $conn->prepare("SELECT COUNT(*) as present_count FROM attendance WHERE student_id = :id AND madrasa_id = :madrasa_id AND status = 'Present'");
    $attStmt->execute([':id' => $student['id'], ':madrasa_id' => $_SESSION['madrasa_id']]);
    $totalPresent = $attStmt->fetch(PDO::FETCH_ASSOC)['present_count'];

    $memStmt = $conn->prepare("SELECT COUNT(*) as mem_count FROM memorization_records WHERE student_id = :id AND madrasa_id = :madrasa_id");
    $memStmt->execute([':id' => $student['id'], ':madrasa_id' => $_SESSION['madrasa_id']]);
    $totalMem = $memStmt->fetch(PDO::FETCH_ASSOC)['mem_count'];

    $summaryCounts[$student['id']] = [
        'present' => $totalPresent,
        'memorized' => $totalMem,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Madrasa Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; color: #0f172a; margin: 0; }
        header { display: flex; justify-content: space-between; align-items: center; padding: 24px 32px; background: #ffffff; border-bottom: 1px solid #e2e8f0; }
        header h1 { font-size: 22px; }
        header nav a { color: #334155; margin-left: 18px; text-decoration: none; font-weight: 600; }
        header nav a:hover { color: #2563eb; }
        main { max-width: 1120px; margin: 32px auto; padding: 0 20px; }
        .page-title { font-size: 28px; margin-bottom: 18px; }
        .filter-card, .summary-card, .details-card { background: #ffffff; border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 18px 50px rgba(15,23,42,0.08); }
        .filter-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; align-items: end; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { margin-bottom: 8px; font-size: 13px; color: #475569; }
        .filter-group select, .filter-group input { padding: 12px 14px; border-radius: 12px; border: 1px solid #cbd5e1; background: #f8fafc; }
        .filter-actions { display: flex; justify-content: flex-end; }
        .btn { padding: 12px 18px; border-radius: 12px; border: none; cursor: pointer; font-weight: 700; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .stat-box { background: #eff6ff; border-radius: 16px; padding: 18px; text-align: center; }
        .stat-box h3 { font-size: 14px; color: #475569; margin-bottom: 8px; }
        .stat-box p { font-size: 28px; font-weight: 700; color: #0f172a; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 14px; border-bottom: 1px solid #e2e8f0; }
        th { background: #eef2ff; color: #0f172a; }
        .badge-present { color: #166534; font-weight: 700; }
        .badge-absent { color: #b91c1c; font-weight: 700; }
        .date-list { list-style: none; padding: 0; margin: 0; }
        .date-list li { padding: 10px 12px; background: #f8fafc; border-radius: 10px; margin-bottom: 10px; }
        @media (max-width: 960px) { .filter-grid, .details-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<header>
    <h1>Madrasa Management System</h1>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <div class="page-title">Attendance & Memorization Reports</div>

    <div class="filter-card">
        <form method="GET" action="reports.php">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="report_type">Report Type</label>
                    <select name="report_type" id="report_type">
                        <option value="single" <?php echo $report_type === 'single' ? 'selected' : ''; ?>>Single Student</option>
                        <option value="combined" <?php echo $report_type === 'combined' ? 'selected' : ''; ?>>Combined (All Students)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="chart_year">Year (for monthly chart)</label>
                    <select name="chart_year" id="chart_year">
                        <?php for ($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $chart_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <script>
                    // Toggle student selector disabled when combined selected
                    function toggleStudentSelect() {
                        var rt = document.getElementById('report_type').value;
                        document.getElementById('student_id').disabled = (rt === 'combined');
                    }
                    document.addEventListener('DOMContentLoaded', function(){
                        document.getElementById('report_type').addEventListener('change', toggleStudentSelect);
                        toggleStudentSelect();
                    });
                </script>
                <div class="filter-group">
                    <label for="student_id">Choose Student</label>
                    <select name="student_id" id="student_id">
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" <?php echo $student['id'] == $student_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($student['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="start_date">From Date</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="filter-group">
                    <label for="end_date">To Date</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Show Report</button>
                </div>
            </div>
        </form>
    </div>

    <?php if ($student_id && $selectedStudent): ?>
        <div class="summary-card">
            <h2>Attendance Summary for <?php echo htmlspecialchars($selectedStudent['full_name']); ?></h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <h3>Present</h3>
                    <p><?php echo $attendanceSummary['Present'] ?? 0; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Absent</h3>
                    <p><?php echo $attendanceSummary['Absent'] ?? 0; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Late</h3>
                    <p><?php echo $attendanceSummary['Late'] ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="details-card">
            <div class="details-grid">
                <div>
                    <h3>Dates Present</h3>
                    <?php if ($presentDates): ?>
                        <ul class="date-list">
                            <?php foreach ($presentDates as $date): ?>
                                <li><?php echo htmlspecialchars($date); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No present dates found for this selection.</p>
                    <?php endif; ?>
                </div>
                <div>
                    <h3>Dates Absent</h3>
                    <?php if ($absentDates): ?>
                        <ul class="date-list">
                            <?php foreach ($absentDates as $date): ?>
                                <li><?php echo htmlspecialchars($date); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No absent dates found for this selection.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="details-card">
        <h2>Student Totals</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Attendance Count</th>
                    <th>Memorized Records</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; foreach ($students as $student): ?>
                <tr>
                    <td><?php echo $count++; ?></td>
                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                    <td><?php echo $summaryCounts[$student['id']]['present']; ?></td>
                    <td><?php echo $summaryCounts[$student['id']]['memorized']; ?></td>
                    <td><a href="student_detail.php?id=<?php echo $student['id']; ?>" style="color: #2563eb; text-decoration: none; font-weight: 600;">View Profile →</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($report_type === 'combined'): ?>
        <div class="details-card">
            <h2>Combined Attendance - <?php echo htmlspecialchars($chart_year); ?></h2>
            <canvas id="attendanceChart" width="800" height="320"></canvas>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                (function(){
                    var labels = <?php echo json_encode(array_values($monthlyLabels)); ?>;
                    var data = <?php echo json_encode(array_values($monthlyCounts)); ?>;
                    var ctx = document.getElementById('attendanceChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Present count',
                                data: data,
                                backgroundColor: 'rgba(37,99,235,0.7)'
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                })();
            </script>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
