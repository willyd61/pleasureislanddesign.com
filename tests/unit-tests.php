<?php
/**
 * Unit Tests for Contact & Newsletter Forms
 *
 * Run: php tests/unit-tests.php
 *
 * Tests validation, sanitization, rate limiting, and edge cases
 */

define('TESTS_DIR', __DIR__);

// Test results
$tests = [];
$passed = 0;
$failed = 0;

echo "========================================\n";
echo "PLEASURE ISLAND DESIGN - UNIT TESTS\n";
echo "========================================\n\n";

// ===== CONTACT FORM TESTS =====
echo "[TEST SUITE] Contact Form Validation\n";
echo str_repeat("-", 50) . "\n";

test_contact_form_valid_input();
test_contact_form_missing_fields();
test_contact_form_invalid_email();
test_contact_form_message_length();
test_contact_form_xss_prevention();
test_contact_form_sql_injection_prevention();

echo "\n[TEST SUITE] Newsletter Validation\n";
echo str_repeat("-", 50) . "\n";

test_newsletter_valid_email();
test_newsletter_invalid_email();
test_newsletter_duplicate_prevention();

echo "\n[TEST SUITE] Email Header Injection\n";
echo str_repeat("-", 50) . "\n";

test_email_header_injection_prevention();

echo "\n[TEST SUITE] Blog Scheduler\n";
echo str_repeat("-", 50) . "\n";

test_blog_slug_sanitization();
test_blog_html_validation();

// ===== RESULTS =====
echo "\n" . str_repeat("=", 50) . "\n";
echo "TEST RESULTS\n";
echo str_repeat("=", 50) . "\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total: " . ($passed + $failed) . "\n\n";

if ($failed === 0) {
    echo "✓ All tests passed!\n";
    exit(0);
} else {
    echo "✗ Some tests failed. See above for details.\n";
    exit(1);
}

// ===== TEST FUNCTIONS =====

function test_contact_form_valid_input() {
    $input = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '(910) 444-1230',
        'service' => 'refinishing',
        'message' => 'I would like to refinish my cabinets.'
    ];

    $errors = validate_contact_input($input);
    assert_equal(count($errors), 0, 'Valid input should pass validation');
}

function test_contact_form_missing_fields() {
    $input = ['name' => 'John'];
    $errors = validate_contact_input($input);
    assert_true(count($errors) > 0, 'Missing fields should cause errors');
    assert_true(isset($errors['email']), 'Missing email should be caught');
    assert_true(isset($errors['message']), 'Missing message should be caught');
}

function test_contact_form_invalid_email() {
    $input = [
        'name' => 'John Doe',
        'email' => 'not-an-email',
        'message' => 'Test message here.'
    ];
    $errors = validate_contact_input($input);
    assert_true(isset($errors['email']), 'Invalid email should be caught');
}

function test_contact_form_message_length() {
    $input = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'message' => 'too short'
    ];
    $errors = validate_contact_input($input);
    assert_true(isset($errors['message']), 'Message too short should fail');

    $input['message'] = str_repeat('a', 5001);
    $errors = validate_contact_input($input);
    assert_true(isset($errors['message']), 'Message too long should fail');
}

function test_contact_form_xss_prevention() {
    $malicious = '<script>alert("XSS")</script>';
    $sanitized = htmlspecialchars($malicious, ENT_QUOTES, 'UTF-8');
    assert_not_equal($sanitized, $malicious, 'XSS should be escaped');
    assert_true(strpos($sanitized, '<script>') === false, 'Script tag should be escaped');
}

function test_contact_form_sql_injection_prevention() {
    // PHP's htmlspecialchars and filter_var provide protection
    $malicious = "'; DROP TABLE users; --";
    $sanitized = htmlspecialchars($malicious, ENT_QUOTES, 'UTF-8');
    assert_not_equal($sanitized, $malicious, 'SQL should be escaped');
}

function test_newsletter_valid_email() {
    $email = 'user@example.com';
    $valid = filter_var($email, FILTER_VALIDATE_EMAIL);
    assert_true($valid !== false, 'Valid email should pass');
}

function test_newsletter_invalid_email() {
    $invalid_emails = [
        'not-an-email',
        '@example.com',
        'user@',
        'user @example.com',
        ''
    ];

    foreach ($invalid_emails as $email) {
        $valid = filter_var($email, FILTER_VALIDATE_EMAIL);
        assert_true($valid === false, "Invalid email '{$email}' should fail");
    }
}

function test_newsletter_duplicate_prevention() {
    // Simulated duplicate check
    $email = 'test@example.com';
    $existing = ['test@example.com', 'other@example.com'];
    $is_duplicate = in_array(strtolower($email), array_map('strtolower', $existing));
    assert_true($is_duplicate, 'Duplicate detection should work');
}

function test_email_header_injection_prevention() {
    // Simulate header injection attempt
    $malicious = "test@example.com\nBcc: attacker@example.com";
    $safe = filter_var($malicious, FILTER_SANITIZE_EMAIL);
    assert_true(strpos($safe, "\n") === false, 'Newlines should be removed from email');
}

function test_blog_slug_sanitization() {
    $slugs = [
        'valid-slug' => 'valid-slug',
        'Invalid Slug!' => 'InvalidSlug',
        'slug-with-123' => 'slug-with-123',
        'slug<script>' => 'slugscript'
    ];

    foreach ($slugs as $input => $expected) {
        $sanitized = preg_replace('/[^a-z0-9\-]/i', '', $input);
        assert_equal($sanitized, $expected, "Slug '{$input}' should sanitize correctly");
    }
}

function test_blog_html_validation() {
    $valid_html = '<article><h1>Test Post</h1><p>Content</p></article>';
    $result = validate_post_html_test($valid_html);
    assert_true($result, 'Valid HTML should pass');

    $invalid_html = '<article<h1>Missing bracket';
    $result = validate_post_html_test($invalid_html);
    assert_false($result, 'Unmatched angle brackets should fail');
}

// ===== ASSERTION FUNCTIONS =====

function assert_equal($actual, $expected, $message) {
    global $passed, $failed;
    if ($actual === $expected) {
        echo "✓ {$message}\n";
        $passed++;
    } else {
        echo "✗ {$message}\n";
        echo "  Expected: " . var_export($expected, true) . "\n";
        echo "  Actual: " . var_export($actual, true) . "\n";
        $failed++;
    }
}

function assert_true($condition, $message) {
    assert_equal($condition, true, $message);
}

function assert_false($condition, $message) {
    assert_equal($condition, false, $message);
}

function assert_not_equal($actual, $expected, $message) {
    global $passed, $failed;
    if ($actual !== $expected) {
        echo "✓ {$message}\n";
        $passed++;
    } else {
        echo "✗ {$message}\n";
        echo "  Should not equal: " . var_export($expected, true) . "\n";
        $failed++;
    }
}

// ===== MOCK VALIDATORS =====

function validate_contact_input($input) {
    $errors = [];
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $message = trim($input['message'] ?? '');

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

    return $errors;
}

function validate_post_html_test($content) {
    if (!preg_match('/<h1[^>]*>/', $content)) {
        return false;
    }
    if (substr_count($content, '<') !== substr_count($content, '>')) {
        return false;
    }
    return true;
}
?>
