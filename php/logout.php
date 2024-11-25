<?php
session_start();
session_destroy();
header("Location: ../php/home.php");
exit();
?>