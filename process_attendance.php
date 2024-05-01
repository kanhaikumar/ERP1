<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_system');

try {
    $dbh = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
} catch (PDOException $e) {
    exit("Error: " . $e->getMessage());
}

$studentID = $_POST['studentID'];
$attendanceDate = $_POST['attendanceDate'];

$sql = "INSERT INTO attendance (student_id, attendance_date) VALUES (:studentID, :attendanceDate)";
$stmt = $dbh->prepare($sql);

// Bind parameters
$stmt->bindParam(':studentID', $studentID, PDO::PARAM_STR);
$stmt->bindParam(':attendanceDate', $attendanceDate, PDO::PARAM_STR);

try {
    $stmt->execute();
    echo "Attendance recorded successfully";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close the database connection
$dbh = null;
?>
