<?php
session_start();

// Close session and return to Landing
session_unset();
session_destroy();
header("location: landing.php");
?>