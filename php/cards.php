<?php
session_start();
$is_logged_in = isset($_SESSION['user_name']);
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R~Pokemon Go！-卡牌圖鑑</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>
    <nav class="sidebar">
        <ul>
            <li><a href="../php/home.php">首頁</a></li>
            <li><a href="../php/generate.php" id="card-generation-link">卡牌生成區</a></li>
            <li><a href="../php/cards.php">卡牌圖鑑</a></li>
            <li><a href="../php/reference.php">關於我們</a></li>
        </ul>
    </nav>
    </header>
    <div class="user-info">
        <?php if ($is_logged_in): ?>
            <p class="welcome">歡迎, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
            <a href="../php/logout.php">
                <button class="login-button">登出</button>
            </a>
        <?php else: ?>
            <a href="../html/login.html" class="login-button-link">
                <button class="login-button">登入 / 註冊</button>
            </a>
        <?php endif; ?>
    </div>
    </header>

    <main class="content">
        <section id="home">
            <h1>卡牌圖鑑</h1>
            <p>幫自己喜歡的寶可夢自製屬於自己的卡牌！</p>
        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
            const cardGenerationLink = document.getElementById("card-generation-link");

            cardGenerationLink.addEventListener("click", function (e) {
                if (!isLoggedIn) {
                    e.preventDefault(); // 停止跳轉
                    alert("登入後即可使用");
                } else {
                    cardGenerationLink.href = "../php/generate.php"; // 設定連結目標
                }
            });
        });
    </script>
</body>

</html>