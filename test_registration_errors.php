<?php
/**
 * Test file to demonstrate improved error handling in the registration system
 * This file shows how the new error handling works with specific error messages
 */

require_once __DIR__ . '/config/supabase.php';
require_once __DIR__ . '/public/includes/logger.php';
require_once __DIR__ . '/public/includes/validation.php';
require_once __DIR__ . '/public/includes/user.php';

echo "<h1>Registration Error Handling Test</h1>\n";
echo "<p>This test demonstrates the improved error handling in the registration system.</p>\n\n";

// Test 1: Invalid email
echo "<h2>Test 1: Invalid Email</h2>\n";
$result = create_user_with_security_question(
    'invalid-email',
    'ValidPassword123!',
    'customer',
    'What was your first pet\'s name?',
    'Fluffy'
);
echo "<strong>Input:</strong> invalid-email<br>\n";
echo "<strong>Result:</strong> " . ($result['success'] ? 'Success' : 'Failed') . "<br>\n";
if (!$result['success']) {
    echo "<strong>Error Code:</strong> " . $result['error_code'] . "<br>\n";
    echo "<strong>Message:</strong> " . $result['message'] . "<br>\n";
}
echo "<br>\n";

// Test 2: Weak password
echo "<h2>Test 2: Weak Password</h2>\n";
$result = create_user_with_security_question(
    'test@example.com',
    'weak',
    'customer',
    'What was your first pet\'s name?',
    'Fluffy'
);
echo "<strong>Input:</strong> weak<br>\n";
echo "<strong>Result:</strong> " . ($result['success'] ? 'Success' : 'Failed') . "<br>\n";
if (!$result['success']) {
    echo "<strong>Error Code:</strong> " . $result['error_code'] . "<br>\n";
    echo "<strong>Message:</strong> " . $result['message'] . "<br>\n";
}
echo "<br>\n";

// Test 3: Short security question
echo "<h2>Test 3: Short Security Question</h2>\n";
$result = create_user_with_security_question(
    'test@example.com',
    'ValidPassword123!',
    'customer',
    'Short?',
    'Fluffy'
);
echo "<strong>Input:</strong> Short?<br>\n";
echo "<strong>Result:</strong> " . ($result['success'] ? 'Success' : 'Failed') . "<br>\n";
if (!$result['success']) {
    echo "<strong>Error Code:</strong> " . $result['error_code'] . "<br>\n";
    echo "<strong>Message:</strong> " . $result['message'] . "<br>\n";
}
echo "<br>\n";

// Test 4: Weak security answer
echo "<h2>Test 4: Weak Security Answer</h2>\n";
$result = create_user_with_security_question(
    'test@example.com',
    'ValidPassword123!',
    'customer',
    'What was your first pet\'s name?',
    'yes'
);
echo "<strong>Input:</strong> yes<br>\n";
echo "<strong>Result:</strong> " . ($result['success'] ? 'Success' : 'Failed') . "<br>\n";
if (!$result['success']) {
    echo "<strong>Error Code:</strong> " . $result['error_code'] . "<br>\n";
    echo "<strong>Message:</strong> " . $result['message'] . "<br>\n";
}
echo "<br>\n";

// Test 5: Valid input (should succeed)
echo "<h2>Test 5: Valid Input (Should Succeed)</h2>\n";
$result = create_user_with_security_question(
    'test@example.com',
    'ValidPassword123!',
    'customer',
    'What was your first pet\'s name?',
    'Fluffy'
);
echo "<strong>Input:</strong> Valid data<br>\n";
echo "<strong>Result:</strong> " . ($result['success'] ? 'Success' : 'Failed') . "<br>\n";
if ($result['success']) {
    echo "<strong>User ID:</strong> " . $result['user']['id'] . "<br>\n";
    echo "<strong>Email:</strong> " . $result['user']['email'] . "<br>\n";
} else {
    echo "<strong>Error Code:</strong> " . $result['error_code'] . "<br>\n";
    echo "<strong>Message:</strong> " . $result['message'] . "<br>\n";
}
echo "<br>\n";

echo "<h2>Summary</h2>\n";
echo "<p>The improved error handling now provides:</p>\n";
echo "<ul>\n";
echo "<li><strong>Specific error codes</strong> for different failure scenarios</li>\n";
echo "<li><strong>User-friendly error messages</strong> that clearly explain the problem</li>\n";
echo "<li><strong>Field-specific error display</strong> in the registration form</li>\n";
echo "<li><strong>Real-time validation feedback</strong> with JavaScript</li>\n";
echo "<li><strong>Better logging</strong> for debugging and security monitoring</li>\n";
echo "</ul>\n";

echo "<p><strong>Error Codes Implemented:</strong></p>\n";
echo "<ul>\n";
echo "<li>INVALID_EMAIL - Invalid email format</li>\n";
echo "<li>WEAK_PASSWORD - Password doesn't meet complexity requirements</li>\n";
echo "<li>EMPTY_SECURITY_QUESTION - Security question is empty</li>\n";
echo "<li>WEAK_SECURITY_QUESTION - Security question is too short</li>\n";
echo "<li>EMPTY_SECURITY_ANSWER - Security answer is empty</li>\n";
echo "<li>WEAK_SECURITY_ANSWER - Security answer is too common/weak</li>\n";
echo "<li>EMAIL_EXISTS - Email address already registered</li>\n";
echo "<li>DATABASE_ERROR - System/database error</li>\n";
echo "</ul>\n";
?> 