<?php
session_start();
include '../auth/db.php';

// Only admin can access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

$teacher_id = intval($_GET['id'] ?? 0);
if ($teacher_id <= 0) {
    header('Location: view_teachers.php');
    exit();
}

$stmt = $conn->prepare("SELECT id, username, full_name, dob, phone, photo FROM users WHERE id = :id AND role = 'Teacher' AND madrasa_id = :madrasa_id LIMIT 1");
$stmt->execute([':id' => $teacher_id, ':madrasa_id' => $_SESSION['madrasa_id']]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$teacher) {
    header('Location: view_teachers.php?error=Teacher not found');
    exit();
}

// Fetch students for this madrasa to list under the teacher profile
$studentsStmt = $conn->prepare("SELECT id, full_name, student_class FROM students WHERE madrasa_id = :madrasa_id ORDER BY full_name ASC");
$studentsStmt->execute([':madrasa_id' => $_SESSION['madrasa_id']]);
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Profile - <?php echo htmlspecialchars($teacher['full_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
        .stat-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 20px; color: white; }
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
        .no-data { text-align: center; color: #64748b; padding: 20px; font-style: italic; }
        .avatar-box { display: flex; align-items: center; gap: 18px; }
        .avatar { width: 120px; height: 120px; border-radius: 18px; background: #eef2ff; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-placeholder { color: #475569; font-weight: 700; text-align: center; }
        @media (max-width: 980px) { .info-grid, .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) { .info-grid, .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<header>
    <h1>Teacher Profile</h1>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="view_teachers.php">View Teachers</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>
<main>
    <div class="back-link">
        <a href="view_teachers.php">← Back to Teachers</a>
    </div>
    <div class="page-header">
        <h2><?php echo htmlspecialchars($teacher['full_name']); ?></h2>
        <p>Detailed teacher profile and assigned students</p>
    </div>
    <div class="info-card">
        <div class="avatar-box">
            <div class="avatar">
                <?php if (!empty($teacher['photo']) && file_exists(__DIR__ . '/../' . $teacher['photo'])): ?>
                    <img src="../<?php echo htmlspecialchars($teacher['photo']); ?>" alt="<?php echo htmlspecialchars($teacher['full_name']); ?>">
                <?php else: ?>
                    <div class="avatar-placeholder">No Photo</div>
                <?php endif; ?>
            </div>
            <div style="flex:1;">
                <div style="margin-bottom:18px;">
                    <h2 style="font-size:28px; margin-bottom:6px;"><?php echo htmlspecialchars($teacher['full_name']); ?></h2>
                    <p style="color:#475569; font-size:15px;">Teacher profile with contact details and assigned students</p>
                </div>
                <div class="info-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <div class="info-item">
                        <span class="info-label">Username</span>
                        <span class="info-value"><?php echo htmlspecialchars($teacher['username']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?php echo htmlspecialchars($teacher['phone'] ?? '—'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date of Birth</span>
                        <span class="info-value"><?php echo htmlspecialchars($teacher['dob'] ?? '—'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Teacher ID</span>
                        <span class="info-value"><?php echo htmlspecialchars($teacher['id']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-box green">
            <h3>Assigned Students</h3>
            <p><?php echo count($students); ?></p>
        </div>
        <div class="stat-box blue">
            <h3>Teacher ID</h3>
            <p><?php echo htmlspecialchars($teacher['id']); ?></p>
        </div>
        <div class="stat-box red">
            <h3>Profile Status</h3>
            <p><?php echo !empty($teacher['photo']) ? 'Photo' : 'No Photo'; ?></p>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <h3>Role</h3>
            <p>Teacher</p>
        </div>
    </div>
    <div class="section-card">
        <h3 class="section-title">📋 Assigned Students</h3>
        <?php if (count($students) === 0): ?>
            <p class="no-data">No students assigned to this teacher.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>#</th><th>Student</th><th>Class</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($students as $s): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($s['student_class']); ?></td>
                            <td><a href="../teacher/student_detail.php?id=<?php echo $s['id']; ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
