<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting test...<br>";

require_once '../app/config/config.php';

echo "Config loaded.<br>";

if (isAuthenticated()) {
    echo "User is authenticated.<br>";
    $user = getCurrentUser();
    print_r($user);
} else {
    echo "User is NOT authenticated.<br>";
}

echo "<br>Test complete.";
?>