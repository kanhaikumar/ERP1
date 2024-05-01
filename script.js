function takeAttendance() {
    var studentID = document.getElementById('studentID').value;
    var attendanceDate = document.getElementById('attendanceDate').value;

    // Validate inputs
    if (studentID === '' || attendanceDate === '') {
        alert('Please fill in all fields');
        return;
    }

    // Send data to the server using AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'process_attendance.php', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById('attendanceResult').innerHTML = xhr.responseText;
        }
    };

    var data = 'studentID=' + encodeURIComponent(studentID) + '&attendanceDate=' + encodeURIComponent(attendanceDate);
    xhr.send(data);
}
