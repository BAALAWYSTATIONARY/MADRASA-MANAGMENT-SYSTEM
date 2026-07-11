<?php
// Public setup page to create the very first admin if none exist.
require_once __DIR__ . '/../auth/db.php';

// If any admin exists, redirect to login
$adminCheck = $conn->query("SELECT id FROM users WHERE role = 'Admin' LIMIT 1");
if ($adminCheck->rowCount() > 0) {
    header('Location: ../index.php');
    exit();
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $madrasa_name = trim($_POST['madrasa_name'] ?? 'Default Madrasa');

    if ($username === '' || $password === '' || $full_name === '') {
        $error = 'Tafadhali jaza kila sehemu.';
    } else {
        try {
            // create madrasa
            $conn->beginTransaction();
            $stmt = $conn->prepare("INSERT INTO madrasas (name) VALUES (:name)");
            $stmt->execute([':name' => $madrasa_name]);
            $madrasa_id = $conn->lastInsertId();

            // insert admin
            $pwd = md5($password);
            $ins = $conn->prepare("INSERT INTO users (username, password, full_name, role, madrasa_id) VALUES (:username, :password, :full_name, 'Admin', :madrasa_id)");
            $ins->execute([
                ':username' => $username,
                ':password' => $pwd,
                ':full_name' => $full_name,
                ':madrasa_id' => $madrasa_id
            ]);
            $conn->commit();

            $message = 'Admin account created. Now you can log in.';
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = 'Kosa: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Initial Admin Setup</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background:#f4f7ff; }
        .card { max-width:520px; margin:60px auto; background:#fff; padding:24px; border-radius:12px; box-shadow:0 18px 50px rgba(15,23,42,0.06); }
        .form-group { margin-bottom:14px; }
        input { width:100%; padding:12px; border-radius:8px; border:1px solid #cbd5e1; }
        button { background:#2563eb; color:#fff; padding:12px 16px; border:none; border-radius:8px; }
        .message { padding:12px; border-radius:8px; margin-bottom:12px; }
        .success { background:#ecfdf5; color:#166534; }
        .error { background:#fef2f2; color:#991b1b; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Initial Admin Setup</h2>
        <p>This page allows creation of the first admin account and its madrasa. It will be disabled once an admin exists.</p>

        <?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Admin Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Admin Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="form-group">
                <label>Madrasa Name</label>
                <input type="text" name="madrasa_name" value="Default Madrasa">
            </div>
            <button type="submit">Create Admin</button>
        </form>

        <p style="margin-top:12px;"><a href="../index.php">Back to Login</a></p>
    </div>
</body>
</html>