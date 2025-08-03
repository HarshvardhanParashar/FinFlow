<?php
session_start(); // Start session (though user_id not strictly needed for contact form)
include 'db_connect.php'; // Include database connection

// REMOVE these error reporting lines once it's working in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // --- Validation ---
    if (empty($fullName) || empty($email) || empty($subject) || empty($message)) {
        header("Location: contact.php?status=invalid");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: contact.php?status=invalid");
        exit();
    }

    // --- Store Message in Database ---
    $stmt = $conn->prepare("INSERT INTO contact_messages (full_name, email, subject, message) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Contact DB Prepare Error: " . $conn->error);
        header("Location: contact.php?status=error");
        exit();
    }

    $stmt->bind_param("ssss", $fullName, $email, $subject, $message);

    if ($stmt->execute()) {
        // Database insertion successful

        // --- Send Email Notification ---
        $to = "finflow31@gmail.com"; // <--- IMPORTANT: CHANGE THIS TO YOUR ACTUAL EMAIL ADDRESS
        $email_subject = "New FinFlow Contact Message: " . $subject;
        $email_body = "You have received a new message from the FinFlow contact form.\n\n";
        $email_body .= "Name: " . $fullName . "\n";
        $email_body .= "Email: " . $email . "\n";
        $email_body .= "Subject: " . $subject . "\n";
        $email_body .= "Message:\n" . $message . "\n";

        $headers = "From: no-reply@finflow31.gmail.com\r\n"; // <--- IMPORTANT: Change this to a valid sender email if needed
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // The mail() function requires a mail server (like Postfix, Sendmail, or configured SMTP in php.ini)
        // If mail() doesn't work locally, it's a server configuration issue, not a PHP code issue.
        $mail_sent = mail($to, $email_subject, $email_body, $headers);

        if ($mail_sent) {
            header("Location: contact.php?status=success");
            exit();
        } else {
            // Mail failed to send, but message was saved to DB.
            // You might log this error and redirect with a partial success or a warning.
            error_log("Failed to send contact email to " . $to . " from " . $email);
            header("Location: contact.php?status=success_no_email"); // Custom status for mail failure
            exit();
        }

    } else {
        // Database insertion failed
        error_log("Contact DB Insert Error: " . $stmt->error);
        header("Location: contact.php?status=error");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    // Not a POST request
    header("Location: contact.php");
    exit();
}
?>