<?php
session_start();
include '../auth/db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Student') {
    header("Location: ../index.php");
    exit();
}

$currentUsername = $_SESSION['username'];
$message = '';
$messageType = '';

$userStmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE username = :username AND madrasa_id = :madrasa_id LIMIT 1");
$userStmt->execute([':username' => $currentUsername, ':madrasa_id' => $_SESSION['madrasa_id']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: ../logout.php");
    exit();
}

$displayName = $user['full_name'] ?: $currentUsername;
$displayUsername = $user['username'] ?: $currentUsername;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newUsername = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($currentPassword === '') {
        $message = 'Tafadhali ingiza password ya sasa ili kuthibitisha mabadiliko.';
        $messageType = 'error';
    } elseif ($newUsername === '' || $fullName === '') {
        $message = 'Tafadhali jaza username na jina lako kamili.';
        $messageType = 'error';
    } elseif ($newPassword !== '' && strlen($newPassword) < 4) {
        $message = 'Neno la siri jipya lazima liwe na angalau herufi 4.';
        $messageType = 'error';
    } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
        $message = 'Neno la siri jipya halifanani na uthibitisho.';
        $messageType = 'error';
    } elseif ($newPassword === '' && $confirmPassword !== '') {
        $message = 'Ili kubadilisha password, jaza sehemu zote mbili za password mpya.';
        $messageType = 'error';
    } else {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = :username AND password = :password AND madrasa_id = :madrasa_id LIMIT 1");
        $checkStmt->execute([
            ':username' => $currentUsername,
            ':password' => md5($currentPassword),
            ':madrasa_id' => $_SESSION['madrasa_id']
        ]);

        if ($checkStmt->rowCount() !== 1) {
            $message = 'Neno la siri la sasa si sahihi.';
            $messageType = 'error';
        } else {
            $usernameCheckStmt = $conn->prepare("SELECT id FROM users WHERE username = :username AND id != :id AND madrasa_id = :madrasa_id LIMIT 1");
            $usernameCheckStmt->execute([
                ':username' => $newUsername,
                ':id' => $user['id'],
                ':madrasa_id' => $_SESSION['madrasa_id']
            ]);

            if ($usernameCheckStmt->rowCount() > 0) {
                $message = 'Username hiyo tayari imetumika. Chagua nyingine.';
                $messageType = 'error';
            } else {
                $setParts = [
                    'username = :username',
                    'full_name = :full_name'
                ];
                $params = [
                    ':username' => $newUsername,
                    ':full_name' => $fullName,
                    ':id' => $user['id']
                ];

                if ($newPassword !== '') {
                    $setParts[] = 'password = :password';
                    $params[':password'] = md5($newPassword);
                }

                $updateStmt = $conn->prepare("UPDATE users SET " . implode(', ', $setParts) . " WHERE id = :id");
                $updateStmt->execute($params);

                $studentStmt = $conn->prepare("SELECT id FROM students WHERE (full_name = :current_full_name OR full_name = :old_username OR full_name = :display_name) AND madrasa_id = :madrasa_id LIMIT 1");
                $studentStmt->execute([
                    ':current_full_name' => $user['full_name'],
                    ':old_username' => $currentUsername,
                    ':display_name' => $displayName,
                    ':madrasa_id' => $_SESSION['madrasa_id']
                ]);
                $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

                if ($student) {
                    $studentUpdateStmt = $conn->prepare("UPDATE students SET full_name = :full_name WHERE id = :id");
                    $studentUpdateStmt->execute([
                        ':full_name' => $fullName,
                        ':id' => $student['id']
                    ]);
                }

                $_SESSION['username'] = $newUsername;
                $currentUsername = $newUsername;
                $displayName = $fullName;
                $displayUsername = $newUsername;

                $message = 'Taarifa zako zimehifadhiwa kwa mafanikio.';
                $messageType = 'success';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Madrasa Management System</title>
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

        .content-card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 6px 18px rgba(16,24,40,0.06); border: 1px solid rgba(16,24,40,0.04); max-width: 620px; }
        .content-card h3 { color: #0b3a70; margin-bottom: 16px; font-size: 18px; font-weight: 700; }
        .content-card p { color: #5f6b7a; margin-bottom: 18px; line-height: 1.6; }
        .alert { padding: 12px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .alert.success { background: #e8f7ed; color: #1f6f42; }
        .alert.error { background: #fdecea; color: #b42318; }

        label { display: block; margin-bottom: 8px; color: #344054; font-weight: 600; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px 14px; border: 1px solid #d0d5dd; border-radius: 8px; margin-bottom: 14px; outline: none; }
        input[type="text"]:focus, input[type="password"]:focus { border-color: #0b3a70; box-shadow: 0 0 0 3px rgba(11,58,112,0.08); }
        button { background: #0b3a70; color: white; border: none; border-radius: 8px; padding: 12px 16px; cursor: pointer; font-weight: 600; }
        button:hover { background: #082f56; }
        .hint { font-size: 12px; color: #667085; margin-top: -8px; margin-bottom: 12px; }

        #sidebarToggle { position: fixed; left: 16px; top: 16px; z-index: 9999; background: #0b3a70; color: #fff; border: none; padding: 10px 12px; border-radius: 6px; cursor: pointer; box-shadow: 0 6px 18px rgba(11,58,112,0.25); font-size: 18px; transition: all 0.2s; }
        #sidebarToggle:hover { background: #053a52; }
        .sidebar-hidden .sidebar { transform: translateX(-100%); }
        .sidebar-hidden main { margin-left: 0; }

        @media (max-width: 900px) { .sidebar { position: relative; width: 100%; min-height: auto; } main { margin-left: 0; padding: 20px; } }
    </style>
</head>
<body>
    <button id="sidebarToggle" aria-label="Toggle sidebar">☰</button>

    <div class="sidebar">
        <h2><?php echo htmlspecialchars($displayName); ?></h2>
        <ul class="sidebar-nav">
            <li><a href="my_progress.php">📊 My Progress</a></li>
            <li><a href="view_results.php">📜 My Results</a></li>
            <li><a href="change_password.php">🔐 Account Settings</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <main>
        <h2>🔐 Account Settings</h2>
        <div class="content-card">
            <h3>Update your account details</h3>
            <p>Badilisha username yako, weka jina lako kamili, na kama unataka pia badilisha password yako ya kuingia.</p>
            <?php if ($message !== ''): ?>
                <div class="alert <?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($displayUsername); ?>" required>

                <label for="full_name">Jina Kamili</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($displayName); ?>" required>

                <label for="current_password">Password ya Sasa</label>
                <input type="password" id="current_password" name="current_password" required>

                <label for="new_password">Password Mpya (si lazima)</label>
                <input type="password" id="new_password" name="new_password">
                <div class="hint">Acha tupu kama hutaki kubadilisha password.</div>

                <label for="confirm_password">Thibitisha Password Mpya</label>
                <input type="password" id="confirm_password" name="confirm_password">

                <button type="submit">Save Changes</button>
            </form>
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
