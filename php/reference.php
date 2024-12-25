<?php
session_start();
$is_logged_in = isset($_SESSION['user_name']);
$coins = 0; // 預設代幣數量為0

// 如果用戶已登入，獲取代幣數量
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "Pokemon";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("資料庫連接失敗：" . $conn->connect_error);
    }

    $sql = "SELECT COALESCE(coins, 0) as coins FROM account WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($coins);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R~Pokemon Go！-參考資料</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body data-page="reference">
    <header>
        <div class="user-info">
            <ul>
                <?php if ($is_logged_in): ?>
                    <div class="user-info">
                        <span class="coin-display">
                            <img src="../images/coin-icon.png" alt="代幣" class="coin-icon">
                            <span id="coin-amount"><?php echo $coins; ?></span>
                        </span>
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

    <main class="content">
        <section id="about">
            <h1>關於我們</h1>
            <br>
            <hr><br>
            <p>課程名稱：NYCU 113-1 DBMS</p>
            <br>
            <p>您可以訪問我們的資料庫展示頁面以了解更多：</p>
            <br>
            <a href="https://www.kaggle.com/datasets/giorgiocarbone/complete-competitive-pokmon-datasets-may-2022?select=bridge_moves_pokemon_GMAX_MOVE.csv"
                target="_blank">Data Base</a>
            <br>
            <br>
            <a href="https://github.com/yiying10/DBMS_final_project" target="_blank">GitHub</a>
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

            // 論壇權限控制
            const forumLink = document.getElementById("forum-link");
            forumLink.addEventListener("click", function (e) {
                if (!isLoggedIn) {
                    e.preventDefault();
                    alert("登入後即可使用");
                }
            });
        });
    </script>
</body>

</html>