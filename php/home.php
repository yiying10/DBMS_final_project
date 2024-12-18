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
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body data-page="home">
    <nav class="sidebar">
        <ul>
            <li><a href="../php/home.php">首頁</a></li>
            <li><a href="../php/generate.php" id="card-generation-link">卡牌生成區</a></li>
            <li><a href="../php/illustrated_book.php">卡牌圖鑑</a></li>
            <li><a href="../php/pakage.php" id="pakage-link">抽卡區</a></li>
            <li><a href="../php/booklet.php" id="booklet-link">卡冊</a></li>
            <li><a href="../php/reference.php">關於我們</a></li>
        </ul>
    </nav>

    <header>
        <div class="user-info">
            <ul>
                <!-- 動態顯示登入 / 註冊 或使用者名稱 -->
                <?php if ($is_logged_in): ?>
                    <div class="user-info">
                        <p class="welcome">歡迎, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        <a href="../php/logout.php">
                            <button class="login-button">登出</button>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="../html/login.html" class="login-button-link">
                        <button class="login-button">登入 / 註冊</button>
                    </a>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <main class="content">
        <section id="home">
            <h1>歡迎來到我們的網站</h1>
            <br>
            <hr><br>
            <p>幫自己喜歡的寶可夢自製屬於自己的卡牌！</p>
            <br>
            <h1>使用說明</h1>
            <div>
                <br>
                <p>Step 1: 點擊右上角<b>登入/註冊</b></p>
                <br>
                <p>Step 2: 登入後點擊左側<b>卡牌生成區</b></p>
                <br>
                <p>Step 3: 選擇寶可夢的類別、屬性</p>
                <br>
                <p>Step 4: 點擊完成生成卡牌</p>
                <br>
                <p>Tip: 參考卡牌圖鑑製作你的專屬卡牌吧</p>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
            const cardGenerationLink = document.getElementById("card-generation-link");
            const pakageLink = document.getElementById("pakage-link");
            const bookletLink = document.getElementById("booklet-link");

            // 卡牌生成區權限控制
            cardGenerationLink.addEventListener("click", function (e) {
                if (!isLoggedIn) {
                    e.preventDefault();
                    alert("登入後即可使用");
                }
            });

            // 抽卡區權限控制
            pakageLink.addEventListener("click", function (e) {
                if (!isLoggedIn) {
                    e.preventDefault();
                    alert("登入後即可使用");
                }
            });

            // 卡冊權限控制
            bookletLink.addEventListener("click", function (e) {
                if (!isLoggedIn) {
                    e.preventDefault();
                    alert("登入後即可使用");
                }
            });
        });
    </script>
</body>

</html>