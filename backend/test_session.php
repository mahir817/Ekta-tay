<?php
session_start();

echo "Session Test:\n";
echo "Session ID: " . session_id() . "\n";
echo "Session started: " . (session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No') . "\n";

// Check if user_id is in session
if (isset($_SESSION['user_id'])) {
    echo "User ID in session: " . $_SESSION['user_id'] . "\n";
} else {
    echo "No user_id in session.\n";
    echo "Available session variables:\n";
    foreach ($_SESSION as $key => $value) {
        echo "- $key: $value\n";
    }
}

// Try to set a test session variable
$_SESSION['test_var'] = 'test_value';
echo "Test session variable set.\n";

// Check if we can read it back
if (isset($_SESSION['test_var'])) {
    echo "Test session variable: " . $_SESSION['test_var'] . "\n";
} else {
    echo "Could not read test session variable.\n";
}
?>
