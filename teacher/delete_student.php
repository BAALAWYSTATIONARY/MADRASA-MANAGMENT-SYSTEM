<?php
session_start();
include '../auth/db.php';

// Only teachers can access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Teacher') {
    header("Location: ../index.php");
    exit();
}

$student_id = $_GET['id'] ?? null;

if (!$student_id) {
    header("Location: dashboard.php?error=Invalid student");
    exit();
}

try {
    // First delete related records for same madrasa
    $conn->prepare("DELETE FROM attendance WHERE student_id = :id AND madrasa_id = :madrasa_id")->execute([':id' => $student_id, ':madrasa_id' => $_SESSION['madrasa_id']]);
    $conn->prepare("DELETE FROM memorization_records WHERE student_id = :id AND madrasa_id = :madrasa_id")->execute([':id' => $student_id, ':madrasa_id' => $_SESSION['madrasa_id']]);
    $conn->prepare("DELETE FROM results WHERE student_id = :id")->execute([':id' => $student_id]);
    
    // Then delete the student
    $stmt = $conn->prepare("DELETE FROM students WHERE id = :id AND madrasa_id = :madrasa_id");
    $result = $stmt->execute([':id' => $student_id, ':madrasa_id' => $_SESSION['madrasa_id']]);

    if ($result) {
        header("Location: dashboard.php?msg=Student deleted successfully");
    } else {
        header("Location: dashboard.php?error=Failed to delete student");
    }
} catch (Exception $e) {
    header("Location: dashboard.php?error=Error: " . urlencode($e->getMessage()));
}
exit();
?>
