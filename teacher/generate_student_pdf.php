<?php
session_start();
include '../auth/db.php';

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Teacher','Admin','Student'], true)) {
    header('Location: ../index.php');
    exit();
}

$student_id = intval($_GET['student_id'] ?? 0);
if ($student_id <= 0) {
    exit('Student not found.');
}

$stmt = $conn->prepare("SELECT id, full_name, age, student_class, parent_contact FROM students WHERE id = :id AND madrasa_id = :madrasa_id LIMIT 1");
$stmt->execute([':id' => $student_id, ':madrasa_id' => $_SESSION['madrasa_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    exit('Student not found.');
}

$resultsStmt = $conn->prepare("SELECT e.exam_name, e.exam_date, r.marks_obtained, r.total_marks FROM results r JOIN exams e ON r.exam_id = e.id WHERE r.student_id = :student_id AND e.madrasa_id = :madrasa_id ORDER BY e.exam_date DESC");
$resultsStmt->execute([':student_id' => $student_id, ':madrasa_id' => $_SESSION['madrasa_id']]);
$results = $resultsStmt->fetchAll(PDO::FETCH_ASSOC);

$rows = [];
$totalPercent = 0;
$count = 0;
foreach ($results as $row) {
    $percentage = ($row['total_marks'] > 0) ? round(($row['marks_obtained'] / $row['total_marks']) * 100) : 0;
    $totalPercent += $percentage;
    $count++;
    $grade = 'F';
    if ($percentage >= 80) { $grade = 'A'; }
    elseif ($percentage >= 65) { $grade = 'B'; }
    elseif ($percentage >= 45) { $grade = 'C'; }
    elseif ($percentage >= 30) { $grade = 'D'; }
    $rows[] = [$row['exam_name'], $row['exam_date'], $row['marks_obtained'], $row['total_marks'], $percentage, $grade];
}
$average = $count > 0 ? round($totalPercent / $count) : 0;
$overallGrade = 'F';
if ($average >= 80) { $overallGrade = 'A'; }
elseif ($average >= 65) { $overallGrade = 'B'; }
elseif ($average >= 45) { $overallGrade = 'C'; }
elseif ($average >= 30) { $overallGrade = 'D'; }

$html = '<html><body style="font-family:Arial,sans-serif; padding:24px; color:#111;">
<h2 style="margin-bottom:8px;">Student Examination Report</h2>
<p><strong>Name:</strong> ' . htmlspecialchars($student['full_name']) . '</p>
<p><strong>Class:</strong> ' . htmlspecialchars($student['student_class'] ?? '—') . '</p>
<p><strong>Age:</strong> ' . htmlspecialchars($student['age'] ?? '—') . '</p>
<p><strong>Parent Contact:</strong> ' . htmlspecialchars($student['parent_contact'] ?? '—') . '</p>
<p><strong>Overall Average:</strong> ' . $average . '%</p>
<p><strong>Overall Grade:</strong> ' . $overallGrade . '</p>
<hr />
<table style="width:100%; border-collapse:collapse; margin-top:16px;">
<thead><tr style="background:#f1f5f9;"><th style="border:1px solid #ddd; padding:8px; text-align:left;">Exam</th><th style="border:1px solid #ddd; padding:8px; text-align:left;">Date</th><th style="border:1px solid #ddd; padding:8px; text-align:left;">Marks</th><th style="border:1px solid #ddd; padding:8px; text-align:left;">Total</th><th style="border:1px solid #ddd; padding:8px; text-align:left;">%age</th><th style="border:1px solid #ddd; padding:8px; text-align:left;">Grade</th></tr></thead><tbody>';
foreach ($rows as $row) {
    $html .= '<tr><td style="border:1px solid #ddd; padding:8px;">' . htmlspecialchars($row[0]) . '</td><td style="border:1px solid #ddd; padding:8px;">' . htmlspecialchars($row[1]) . '</td><td style="border:1px solid #ddd; padding:8px;">' . htmlspecialchars($row[2]) . '</td><td style="border:1px solid #ddd; padding:8px;">' . htmlspecialchars($row[3]) . '</td><td style="border:1px solid #ddd; padding:8px;">' . htmlspecialchars($row[4]) . '</td><td style="border:1px solid #ddd; padding:8px;">' . htmlspecialchars($row[5]) . '</td></tr>';
}
$html .= '</tbody></table></body></html>';

header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="student_report_' . $student_id . '.html"');
echo $html;
