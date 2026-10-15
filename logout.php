<?php
session_start();

// Destroy the user's login session
session_unset();
session_destroy();

// Send them back to the login page with a logout message
header("Location: index.php?logged_out=1");
exit();
?>
