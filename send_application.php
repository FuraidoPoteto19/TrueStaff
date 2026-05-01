<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "<h1>Invalid request.</h1>";
    exit;
}

$name = htmlspecialchars(trim($_POST['name'] ?? ''));
$email = htmlspecialchars(trim($_POST['email'] ?? ''));
$phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
$linkedin = htmlspecialchars(trim($_POST['linkedin'] ?? ''));
$message = htmlspecialchars(trim($_POST['message'] ?? ''));
$position = htmlspecialchars(trim($_POST['position'] ?? ''));

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
$fromAddress = "no-reply@truestaffphmgt.com";
$replyTo = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : $fromAddress;
$subject = "New Job Application from $name for $position";
$body = "Name: $name\nEmail: $email\nPhone: $phone\nLinkedIn: $linkedin\nPosition: $position\nMessage:\n$message";

$boundary = md5(uniqid('', true));
$headers = [];
$headers[] = "From: True Staff Philippines <$fromAddress>";
$headers[] = "Reply-To: $replyTo";
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

$messageBody = "--{$boundary}\r\n";
$messageBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
$messageBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$messageBody .= $body . "\r\n\r\n";

$messageBody .= "--{$boundary}\r\n";
$messageBody .= "Content-Type: {$resumeType}; name=\"{$resumeName}\"\r\n";
$messageBody .= "Content-Disposition: attachment; filename=\"{$resumeName}\"\r\n";
$messageBody .= "Content-Transfer-Encoding: base64\r\n\r\n";
$messageBody .= $resumeData . "\r\n";
$messageBody .= "--{$boundary}--\r\n";

$sent = mail($to, $subject, $messageBody, implode("\r\n", $headers), "-f{$fromAddress}");

header('Content-Type: application/json');
if ($sent) {
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully! Thank you for applying. We\'ll review your application within 3-5 business days.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Unable to send your application at this time. Please try again later.']);
}
?>