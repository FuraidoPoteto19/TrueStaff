<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "<h1>Invalid request.</h1>";
    exit;
}

$name = htmlspecialchars($_POST['name']);
$email = htmlspecialchars($_POST['email']);
$phone = htmlspecialchars($_POST['phone']);
$linkedin = htmlspecialchars($_POST['linkedin']);
$message = htmlspecialchars($_POST['message']);
$position = htmlspecialchars($_POST['position']);

// Handle uploaded resume and send it as an attachment
$resume = $_FILES['resume'] ?? null;
if (!$resume || $resume['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Resume file upload failed. Please try again.']);
    exit;
}
$resumeName = basename($resume['name']);
$resumeType = mime_content_type($resume['tmp_name']) ?: 'application/octet-stream';
$resumeData = chunk_split(base64_encode(file_get_contents($resume['tmp_name'])));

$to = "hello@truestaffphmgt.com";
$fromAddress = "janssengeyrozaga213@gmail.com";
$replyTo = $email;
$subject = "New Job Application from $name for $position";
$body = "Name: $name\nEmail: $email\nPhone: $phone\nLinkedIn: $linkedin\nPosition: $position\nMessage:\n$message";

$smtpHost = 'smtp.gmail.com';
$smtpPort = 465;
$smtpUser = 'janssengeyrozaga213@gmail.com';
$smtpPass = 'xrpe gzev thqf eeqb';

$result = smtp_send_email($smtpHost, $smtpPort, $smtpUser, $smtpPass, $fromAddress, $replyTo, $to, $subject, $body, $resumeName, $resumeType, $resumeData);

header('Content-Type: application/json');
if ($result === true) {
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully! Thank you for applying. We\'ll review your application within 3-5 business days.']);
} else {
    echo json_encode(['success' => false, 'message' => $result]);
}

function smtp_send_email($host, $port, $user, $pass, $from, $replyTo, $to, $subject, $body, $attachmentName, $attachmentType, $attachmentData)
{
    $socket = stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 30);
    if (!$socket) {
        return "Connection failed: $errstr ($errno)";
    }

    $response = smtp_get_response($socket);
    if (substr($response, 0, 3) !== '220') {
        fclose($socket);
        return "SMTP connect failed: $response";
    }

    $result = smtp_send_command($socket, "EHLO localhost", ['250']);
    if (!$result['ok']) { fclose($socket); return $result['response']; }

    $result = smtp_send_command($socket, "AUTH LOGIN", ['334']);
    if (!$result['ok']) { fclose($socket); return $result['response']; }

    $result = smtp_send_command($socket, base64_encode($user), ['334']);
    if (!$result['ok']) { fclose($socket); return $result['response']; }

    $result = smtp_send_command($socket, base64_encode($pass), ['235']);
    if (!$result['ok']) { fclose($socket); return $result['response']; }

    $result = smtp_send_command($socket, "MAIL FROM: <$from>", ['250']);
    if (!$result['ok']) { fclose($socket); return $result['response']; }

    $result = smtp_send_command($socket, "RCPT TO: <$to>", ['250', '251']);
    if (!$result['ok']) { fclose($socket); return $result['response']; }

    $result = smtp_send_command($socket, "DATA", ['354']);
    if (!$result['ok']) { fclose($socket); return $result['response']; }

    $boundary = md5(time());
    $headers = [];
    $headers[] = "From: {$from}";
    $headers[] = "Reply-To: {$replyTo}";
    $headers[] = "To: {$to}";
    $headers[] = "Subject: {$subject}";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";
    $headers[] = "X-Mailer: PHP/" . phpversion();

    $messageBody = "--{$boundary}\r\n";
    $messageBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $messageBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $messageBody .= $body . "\r\n\r\n";

    $messageBody .= "--{$boundary}\r\n";
    $messageBody .= "Content-Type: {$attachmentType}; name=\"{$attachmentName}\"\r\n";
    $messageBody .= "Content-Disposition: attachment; filename=\"{$attachmentName}\"\r\n";
    $messageBody .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $messageBody .= $attachmentData . "\r\n";
    $messageBody .= "--{$boundary}--\r\n";

    $messageData = implode("\r\n", $headers) . "\r\n\r\n" . $messageBody . ".\r\n";
    $result = smtp_send_command($socket, $messageData, ['250']);
    if (!$result['ok']) { fclose($socket); return $result['response']; }

    smtp_send_command($socket, "QUIT", ['221']);
    fclose($socket);
    return true;
}

function smtp_send_command($socket, $command, array $expectedCodes = ['250'])
{
    fwrite($socket, $command . "\r\n");
    $response = smtp_get_response($socket);
    $code = substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        return ['ok' => false, 'response' => "SMTP error for '$command': $response"];
    }
    return ['ok' => true, 'response' => $response];
}

function smtp_get_response($socket)
{
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return trim($response);
}
?>