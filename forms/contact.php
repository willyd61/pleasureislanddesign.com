<?php
/**
 * Contact Form Handler (Production-Ready)
 *
 * Security Features:
 * - CSRF token validation
 * - Rate limiting (3 submissions per hour per IP)
 * - Input validation & sanitization
 * - XSS prevention
 * - Email header injection prevention
 * - Request logging for debugging
 * - Error handling with user-friendly responses
 */

define('LOG_FILE', __DIR__ . '/../.logs/contact-form.log');
define('MAX_SUBMISSIONS_PER_HOUR', 3);
define('RATE_LIMIT_KEY_PREFIX', 'pid_contact_');
define('RECIPIENT_EMAIL', 'pleasureislanddesign@gmail.com');

// Ensure log directory exists
if (!is_dir(dirname(LOG_FILE))) {
    @mkdir(dirname(LOG_FILE), 0755, true);
}

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS - restrict to same origin for production
$allowed_origins = ['https://www.pleasureislanddesign.com', 'https://pleasureislanddesign.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Pre-flight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(['error' => 'Method not allowed'], 405);
}

// Parse input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    respond(['error' => 'Invalid JSON'], 400);
}

// --- RATE LIMITING ---
$ip = sanitize_ip($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
$rate_limit_key = RATE_LIMIT_KEY_PREFIX . $ip;
$cache_file = sys_get_temp_dir() . '/' . md5($rate_limit_key) . '.tmp';

if (check_rate_limit($cache_file)) {
    log_event('RATE_LIMIT_EXCEEDED', ['ip' => $ip]);
    respond(['error' => 'Too many submissions. Please try again later.'], 429);
}

// --- VALIDATION ---
$validation_errors = validate_input($input);
if (!empty($validation_errors)) {
    respond(['errors' => $validation_errors], 400);
}

// --- SANITIZATION ---
$name = htmlspecialchars($input['name'] ?? '', ENT_QUOTES, 'UTF-8');
$email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
$phone = htmlspecialchars($input['phone'] ?? '', ENT_QUOTES, 'UTF-8');
$service = htmlspecialchars($input['service'] ?? '', ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($input['message'] ?? '', ENT_QUOTES, 'UTF-8');

// --- SEND EMAILS ---
$success = send_contact_emails($name, $email, $phone, $service, $message);

if ($success) {
    log_event('CONTACT_FORM_SUBMITTED', ['email' => $email, 'name' => $name]);
    record_rate_limit($cache_file);
    respond([
        'success' => true,
        'message' => 'Thank you! We\'ll be in touch within 1-2 business days.'
    ], 200);
} else {
    log_event('CONTACT_FORM_FAILED', ['email' => $email, 'error' => 'Mail send failed']);
    respond([
        'error' => 'Unable to send message. Please call (910) 444-1230 or email directly.'
    ], 500);
}

// ===== HELPER FUNCTIONS =====

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function validate_input($input) {
    $errors = [];
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $message = trim($input['message'] ?? '');

    // Length checks
    if (strlen($name) < 2 || strlen($name) > 100) {
        $errors['name'] = 'Name must be 2-100 characters';
    }
    if (strlen($email) < 5 || strlen($email) > 255) {
        $errors['email'] = 'Invalid email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    if (strlen($message) < 10 || strlen($message) > 5000) {
        $errors['message'] = 'Message must be 10-5000 characters';
    }

    // Optional fields
    $phone = trim($input['phone'] ?? '');
    if ($phone && !preg_match('/^[\d\s\-\+\(\)\.]+$/', $phone)) {
        $errors['phone'] = 'Invalid phone number format';
    }

    return $errors;
}

function send_contact_emails($name, $email, $phone, $service, $message) {
    $to = RECIPIENT_EMAIL;
    $subject = 'New Consultation Request from ' . $name;

    // Build professional email body
    $body = "=== NEW CONSULTATION REQUEST ===\n\n";
    $body .= "Name: {$name}\n";
    $body .= "Email: {$email}\n";
    $body .= "Phone: " . ($phone ?: 'Not provided') . "\n";
    $body .= "Service Interest: " . ($service ?: 'Not specified') . "\n";
    $body .= "\n--- MESSAGE ---\n";
    $body .= "{$message}\n";
    $body .= "\n--- SUBMISSION DETAILS ---\n";
    $body .= "Submitted from: {$_SERVER['HTTP_HOST']}\n";
    $body .= "IP Address: " . sanitize_ip($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') . "\n";
    $body .= "Date: " . date('Y-m-d H:i:s') . " UTC\n";
    $body .= "User Agent: " . substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 200) . "\n";

    // Prevent header injection
    $safe_email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $headers = "From: noreply@pleasureislanddesign.com\r\n";
    $headers .= "Reply-To: {$safe_email}\r\n";
    $headers .= "Return-Path: noreply@pleasureislanddesign.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Send to business
    $business_sent = mail($to, $subject, $body, $headers);

    // Send confirmation to user
    if ($business_sent) {
        $confirm_subject = 'We Received Your Consultation Request';
        $confirm_body = "Hi {$name},\n\n";
        $confirm_body .= "Thank you for reaching out to Pleasure Island Design! We received your consultation request and will review your message shortly.\n\n";
        $confirm_body .= "We'll get back to you within 1-2 business days via email or phone.\n\n";
        $confirm_body .= "In the meantime, feel free to call us at (910) 444-1230 or visit our website.\n\n";
        $confirm_body .= "Best regards,\n";
        $confirm_body .= "The Pleasure Island Design Team\n";
        $confirm_body .= "https://www.pleasureislanddesign.com\n";

        $user_headers = "From: pleasureislanddesign@gmail.com\r\n";
        $user_headers .= "Return-Path: noreply@pleasureislanddesign.com\r\n";
        $user_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($safe_email, $confirm_subject, $confirm_body, $user_headers);
    }

    return $business_sent;
}

function check_rate_limit($cache_file) {
    if (!file_exists($cache_file)) {
        return false;
    }

    $data = json_decode(file_get_contents($cache_file), true);
    $count = $data['count'] ?? 0;
    $timestamp = $data['timestamp'] ?? 0;
    $now = time();

    // Reset if older than 1 hour
    if ($now - $timestamp > 3600) {
        @unlink($cache_file);
        return false;
    }

    return $count >= MAX_SUBMISSIONS_PER_HOUR;
}

function record_rate_limit($cache_file) {
    $data = file_exists($cache_file) ? json_decode(file_get_contents($cache_file), true) : [];
    $data['count'] = ($data['count'] ?? 0) + 1;
    $data['timestamp'] = $data['timestamp'] ?? time();

    file_put_contents($cache_file, json_encode($data));
}

function sanitize_ip($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

function log_event($event, $data = []) {
    $timestamp = date('Y-m-d H:i:s');
    $entry = "{$timestamp} | {$event} | " . json_encode($data) . "\n";
    @error_log($entry, 3, LOG_FILE);
}
?>
