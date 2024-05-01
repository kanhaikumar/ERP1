<?php
require_once 'config.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.html');
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch student details
$stmt_user_details = $dbh->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt_user_details->bindParam(':user_id', $student_id, PDO::PARAM_INT);

// Fetch attendance records
$stmt_attendance = $dbh->prepare("SELECT * FROM attendance WHERE user_id = :user_id ORDER BY attendance_date DESC");
$stmt_attendance->bindParam(':user_id', $student_id, PDO::PARAM_INT);

// Fetch notices
$stmt_notices = $dbh->prepare("SELECT * FROM notices ORDER BY created_at DESC");
try {
    if ($stmt_user_details->execute() && $stmt_attendance->execute() && $stmt_notices->execute()) {
        $userDetails = $stmt_user_details->fetch(PDO::FETCH_ASSOC);
        $attendanceRecords = $stmt_attendance->fetchAll(PDO::FETCH_ASSOC);
        $notices = $stmt_notices->fetchAll(PDO::FETCH_ASSOC);

        // Calculate attendance percentage
        $totalDays = count($attendanceRecords);
        $presentDays = array_reduce($attendanceRecords, function ($carry, $record) {
            return $carry + $record['status'];
        }, 0);
        $attendancePercentage = ($totalDays > 0) ? round(($presentDays / $totalDays) * 100, 2) : 0;
    } else {
        throw new PDOException("Error fetching data: " . implode(", ", $stmt_notices->errorInfo()));
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit(); // Stop execution if there's an error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
       .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        /* Modal content */
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
        }

        /* Close button */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #333;
            padding-top: 20px;
        }
        .sidebar a {
            padding: 10px;
            text-decoration: none;
            display: block;
            color: white;
        }
        .sidebar a:hover {
            background-color: #555;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 20px;
        }
        @media screen and (max-width: 600px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .sidebar a {
                text-align: center;
            }
            .content {
                margin-left: 0;
            }
        }
    
    </style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <img src="<?php echo $userDetails['profile_photo']; ?>" alt="Profile Photo" class="profile-photo">
        <a href="#">Edit Profile</a>
        <a href="#" id="attendanceLink">Attendance</a>
        <a href="#" id="noticeLink">Notice</a>
        <a href="logout.php">Logout</a>
        <a href="#">Leave Apply</a>
    </div>

    <div class="content">
        <h2>Welcome, <?php echo $userDetails['username']; ?>!</h2>

        <h3>Personal Details</h3>
        <ul>
            <li><strong>Enrollment Number:</strong> <?php echo $userDetails['enrollment_number']; ?></li>
            <li><strong>Department:</strong> <?php echo $userDetails['department']; ?></li>
            <li><strong>Semester:</strong> <?php echo $userDetails['semester']; ?></li>
            <li><strong>Attendance Percentage:</strong> <?php echo $attendancePercentage; ?>%</li> <!-- Add this line -->
        </ul>

        <h3>Attendance Data</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Attendance Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendanceRecords as $record): ?>
                    <tr>
                        <td><?php echo $record['attendance_date']; ?></td>
                        <td><?php echo $record['status']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="noticeModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Notice</h2>
        <div id="noticeContent"></div>
    </div>
</div>

<script>
    // Function to display attendance records
    function showAttendance() {
        // Get the attendance data
        var attendanceData = <?php echo json_encode($attendanceRecords); ?>;
        var attendanceHtml = '';

        // Generate HTML for attendance table
        if (attendanceData.length > 0) {
            attendanceHtml += '<table>';
            attendanceHtml += '<thead><tr><th>Attendance Date</th><th>Status</th></tr></thead>';
            attendanceHtml += '<tbody>';

            attendanceData.forEach(function (record) {
                attendanceHtml += '<tr>';
                attendanceHtml += '<td>' + record.attendance_date + '</td>';
                attendanceHtml += '<td>' + record.status + '</td>';
                attendanceHtml += '</tr>';
            });

            attendanceHtml += '</tbody></table>';
        } else {
            attendanceHtml = '<p>No attendance records found.</p>';
        }

        // Display attendance HTML in the content area
        document.getElementById('attendanceData').innerHTML = attendanceHtml;
    }

    // Function to show notice modal
    function showNoticeModal() {
        // Get the notices data
        var notices = <?php echo json_encode($notices); ?>;
        var noticeHtml = '';

        // Generate HTML for notices
        if (notices.length > 0) {
            notices.forEach(function(notice) {
                noticeHtml += '<h3>' + notice.title + '</h3>';
                noticeHtml += '<p><strong>Author:</strong> ' + notice.author + '</p>';
                noticeHtml += '<p><strong>Created At:</strong> ' + notice.created_at + '</p>';
                noticeHtml += '<p>' + notice.content + '</p>';
                noticeHtml += '<hr>';
            });
        } else {
            noticeHtml = '<p>No notices found.</p>';
        }

        // Show the notice modal and display notice HTML
        var modal = document.getElementById('noticeModal');
        var contentDiv = document.getElementById('noticeContent');
        contentDiv.innerHTML = noticeHtml;
        modal.style.display = 'block';

        // Close modal on clicking the close button
        var closeButton = document.getElementsByClassName('close')[0];
        closeButton.onclick = function() {
            modal.style.display = 'none';
        }

        // Close modal on clicking outside the modal
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    }

    // Event listener for Attendance link
    document.getElementById('attendanceLink').addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default link behavior
        showAttendance(); // Show attendance data
    });

    // Event listener for Notice link
    document.getElementById('noticeLink').addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default link behavior
        showNoticeModal(); // Show notice modal
    });

    // Your existing JavaScript code for displaying attendance records...
</script>

</body>
</html>
