<?php
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Kolkata'); // Change as per your region

// Database connection
$conn = new mysqli("localhost", "root", "", "edoc");

if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}

// Get current timestamp
$current_time = date("Y-m-d H:i:s");

// Get 24 hours from now
$reminder_time = date("Y-m-d H:i:s", strtotime("+24 hours"));

// Fetch appointments within the next 24 hours
$query = "
    SELECT a.appoid, a.appodate, s.scheduletime, p.pemail, p.pname 
    FROM appointment a
    JOIN patient p ON a.pid = p.pid
    JOIN schedule s ON a.scheduleid = s.scheduleid
    WHERE TIMESTAMP(a.appodate, s.scheduletime) BETWEEN '$current_time' AND '$reminder_time'";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $email = $row['pemail'];
        $name = $row['pname'];
        $appodate = $row['appodate'];
        $scheduletime = $row['scheduletime'];

        sendEmailReminder($email, $name, $appodate, $scheduletime);
    }
} else {
    echo "✅ No upcoming appointments within 24 hours.<br>";
}

$conn->close();

// Function to send email reminders
function sendEmailReminder($email, $name, $appodate, $scheduletime)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = ''; // Your email
        $mail->Password   = ''; // Use App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Bypass SSL verification (Temporary fix for OpenSSL issue)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Debug SMTP
        $mail->SMTPDebug = 2; 
        $mail->Debugoutput = 'html';

        $mail->setFrom('sn', 'E-Doc System');
        $mail->addAddress($email, $name);
        $mail->addReplyTo('', 'Support Team');

        $mail->isHTML(true);
        $mail->Subject = "Appointment Reminder";
        $mail->Body    = "Hello $name, <br><br> You have an appointment on <b>$appodate</b> at <b>$scheduletime</b>. Please be on time.<br><br> Regards,<br>E-Doc Team";

        $mail->send();
        echo "✅ Reminder sent to $email for appointment on $appodate at $scheduletime.<br>";
    } catch (Exception $e) {
        echo "❌ Could not send email to $email. Error: {$mail->ErrorInfo}<br>";
    }
}
?>
