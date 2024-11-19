<?php
session_start();
$is_logged_in = isset($_SESSION['user_name']);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R~Pokemon Go！-卡牌生成區</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="sidebar">
        <ul>
            <li><a href="home.php">首頁</a></li>
            <li><a href="generate.php">卡牌生成區</a></li>
            <li><a href="cards.php">卡牌圖鑑</a></li>
            <li><a href="reference.php">關於我們</a></li>
        </ul>
    </nav>

    <p class="welcome">歡迎, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
    <a href="logout.php">
        <button class="login-button">登出</button>
    </a>
    
    <main class="content">
        <section id="home">
            <h1>歡迎來到我們的網站</h1>
            <p>幫自己喜歡的寶可夢自製屬於自己的卡牌！</p>
        </section>
    </main>
</body>
</html>
