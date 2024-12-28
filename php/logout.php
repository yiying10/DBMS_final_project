<?php
session_start();
session_destroy();
echo "<script>alert('成功登出！'); window.location.href='../php/home.php';</script>";
exit();
?>