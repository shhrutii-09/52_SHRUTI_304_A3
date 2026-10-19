
<?php
session_start();

// Remove all session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to admin login
header("Location: login.php");
exit;
?>