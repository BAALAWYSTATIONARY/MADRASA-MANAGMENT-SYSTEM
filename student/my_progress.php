<?php
session_start();
include '../auth/db.php';

// Only students can access
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Student') {
    header("Location: index.html");
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

// Attendance
if ($student_id) {
    $attStmt = $conn->prepare("SELECT COUNT(*) as present_count FROM attendance WHERE student_id = :id AND status = 'Present'");
    $attStmt->execute([':id' => $student_id]);
    $att_row = $attStmt->fetch(PDO::FETCH_ASSOC);
    $att_count = $att_row ? $att_row['present_count'] : 0;
} else {
    $att_count = 0;
}

// Memorization
$memorized = [];
if ($student_id) {
    $memStmt = $conn->prepare("SELECT surah_name, ayah_start, ayah_end FROM memorization_records WHERE student_id = :id");
    $memStmt->execute([':id' => $student_id]);
    while($row = $memStmt->fetch(PDO::FETCH_ASSOC)){
        $memorized[] = $row['surah_name']."({$row['ayah_start']}-{$row['ayah_end']})";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progress - Madrasa Management System</title>
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

        .cards-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { border-radius: 12px; padding: 24px; color: white; box-shadow: 0 8px 20px rgba(0,0,0,0.12); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-4px); }
        .stat-card.att { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.mem { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card h3 { font-size: 14px; margin-bottom: 12px; font-weight: 600; opacity: 0.95; }
        .stat-card .value { font-size: 42px; font-weight: 700; }
        .stat-card .label { font-size: 12px; margin-top: 10px; opacity: 0.88; }

        .content-card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 6px 18px rgba(16,24,40,0.06); border: 1px solid rgba(16,24,40,0.04); margin-bottom: 20px; }
        .content-card h3 { color: #0b3a70; margin-bottom: 20px; font-size: 18px; font-weight: 700; }
        .surah-list { list-style: none; }
        .surah-list li { padding: 14px; background: #f5f7fa; margin: 10px 0; border-radius: 8px; border-left: 4px solid #4facfe; color: #333; font-weight: 500; }
        .no-data { color: #999; font-style: italic; padding: 20px; text-align: center; background: #f9f9f9; border-radius: 8px; }

        #sidebarToggle { position: fixed; left: 16px; top: 16px; z-index: 9999; background: #0b3a70; color: #fff; border: none; padding: 10px 12px; border-radius: 6px; cursor: pointer; box-shadow: 0 6px 18px rgba(11,58,112,0.25); font-size: 18px; transition: all 0.2s; }
        #sidebarToggle:hover { background: #053a52; }
        .sidebar-hidden .sidebar { transform: translateX(-100%); }
        .sidebar-hidden main { margin-left: 0; }

        footer { text-align: center; padding: 20px; color: #999; font-size: 12px; margin-top: 20px; }
        @media (max-width: 900px) { .cards-grid { grid-template-columns: 1fr; } .sidebar { position: relative; width: 100%; min-height: auto; } main { margin-left: 0; padding: 20px; } }
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
        <h2>📚 My Academic Progress</h2>

        <?php if (!$student_id): ?>
        <div class="content-card">
            <p style="color: #dc3545;"><strong>⚠️ Error:</strong> Your student record was not found in the system. Please contact your teacher or administrator.</p>
        </div>
        <?php else: ?>

        <div class="cards-grid">
            <div class="stat-card att">
                <h3>📍 Attendance Record</h3>
                <div class="value"><?php echo $att_count; ?></div>
                <div class="label">Days Present</div>
            </div>

            <div class="stat-card mem">
                <h3>💚 Memorization Progress</h3>
                <div class="value"><?php echo count($memorized); ?></div>
                <div class="label">Surahs Memorized</div>
            </div>
        </div>

        <div class="content-card">
            <h3>📖 Memorized Surahs</h3>
            <?php if (count($memorized) > 0): ?>
                <ul class="surah-list">
                    <?php foreach ($memorized as $surah): ?>
                    <li><?php echo htmlspecialchars($surah); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-data">No surahs memorized yet. Keep working and stay focused! 💪</p>
            <?php endif; ?>
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
