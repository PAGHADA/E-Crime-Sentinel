<?php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to homepage (index.html)
header("Location: index.html");
exit();
?>
