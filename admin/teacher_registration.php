<?php
session_start();
include '../auth/db.php';

// Only admin can access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

$message = '';

$editUser = null;
// If editing an existing teacher, load data
if (isset($_GET['edit_id'])) {
    $editId = intval($_GET['edit_id']);
    if ($editId > 0) {
        $eStmt = $conn->prepare("SELECT id, username, full_name, dob, phone, photo FROM users WHERE id = :id AND role = 'Teacher' AND madrasa_id = :madrasa_id LIMIT 1");
        $eStmt->execute([':id' => $editId, ':madrasa_id' => $_SESSION['madrasa_id']]);
        $editUser = $eStmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = intval($_POST['edit_id'] ?? 0);
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $dob = $_POST['dob'] ?? null;
    $phone = trim($_POST['phone'] ?? '');

    // handle photo upload
    $photoPath = null;
    if (!empty($_FILES['photo']['name'])) {
        $uploadDir = __DIR__ . '/../assets/img/teachers';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $safe = preg_replace('/[^a-z0-9_-]/i', '_', pathinfo($_FILES['photo']['name'], PATHINFO_FILENAME));
        $newName = $safe . '_' . time() . '.' . $ext;
        $dest = $uploadDir . '/' . $newName;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $photoPath = 'assets/img/teachers/' . $newName;
        }
    }

    if ($full_name === '' || $username === '') {
        $message = 'Please fill required fields.';
    } else {
        try {
            if ($edit_id > 0) {
                // update existing
                $fields = [
                    ':username' => $username,
                    ':full_name' => $full_name,
                    ':dob' => $dob ?: null,
                    ':phone' => $phone ?: null,
                    ':id' => $edit_id,
                    ':madrasa_id' => $_SESSION['madrasa_id']
                ];
                $sql = "UPDATE users SET username = :username, full_name = :full_name, dob = :dob, phone = :phone";
                if ($photoPath !== null) {
                    $sql .= ", photo = :photo";
                    $fields[':photo'] = $photoPath;
                }
                if (!empty($password)) {
                    $sql .= ", password = :password";
                    $fields[':password'] = md5($password);
                }
                $sql .= " WHERE id = :id AND madrasa_id = :madrasa_id";
                $upd = $conn->prepare($sql);
                $ok = $upd->execute($fields);
                if ($ok) $message = 'Teacher updated successfully.'; else { $err = $upd->errorInfo(); $message = 'Error: ' . ($err[2] ?? 'Could not update'); }
            } else {
                // insert new
                $pwd = md5($password);
                $ins = $conn->prepare("INSERT INTO users (username, password, full_name, role, madrasa_id, dob, phone, photo) VALUES (:username, :password, :full_name, 'Teacher', :madrasa_id, :dob, :phone, :photo)");
                $ok = $ins->execute([
                    ':username' => $username,
                    ':password' => $pwd,
                    ':full_name' => $full_name,
                    ':madrasa_id' => $_SESSION['madrasa_id'],
                    ':dob' => $dob ?: null,
                    ':phone' => $phone ?: null,
                    ':photo' => $photoPath
                ]);
                if ($ok) $message = 'Teacher account created successfully.'; else { $err = $ins->errorInfo(); $message = 'Error: ' . ($err[2] ?? 'Could not create teacher'); }
            }
        } catch (PDOException $e) {
            $message = 'Error: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Add Teacher - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container { margin-left: 270px; padding: 30px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 6px 18px rgba(16,24,40,0.06); max-width: 600px; }
        .form-group { margin-bottom: 12px; }
        label { display:block; margin-bottom:6px; }
        input { width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; }
        .btn { background:#0033cc; color:#fff; padding:10px 14px; border-radius:6px; border:none; cursor:pointer; }
        .message { margin-bottom:12px; }
    </style>
</head>
<body>
    <div style="position:fixed; left:0; top:0; width:250px;">
        <div style="background:#092a52; color:#fff; padding:30px 20px; min-height:100vh;">
            <h2 style="font-size:16px; margin-bottom:26px;"><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
            <ul style="list-style:none; padding:0;">
                <li style="margin:12px 0;"><a href="dashboard.php" style="color:#dfe9f3; text-decoration:none;">📊 Dashboard</a></li>
                <li style="margin:12px 0;"><a href="teacher_registration.php" style="color:#dfe9f3; text-decoration:none;">➕ Add Teacher</a></li>
                <li style="margin:12px 0;"><a href="../student/student_registration.php" style="color:#dfe9f3; text-decoration:none;">➕ Add Student</a></li>
                <li style="margin:12px 0;"><a href="../logout.php" style="color:#dfe9f3; text-decoration:none;">🚪 Logout</a></li>
            </ul>
        </div>
    </div>
<div class="container">
    <div class="card">
        <h2>Add New Teacher</h2>
        <?php if($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <?php if (!empty($editUser)): ?>
                <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($editUser['id']); ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required value="<?php echo htmlspecialchars($editUser['full_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="dob" value="<?php echo htmlspecialchars($editUser['dob'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($editUser['phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Photo (optional)</label>
                <input type="file" name="photo" accept="image/*">
                <?php if (!empty($editUser['photo'])): ?>
                    <div style="margin-top:8px;"><img src="../<?php echo htmlspecialchars($editUser['photo']); ?>" style="height:72px;border-radius:6px;" alt="photo"></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required value="<?php echo htmlspecialchars($editUser['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Password <?php if (!empty($editUser)) echo '(leave blank to keep current)'; ?></label>
                <input type="password" name="password" <?php echo empty($editUser) ? 'required' : ''; ?>>
            </div>
            <button class="btn" type="submit"><?php echo empty($editUser) ? 'Create Teacher' : 'Update Teacher'; ?></button>
        </form>
    </div>
</div>
</body>
</html>
