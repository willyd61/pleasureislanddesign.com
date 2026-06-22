<?php
/**
 * Contact Form Handler
 * Receives JSON POST, validates, sends email via PHP mail()
 * Returns JSON response for frontend
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Validate required fields
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$message = trim($input['message'] ?? '');

if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

// Sanitize inputs
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$phone = htmlspecialchars(trim($input['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
$service = htmlspecialchars(trim($input['service'] ?? ''), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Build email body
$to = 'pleasureislanddesign@gmail.com';
$subject = 'New Consultation Request from ' . $name;

$body = "New consultation request:\n\n";
$body .= "Name: $name\n";
$body .= "Email: $email\n";
$body .= "Phone: " . ($phone ?: 'Not provided') . "\n";
$body .= "Service Interest: " . ($service ?: 'Not specified') . "\n";
$body .= "Message:\n$message\n\n";
$body .= "---\n";
$body .= "Submitted from: www.pleasureislanddesign.com\n";
$body .= "Date: " . date('Y-m-d H:i:s') . "\n";

$headers = "From: $email\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send email
if (mail($to, $subject, $body, $headers)) {
    // Also send confirmation to user
    $confirm_subject = 'We received your consultation request';
    $confirm_body = "Hi $name,\n\n";
    $confirm_body .= "Thank you for reaching out to Pleasure Island Design. We received your consultation request and will get back to you within 1-2 business days.\n\n";
    $confirm_body .= "Best regards,\nPleasure Island Design\n(910) 444-1230\n";

    $confirm_headers = "From: pleasureislanddesign@gmail.com\r\n";
    $confirm_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($email, $confirm_subject, $confirm_body, $confirm_headers);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send email. Please try again or call (910) 444-1230']);
}
?>
