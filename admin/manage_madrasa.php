<?php
session_start();
include '../auth/db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

$message = '';
$error = '';

// Fetch current admin user
$userStmt = $conn->prepare("SELECT id, username, madrasa_id FROM users WHERE username = :username LIMIT 1");
$userStmt->execute([':username' => $_SESSION['username']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
// Provide a display name fallback if full_name column is missing
if (!isset($user['full_name'])) {
    $user['full_name'] = $user['username'];
}

// Load existing madrasas
$madrasaStmt = $conn->query("SELECT id, name FROM madrasas ORDER BY name ASC");
$madrasaOptions = $madrasaStmt->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_madrasa'])) {
        $name = trim($_POST['madrasa_name'] ?? '');
        if ($name === '') {
            $error = 'Tafadhali weka jina la madrasa.';
        } else {
            $ins = $conn->prepare("INSERT INTO madrasas (name) VALUES (:name)");
            $ins->execute([':name' => $name]);
            $newId = $conn->lastInsertId();

            // assign to admin
            $upd = $conn->prepare("UPDATE users SET madrasa_id = :madrasa_id WHERE id = :id");
            $upd->execute([':madrasa_id' => $newId, ':id' => $user['id']]);

            $_SESSION['madrasa_id'] = $newId;
            $message = 'Madrasa imeundwa na imewekwa kwenye admin yako.';
            // refresh list
            $madrasaStmt = $conn->query("SELECT id, name FROM madrasas ORDER BY name ASC");
            $madrasaOptions = $madrasaStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }
    }

    if (isset($_POST['select_madrasa'])) {
        $madrasa_id = intval($_POST['madrasa_id'] ?? 0);
        if ($madrasa_id <= 0) {
            $error = 'Chagua madrasa.';
        } else {
            $upd = $conn->prepare("UPDATE users SET madrasa_id = :madrasa_id WHERE id = :id");
            $upd->execute([':madrasa_id' => $madrasa_id, ':id' => $user['id']]);
            $_SESSION['madrasa_id'] = $madrasa_id;
            $message = 'Madrasa imewekwa kwenye admin yako.';
        }
    }

    // Create a new admin/teacher user for a madrasa
    if (isset($_POST['create_user'])) {
        $new_role = ($_POST['new_role'] ?? 'Teacher');
        $new_role = ($new_role === 'Admin') ? 'Admin' : 'Teacher';
        $new_username = trim($_POST['new_username'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $new_full_name = trim($_POST['new_full_name'] ?? '');
        $new_madrasa_id = intval($_POST['new_madrasa_id'] ?? 0);

        if ($new_username === '' || $new_password === '' || $new_full_name === '' || $new_madrasa_id <= 0) {
            $error = 'Tafadhali jaza taarifa zote za kuunda mtumiaji.';
        } else {
            // verify madrasa exists
            if (!array_key_exists($new_madrasa_id, $madrasaOptions)) {
                $error = 'Madrasa uliyochagua haipo.';
            } else {
                // Check username uniqueness globally to match the unique users.username constraint.
                $chk = $conn->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
                $chk->execute([':username' => $new_username]);
                if ($chk->rowCount() > 0) {
                    $error = 'Username tayari imetumika. Chagua jina lingine.';
                } else {
                    $pwdHash = md5($new_password);
                    try {
                        $ins = $conn->prepare("INSERT INTO users (username, password, full_name, role, madrasa_id) VALUES (:username, :password, :full_name, :role, :madrasa_id)");
                        $ins->execute([
                            ':username' => $new_username,
                            ':password' => $pwdHash,
                            ':full_name' => $new_full_name,
                            ':role' => $new_role,
                            ':madrasa_id' => $new_madrasa_id
                        ]);
                        $message = 'Mtumiaji mpya ameundwa kwa mafanikio.';
                    } catch (PDOException $e) {
                        if ($e->getCode() === '23000') {
                            $error = 'Username tayari imetumika. Chagua jina lingine.';
                        } else {
                            $error = 'Haikuwezekana kuunda mtumiaji: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Madrasa - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container { max-width: 760px; margin: 40px auto; padding: 20px; }
        .card { background:#fff; padding:20px; border-radius:12px; box-shadow:0 12px 30px rgba(15,23,42,0.06); }
        .form-group { margin-bottom:12px; }
        input, select { width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1; }
        button { padding:10px 14px; border-radius:8px; background:#2563eb; color:#fff; border:none; }
        .message { padding:12px; border-radius:8px; margin-bottom:12px; }
        .success { background:#ecfdf5; color:#166534; }
        .error { background:#fef2f2; color:#991b1b; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Manage Madrasa</h2>
        <p>Current admin: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <h3>Create New Madrasa</h3>
        <form method="POST">
            <div class="form-group">
                <label>New Madrasa Name</label>
                <input type="text" name="madrasa_name">
            </div>
            <button type="submit" name="create_madrasa">Create and Assign</button>
        </form>

        <hr style="margin:18px 0;">

        <h3>Or Select Existing Madrasa</h3>
        <form method="POST">
            <div class="form-group">
                <label>Select Madrasa</label>
                <select name="madrasa_id">
                    <option value="0">-- Select --</option>
                    <?php foreach ($madrasaOptions as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo (isset($_SESSION['madrasa_id']) && $_SESSION['madrasa_id'] == $id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="select_madrasa">Assign Selected</button>
        </form>

        <hr style="margin:18px 0;">

        <h3>Create Admin / Teacher User</h3>
        <p>Create a login user for this madrasa (Admin or Teacher).</p>
        <form method="POST">
            <div class="form-group">
                <label>Role</label>
                <select name="new_role">
                    <option value="Admin">Admin</option>
                    <option value="Teacher">Teacher</option>
                </select>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="new_username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="new_full_name" required>
            </div>
            <div class="form-group">
                <label>Assign to Madrasa</label>
                <select name="new_madrasa_id">
                    <?php foreach ($madrasaOptions as $id => $name): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="create_user">Create User</button>
        </form>

        <p style="margin-top:12px;"><a href="dashboard.php">← Back to Admin Dashboard</a></p>
    </div>
</div>
</body>
</html>
