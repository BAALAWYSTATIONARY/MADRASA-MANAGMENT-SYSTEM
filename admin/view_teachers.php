<?php
session_start();
include '../auth/db.php';

// Only admin can access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

$message = '';
$error = '';

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_teacher'])) {
    $tid = intval($_POST['teacher_id'] ?? 0);
    if ($tid <= 0) {
        $error = 'Invalid teacher id.';
    } else {
        // ensure teacher belongs to same madrasa
        $chk = $conn->prepare("SELECT id FROM users WHERE id = :id AND role = 'Teacher' AND madrasa_id = :madrasa_id LIMIT 1");
        $chk->execute([':id' => $tid, ':madrasa_id' => $_SESSION['madrasa_id']]);
        if ($chk->rowCount() === 0) {
            $error = 'Teacher not found or not in your madrasa.';
        } else {
            $del = $conn->prepare("DELETE FROM users WHERE id = :id AND role = 'Teacher'");
            if ($del->execute([':id' => $tid])) {
                $message = 'Teacher account deleted.';
            } else {
                $err = $del->errorInfo();
                $error = 'Error deleting teacher: ' . ($err[2] ?? 'unknown');
            }
        }
    }
}

// Fetch teachers for this madrasa
$stmt = $conn->prepare("SELECT id, username, full_name, dob, phone, photo FROM users WHERE role = 'Teacher' AND madrasa_id = :madrasa_id ORDER BY full_name ASC");
$stmt->execute([':madrasa_id' => $_SESSION['madrasa_id']]);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View Teachers - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container { max-width: 920px; margin: 40px auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px 14px; border-bottom: 1px solid #e6eef8; text-align: left; }
        th { background: #f1f5f9; }
        .photo-cell { width: 72px; }
        .photo-thumb { width: 56px; height: 56px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0; display: block; }
        .photo-placeholder { width: 56px; height: 56px; border-radius: 10px; border: 1px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; background: #f8fafc; color: #64748b; font-size: 11px; text-align: center; }
        .actions form { display:inline-block; }
        .btn { padding:8px 12px; border-radius:6px; border:none; cursor:pointer; font-weight:700; }
        .btn-delete { background:#dc3545; color:#fff; }
        .btn-view { background:#2563eb; color:#fff; margin-right:8px; }
        .msg { padding:12px; border-radius:8px; margin-bottom:12px; }
        .success { background:#ecfdf5; color:#166534; }
        .error { background:#fef2f2; color:#991b1b; }
    </style>
</head>
<body>
<div class="container">
    <h2>Teachers for your Madrasa</h2>
    <?php if ($message): ?><div class="msg success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <?php if (count($teachers) === 0): ?>
        <p>No teachers found for this madrasa.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Photo</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>DOB</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($teachers as $t): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td class="photo-cell">
                            <?php if (!empty($t['photo']) && file_exists(__DIR__ . '/../' . $t['photo'])): ?>
                                <img class="photo-thumb" src="../<?php echo htmlspecialchars($t['photo']); ?>" alt="Teacher photo">
                            <?php else: ?>
                                <div class="photo-placeholder">No Photo</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($t['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($t['username']); ?></td>
                        <td><?php echo htmlspecialchars($t['dob'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($t['phone'] ?? '—'); ?></td>
                        <td class="actions">
                            <a class="btn btn-view" href="teacher_registration.php?edit_id=<?php echo $t['id']; ?>">Edit</a>
                            <form method="POST" onsubmit="return confirm('Delete this teacher? This cannot be undone.');" style="display:inline;">
                                <input type="hidden" name="teacher_id" value="<?php echo $t['id']; ?>">
                                <button class="btn btn-delete" type="submit" name="delete_teacher">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p style="margin-top:16px;"><a href="dashboard.php">← Back to Admin Dashboard</a></p>
</div>
</body>
</html>
