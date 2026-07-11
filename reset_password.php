<?php
session_start();
include 'auth/db.php';

$message = '';
$error = '';
$showForm = false;
$tokenParam = $_GET['token'] ?? ($_POST['token'] ?? '');

if ($tokenParam === '') {
    $error = 'Token not provided.';
} else {
    // On POST, process password reset
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = trim($_POST['password'] ?? '');
        $confirm = trim($_POST['confirm'] ?? '');
        if ($password === '' || $confirm === '') {
            $error = 'Tafadhali jaza password zote mbili.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords hazifananani.';
        } else {
            $token_hash = hash('sha256', $tokenParam);
            $stmt = $conn->prepare("SELECT id, user_id, madrasa_id, expires_at FROM password_resets WHERE token_hash = :token_hash LIMIT 1");
            $stmt->execute([':token_hash' => $token_hash]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $error = 'Token si sahihi au imetumika.';
            } elseif (strtotime($row['expires_at']) < time()) {
                $error = 'Token imeisha.';
            } else {
                // update user's password
                $update = $conn->prepare("UPDATE users SET password = :password WHERE id = :id AND madrasa_id = :madrasa_id");
                $update->execute([
                    ':password' => md5($password),
                    ':id' => $row['user_id'],
                    ':madrasa_id' => $row['madrasa_id']
                ]);

                // delete all password_resets for user
                $del = $conn->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
                $del->execute([':user_id' => $row['user_id']]);

                $message = 'Password imebadilishwa. Tafadhali ingia kutumia password mpya.';
            }
        }
    } else {
        // GET: validate token and show form
        $token_hash = hash('sha256', $tokenParam);
        $stmt = $conn->prepare("SELECT id, user_id, madrasa_id, expires_at FROM password_resets WHERE token_hash = :token_hash LIMIT 1");
        $stmt->execute([':token_hash' => $token_hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $error = 'Token si sahihi au imetumika.';
        } elseif (strtotime($row['expires_at']) < time()) {
            $error = 'Token imeisha.';
        } else {
            $showForm = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
        <h2>Reset Password</h2>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <p><a href="index.php">Back to login</a></p>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <p><a href="forgot_password.php">Request a new token</a></p>
            <?php elseif ($showForm): ?>
                <form method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($tokenParam); ?>">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm" required>
                    </div>
                    <button type="submit">Set New Password</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
