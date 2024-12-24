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
            <li><a href="../php/forum.php" id="forum-link">論壇</a></li>
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
        <h1>歡迎來到寶可夢卡牌創作世界！</h1>
        <br><hr><br>
        <br><p>在這裡，你可以釋放你的創意，打造屬於你自己的獨特寶可夢卡牌！我們的網站提供簡單易用的工具，讓每位寶可夢愛好者都能輕鬆設計夢想中的卡牌。</p>
        <br><h1>功能亮點：</h1>
        <ul>
        <br><li><strong>創造專屬卡牌</strong>：挑選你最愛的寶可夢，設計專屬的屬性、技能與能力，甚至可以匯入自己的圖片，讓每張卡牌都是獨一無二的！</li>
        <br><li><strong>每日登入抽卡</strong>：每天登入即可參加一次抽卡活動，看看今天的運氣如何，獲得驚喜的卡牌收藏！</li>
        <br><li><strong>分享與收藏</strong>：完成卡牌設計後，將你的作品分享到社群，與朋友交流創意，還能收藏其他玩家的作品。</li>
        <br></ul>
        <br><hr><br>
        <br><h1>立即加入，開始創作！</h1>
        <br><p>
            註冊你的帳號，探索無限的可能性，加入這個充滿熱情與創意的社群！
        </p>
        <br><p>快來體驗吧，讓你的寶可夢故事更加精彩！</p>
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