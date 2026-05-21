<?php
// Include PHPMailer library files
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database connection
$conn = new mysqli("localhost", "root", "", "edoc"); // Update credentials if needed

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get upcoming appointments within 26, 12, or 2 hours
$query = "SELECT a.appoid, a.appodate, p.pemail, p.pname 
          FROM appointment a
          JOIN patient p ON a.pid = p.pid
          WHERE a.appodate >= NOW() 
          AND (
             TIMESTAMPDIFF(HOUR, NOW(), a.appodate) = 26 
             OR TIMESTAMPDIFF(HOUR, NOW(), a.appodate) = 12
             OR TIMESTAMPDIFF(HOUR, NOW(), a.appodate) = 2
          )";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mail = new PHPMailer(true);
        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'saravananm22mss034@skasc.ac.in'; // Your email
            $mail->Password = 'vupa lann bzvh xlhb'; // Use an App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Sender & Recipient
            $mail->setFrom('saravananm22mss034@skasc.ac.in', 'E-Doc Reminder');
            $mail->addAddress($row['pemail'], $row['pname']);

            // Email Content
            $mail->isHTML(true);
            $mail->Subject = "Appointment Reminder";
            $mail->Body = "Hello " . $row['pname'] . ",<br><br>
                           This is a reminder that your appointment is scheduled on " . $row['appodate'] . ".<br>
                           Please make sure to be available.<br><br>
                           Regards, <br> E-Doc Team";

            // Send email
            $mail->send();
            echo "✅ Reminder sent to " . $row['pemail'] . "<br>";
        } catch (Exception $e) {
            echo "❌ Email could not be sent. Mailer Error: {$mail->ErrorInfo}<br>";
        }
    }
} else {
    echo "No reminders to send.";
}

$conn->close();
?>
