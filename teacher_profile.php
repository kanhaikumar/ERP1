<?php
require_once 'config.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.html');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Fetch student details (modify as needed based on your database structure)
$stmt_students = $dbh->prepare("SELECT * FROM users WHERE role = 'student'");
try {
    $stmt_students->execute();
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendanceDate = $_POST['attendanceDate'];

    foreach ($students as $student) {
        $studentID = $student['id'];
        $attendanceStatus = isset($_POST['attendanceStatus'][$studentID]) ? 1 : 0;

        // Insert or update attendance data (modify as needed based on your database structure)
        $stmt_attendance = $dbh->prepare("INSERT INTO attendance (user_id, attendance_date, status)
                                         VALUES (:user_id, :attendance_date, :status)
                                         ON DUPLICATE KEY UPDATE status = :status");

        $stmt_attendance->bindParam(':user_id', $studentID, PDO::PARAM_INT);
        $stmt_attendance->bindParam(':attendance_date', $attendanceDate, PDO::PARAM_STR);
        $stmt_attendance->bindParam(':status', $attendanceStatus, PDO::PARAM_INT);

        try {
            $stmt_attendance->execute();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // Redirect to avoid resubmission on page refresh
    header('Location: teacher_profile.php');
    exit();
}

// Fetch attendance data for each student
foreach ($students as $student) {
    $studentID = $student['id'];

    $stmt_attendance = $dbh->prepare("SELECT * FROM attendance WHERE user_id = :user_id");
    $stmt_attendance->bindParam(':user_id', $studentID, PDO::PARAM_INT);

    try {
        $stmt_attendance->execute();
        $attendanceData[$studentID] = $stmt_attendance->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Profile</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">CollageERP</a>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </nav>
        <div class="welcome-box">
            <span class="welcome-text">Welcome, Teacher!</span>
        </div>

        <div class="container-md">
            
            <form action="teacher_profile.php" method="POST">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-3">
                        <button type="button" class="btn btn-primary" id="togglemarkattendance">Mark Attendance</button>
                        <div class="card ">
                            <img src="img/Attendence.png" class="card-img-top" alt="...">
                            <div class="card-body" style="display: none;" id="markattendance">
                                <h5 class="card-title">Attendance</h5>
                                <p class="card-text">Mark attendance for each student:</p>
                                <label for="attendanceDate">Date:</label>
                                <input type="date" id="attendanceDate" name="attendanceDate" required>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                            <th>Enrollment Number</th>
                                            <th>Mark Attendance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo $student['id']; ?></td>
                                            <td><?php echo $student['username']; ?></td>
                                            <td><?php echo $student['enrollment_number']; ?></td>
                                            <td>
                                                <input type="checkbox" id="attendanceStatus_<?php echo $student['id']; ?>"
                                                    name="attendanceStatus[<?php echo $student['id']; ?>]" value="1">
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <button type="submit" class="btn btn-primary">Submit Attendance</button>
                                <!-- Button to toggle attendance details -->
                            </div>
                            <button type="button" class="btn btn-primary" id="toggleAttendanceDetails">Show Attendance Details</button>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card">
                            <img src="img/Mark.jpeg" class="card-img-top" alt="...">
                            <div class="card-body">
                              
                                <a href="#" class="btn btn-primary">Enter Marks</a>
                            </div>
                        </div>
                    </div>
                    <!-- Card for TimeTable -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card">
                            <img src="img/Time table.jpeg" class="card-img-top" alt="...">
                            <div class="card-body">
                                
                                <a href="#" class="btn btn-primary">View TimeTable</a>
                            </div>
                        </div>
                    </div>
                    <!-- Card for Notice -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card">
                            <img src="img/Report.png" class="card-img-top" alt="...">
                            <div class="card-body">
                                
                                <a href="write_notice.php" class="btn btn-primary">Write Notice</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Attendance Details Section -->
            <div id="attendanceDetails" style="display: none;">
                <h3>Attendance Details</h3>
                <?php foreach ($students as $student): ?>
                <div>
                    <strong>Student Name:</strong> <?php echo $student['username']; ?><br>
                    <strong>Enrollment Number:</strong> <?php echo $student['enrollment_number']; ?><br>
                    <strong>Student ID:</strong> <?php echo $student['id']; ?><br>
                    <strong>Attendance Percentage:</strong>
                    <?php
                    $totalDays = count($attendanceData[$student['id']]);
                    $presentDays = array_reduce($attendanceData[$student['id']], function ($carry, $attendance) {
                        return $carry + $attendance['status'];
                    }, 0);
                    $percentage = ($totalDays > 0) ? round(($presentDays / $totalDays) * 100, 2) : 0;
                    echo "$percentage%";
                    ?>
                </div>
                <hr>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("toggleAttendanceDetails").addEventListener("click", function () {
            var attendanceDetails = document.getElementById("attendanceDetails");
            if (attendanceDetails.style.display === "none") {
                attendanceDetails.style.display = "block";
            } else {
                attendanceDetails.style.display = "none";
            }
        });
        document.getElementById("togglemarkattendance").addEventListener("click", function () {
            var attendanceDetails = document.getElementById("markattendance");
            if (attendanceDetails.style.display === "none") {
                attendanceDetails.style.display = "block";
            } else {
                attendanceDetails.style.display = "none";
            }
        });
    </script>
</body>

</html>
