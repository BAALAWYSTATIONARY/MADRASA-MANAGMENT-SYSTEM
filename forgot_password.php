<?php
session_start();
include 'auth/db.php';

$message = '';
$error = '';

// Load madrasa options
$madrasaOptions = [];
try {
    $stmt = $conn->query("SELECT id, name FROM madrasas ORDER BY name ASC");
    $madrasaOptions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    // ignore; options may be empty
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $madrasa_id = intval($_POST['madrasa_id'] ?? 0);

    if ($username === '') {
        $error = 'Tafadhali ingiza username.';
    } else {
        // find user in that madrasa
        $userStmt = $conn->prepare("SELECT id, username, full_name, madrasa_id FROM users WHERE username = :username AND madrasa_id = :madrasa_id LIMIT 1");
        $userStmt->execute([':username' => $username, ':madrasa_id' => $madrasa_id]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = 'Hakuna mtumiaji kwa username hiyo kwenye madrasa uliyochagua.';
        } else {
            // create token
            $token = bin2hex(random_bytes(16));
            $token_hash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $ins = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, madrasa_id, expires_at) VALUES (:user_id, :token_hash, :madrasa_id, :expires_at)");
            $ins->execute([
                ':user_id' => $user['id'],
                ':token_hash' => $token_hash,
                ':madrasa_id' => $user['madrasa_id'],
                ':expires_at' => $expires
            ]);

            // Build reset link (we cannot send email here)
            $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . 
                         $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/reset_password.php?token=' . $token;

            $message = "Reset link created. Use this link to reset password (expires in 1 hour): <br><a href=\"$resetLink\">$resetLink</a>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background:#f4f7ff; }
        .card { max-width:520px; margin:60px auto; background:#fff; padding:24px; border-radius:12px; box-shadow:0 18px 50px rgba(15,23,42,0.06); }
        .form-group { margin-bottom:14px; }
        input, select { width:100%; padding:12px; border-radius:8px; border:1px solid #cbd5e1; }
        button { background:#2563eb; color:#fff; padding:12px 16px; border:none; border-radius:8px; }
        .message { padding:12px; border-radius:8px; margin-bottom:12px; }
        .success { background:#ecfdf5; color:#166534; }
        .error { background:#fef2f2; color:#991b1b; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Forgot Password</h2>
        <p>Enter your username and select your madrasa to receive a password reset link.</p>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Madrasa</label>
                <select name="madrasa_id">
                    <?php foreach ($madrasaOptions as $id => $name): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Request Reset Link</button>
        </form>

        <p style="margin-top:10px;"><a href="index.php">Back to login</a></p>
    </div>
</body>
</html>
