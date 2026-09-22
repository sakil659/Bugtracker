<?php
// smtp_mailer.example.php - copy this to smtp_mailer.php and fill in your own Gmail
// This example file is safe to push to GitHub (no real password inside).

function send_email($to_email, $subject, $body) {

    // ---- YOUR GMAIL SETTINGS ----
    $smtp_host = "smtp.gmail.com";
    $smtp_port = 465; // secure SSL port
    $smtp_username = "your-gmail@gmail.com"; // your Gmail address (sender)
    $smtp_password = "your-16-letter-app-password"; // Gmail App Password, NOT your login password

    // Step 1: Open a secure connection to Gmail's mail server
    $socket = fsockopen("ssl://" . $smtp_host, $smtp_port, $errno, $errstr, 15);

    if (!$socket) {
        return false; // connection failed
    }

    // Helper function: send a command and read the server's reply
    function talk($socket, $command) {
        if ($command != "") {
            fwrite($socket, $command . "\r\n");
        }
        $response = "";
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == " ") break; // server finished replying
        }
        return $response;
    }

    // Step 2: Read the server's welcome message
    talk($socket, "");

    // Step 3: Say hello to the server
    talk($socket, "EHLO localhost");

    // Step 4: Start login authentication
    talk($socket, "AUTH LOGIN");

    // Step 5: Send username and password, encoded in base64 (SMTP requires this)
    talk($socket, base64_encode($smtp_username));
    talk($socket, base64_encode($smtp_password));

    // Step 6: Tell the server who the email is from
    talk($socket, "MAIL FROM: <$smtp_username>");

    // Step 7: Tell the server who the email is going to
    talk($socket, "RCPT TO: <$to_email>");

    // Step 8: Start sending the actual email content
    talk($socket, "DATA");

    // Step 9: Build the email headers + body
    $message = "Subject: $subject\r\n";
    $message .= "From: BugTracker <$smtp_username>\r\n";
    $message .= "To: <$to_email>\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "\r\n";
    $message .= $body;
    $message .= "\r\n.";

    talk($socket, $message);

    // Step 10: Close the connection politely
    talk($socket, "QUIT");
    fclose($socket);

    return true;
}
?>
