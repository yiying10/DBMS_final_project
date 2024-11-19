<?php
session_start();
$is_logged_in = isset($_SESSION['user_name']);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R~Pokemon Go！-參考資料</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- 側邊導覽列，方便導航到其他頁面 -->
    <nav class="sidebar">
        <ul>
            <li><a href="home.php">首頁</a></li>
            <li><a href="#" id="card-generation-link">卡牌生成區</a></li>
            <li><a href="cards.php">卡牌圖鑑</a></li>
            <li><a href="reference.php">關於我們</a></li>
        </ul>
    </nav>

    </header>
        <div class="user-info">
            <?php if ($is_logged_in): ?>
                <p class="welcome">歡迎, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                <a href="logout.php">
                    <button class="login-button">登出</button>
                </a>
            <?php else: ?>
                <a href="login.html" class="login-button-link">
                    <button class="login-button">登入 / 註冊</button>
                </a>
            <?php endif; ?>
        </div>
    </header>
    
    <main class="content">
        <section id="about">
            <h1>關於我們</h1>
            <br><hr><br>
            <p>課程名稱：NYCU 113-1 DBMS</p>
            <br>
            <p>您可以訪問我們的資料庫展示頁面以了解更多：</p>
            <br>
            <a href="https://www.kaggle.com/datasets/giorgiocarbone/complete-competitive-pokmon-datasets-may-2022?select=bridge_moves_pokemon_GMAX_MOVE.csv" target="_blank">Data Base</a>
            <br>
            <br>
            <a href="https://github.com/yiying10/DBMS_final_project" target="_blank">GitHub</a>
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
