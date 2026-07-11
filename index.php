<?php
session_start();
include 'auth/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // for now (learning)

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE username = :username AND password = :password"
    );
    $stmt->execute([
        ':username' => $username,
        ':password' => $password
    ]);

    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['madrasa_id'] = $user['madrasa_id'];

        // Redirect based on role
        if (isset($user['role']) && $user['role'] === 'Student') {
            header("Location: student/my_progress.php");
            exit();
        } elseif (isset($user['role']) && $user['role'] === 'Teacher') {
            header("Location: teacher/dashboard.php");
            exit();
        }

        // Default for Admin
        header("Location: admin/dashboard.php");
        exit();
    } else {
        echo "<script>alert('Invalid login'); window.location='index.php';</script>";
    }
}
?>

<?php
// Show initial setup link only if no admin exists
try {
    $adminCheck = $conn->query("SELECT id FROM users WHERE role = 'Admin' LIMIT 1");
    $showSetup = ($adminCheck && $adminCheck->rowCount() == 0);
} catch (Exception $e) {
    $showSetup = false;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Madrasa Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="index-page">
    <header class="site-header">
        <div class="site-header-inner">
            <div class="title">MADRASA MANAGEMENT SYSTEM</div>
            <img src="assets/css/../img/logo.png" alt="logo" class="logo" onerror="this.style.display='none'">
        </div>
    </header>

    <center><div class="welcome-strip">Welcome to MADRASA MANAGEMENT SYSTEM</div></center>

    <div class="page-body">
        <div class="login-panel">

            <div class="login-wrapper">
                <h2>LOGIN</h2>
            <form action="index.php" method="POST">
                <label>Username</label>
                <input type="text" name="username" required>

                <label>Password</label>
                <input type="password" name="password" required>

                <button type="submit" style="margin-top:12px; width:100%; padding:10px; background:#cfcfcf; color:#222; border:1px solid #777;">LOGIN</button>
            </form>
                <div style="margin-top:18px; text-align:center; font-size:14px; color:#333;">
                    <p style="margin:8px 0;"><a href="signup.php" style="color:#0d6efd; text-decoration:none;">Student/Teacher? Sign Up here</a></p>
                    <p style="margin:8px 0;"><a href="#" style="color:#0d6efd; text-decoration:none;">Forgotten login Password? Request New</a></p>
                    <?php if (!empty($showSetup)): ?>
                        <p style="margin:8px 0;"><a href="setup/create_initial_admin.php" style="color:#d63384; font-weight:700; text-decoration:none;">First time? Create initial Admin</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
