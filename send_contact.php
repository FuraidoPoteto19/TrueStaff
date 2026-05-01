<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "<h1>Invalid request.</h1>";
    exit;
}

$name = htmlspecialchars(trim($_POST['name'] ?? ''));
$email = htmlspecialchars(trim($_POST['email'] ?? ''));
$phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
$message = trim(htmlspecialchars($_POST['message'] ?? ''));
$help = trim(htmlspecialchars($_POST['help'] ?? ''));
$comments = trim(htmlspecialchars($_POST['comments'] ?? ''));

$to = "hello@truestaffphmgt.com";
$fromAddress = "no-reply@truestaffphmgt.com";
$replyTo = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : $fromAddress;
$subject = "New Contact Message from $name";

if ($message !== '') {
    $body = "Name: $name\nEmail: $email\nPhone: $phone\nMessage: $message";
} else {
    $body = "Name: $name\nEmail: $email\nPhone: $phone\nHow can we help?: $help\nOther Comments: $comments";
}

$headers = [];
$headers[] = "From: True Staff Philippines <$fromAddress>";
$headers[] = "Reply-To: $replyTo";
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: text/plain; charset=UTF-8";

$sent = mail($to, $subject, $body, implode("\r\n", $headers), "-f{$fromAddress}");

header('Content-Type: application/json');
if ($sent) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully! Thank you for contacting us. We\'ll get back to you soon.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Unable to send your message at this time. Please try again later.']);
}
?>