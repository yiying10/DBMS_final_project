<?php
session_start();
$is_logged_in = isset($_SESSION['user_name']);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R~Pokemon Go！首頁</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="sidebar">
        <ul>
            <li><a href="home.php">首頁</a></li>
            <li><a href="#" id="card-generation-link">卡牌生成區</a></li>
            <li><a href="cards.php">卡牌圖鑑</a></li>
            <li><a href="reference.php">關於我們</a></li>
        </ul>
    </nav>

    <header>
        <div class="user-info">
            <ul>
                <!-- 動態顯示登入 / 註冊 或使用者名稱 -->
                <?php if ($is_logged_in): ?>
                    <div class="user-info">
                        <p class="welcome">歡迎, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        <a href="logout.php">
                            <button class="login-button">登出</button>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.html" class="login-button-link">
                        <button class="login-button">登入 / 註冊</button>
                    </a>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <main class="content">
        <section id="home">
            <h1>歡迎來到我們的網站</h1>
            <br><hr><br>
            <p>幫自己喜歡的寶可夢自製屬於自己的卡牌！</p>
            <br>
            <h1>使用說明</h1>
            <div>
                <br><p>Step 1: 點擊右上角<b>登入/註冊</b></p>
                <br><p>Step 2: 登入後點擊左側<b>卡牌生成區</b></p>
                <br><p>Step 3: 選擇寶可夢的類別、屬性</p>
                <br><p>Step 4: 點擊完成生成卡牌</p>
                <br><p>Tip: 參考卡牌圖鑑製作你的專屬卡牌吧</p>
            </div>
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
                    cardGenerationLink.href = "generate.php"; // 設定連結目標
                }
            });
        });
    </script>
</body>
</html>
