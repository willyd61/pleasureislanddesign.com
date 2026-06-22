<?php
/**
 * Newsletter Signup Handler
 * Receives JSON POST with email, validates, sends to pleasureislanddesign@gmail.com
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

$email = trim($input['email'] ?? '');

if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

$email = filter_var($email, FILTER_SANITIZE_EMAIL);

// Send newsletter signup notification
$to = 'pleasureislanddesign@gmail.com';
$subject = 'New Newsletter Signup';
$body = "New newsletter subscriber: $email\n";
$body .= "Date: " . date('Y-m-d H:i:s') . "\n";

$headers = "From: noreply@pleasureislanddesign.com\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send confirmation to subscriber
$confirm_subject = 'Welcome to Pleasure Island Design';
$confirm_body = "Hi,\n\n";
$confirm_body .= "Thanks for subscribing! You'll receive our latest cabinet design tips, industry trends, and special promotions directly in your inbox.\n\n";
$confirm_body .= "Best regards,\nPleasure Island Design\n";

$confirm_headers = "From: pleasureislanddesign@gmail.com\r\n";
$confirm_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

if (mail($to, $subject, $body, $headers) && mail($email, $confirm_subject, $confirm_body, $confirm_headers)) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Subscribed successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Subscription failed. Please try again.']);
}
?>
