<?php
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database connection
$conn = new mysqli("localhost", "root", "", "edoc");

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Get current time
$current_time = date("Y-m-d H:i:s");

// Query to find upcoming appointments
$query = "
    SELECT a.appoid, a.appodate, s.scheduletime, p.pemail, p.pname 
    FROM appointment a
    JOIN patient p ON a.pid = p.pid
    JOIN schedule s ON a.scheduleid = s.scheduleid
    WHERE TIMESTAMP(a.appodate, s.scheduletime) > '$current_time'";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $appointment_time = $row['appodate'] . " " . $row['scheduletime'];
        $appointment_timestamp = strtotime($appointment_time);
        $time_left = ($appointment_timestamp - time()) / 3600; // Convert seconds to hours

        if (in_array(round($time_left), [26, 12, 2])) {
            sendEmailReminder($row['pemail'], $row['pname'], $time_left);
        }
    }
}

$conn->close();

// Function to send email
function sendEmailReminder($email, $name, $hours_left)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'saravananm22mss034@skasc.ac.in'; // Change to your email
        $mail->Password   = 'saravananm0515'; // Use an app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('saravananm22mss034@skasc.ac.in', 'E-Doc System');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "Appointment Reminder";
        $mail->Body    = "Hello $name, <br><br> You have an appointment in <b>$hours_left hours</b>. Please be on time.<br><br> Regards,<br>E-Doc Team";

        $mail->send();
        echo "✅ Reminder sent to $email for appointment in $hours_left hours.<br>";
    } catch (Exception $e) {
        echo "❌ Could not send email to $email. Error: {$mail->ErrorInfo}<br>";
    }
}
?>
