<?php
session_start();
$is_logged_in = isset($_SESSION['user_name']);
$coins = 0; // 預設代幣數量為0

// 建立數據庫連接
try {
    $db = new PDO('mysql:host=localhost;dbname=pokemon;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 確保 account 表有 coins 欄位，如果沒有則添加
    $sql = "SHOW COLUMNS FROM account LIKE 'coins'";
    $result = $db->query($sql);
    if ($result->rowCount() == 0) {
        $sql = "ALTER TABLE account ADD coins INT DEFAULT 0";
        $db->exec($sql);
    }

    // 如果用戶已登入，獲取代幣數量
    if ($is_logged_in) {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT COALESCE(coins, 0) as coins FROM account WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $coins = $user['coins'] ?? 0; // 如果沒有值就默認為0
    }

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
}
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
            <li><a href="../php/custom_card.php" id="custom-card-link">新增寶可夢</a></li>
            <li><a href="../php/generate.php" id="card-generation-link">卡牌生成區</a></li>
            <li><a href="../php/illustrated_book.php">寶可夢圖鑑</a></li>
            <li><a href="../php/pakage.php" id="pakage-link">抽卡區</a></li>
            <li><a href="../php/booklet.php" id="booklet-link">卡冊</a></li>
            <li><a href="../php/forum.php" id="forum-link">論壇</a></li>
            <li><a href="../php/reference.php">關於我們</a></li>
        </ul>
    </nav>

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

    <main class="content">
        <section id="home">
            <h1>歡迎來到寶可夢卡牌創作世界！</h1>
            <br>
            <hr><br>
            <br>
            <p>在這裡，你可以釋放你的創意，打造屬於你自己的獨特寶可夢卡牌！我們的網站提供簡單易用的工具，讓每位寶可夢愛好者都能輕鬆設計夢想中的卡牌。</p>
            <br>
            <h1>功能亮點：</h1>
            <ul>
                <br>
                <li><strong>創造專屬卡牌</strong>：挑選你最愛的寶可夢，設計專屬的屬性、技能與能力，甚至可以匯入自己的圖片，讓每張卡牌都是獨一無二的！</li>
                <br>
                <li><strong>每日登入抽卡</strong>：每天登入即可參加一次抽卡活動，看看今天的運氣如何，獲得驚喜的卡牌收藏！</li>
                <br>
                <li><strong>分享與收藏</strong>：完成設計後，將你的作品分享到社群，與朋友交流創意，還能收藏其他玩家的作品。</li>
                <br>
            </ul>
            <br>
            <hr><br>
            <br>
            <h1>立即加入，開始創作！</h1>
            <br>
            <p>
                註冊你的帳號，探索無限的可能性，加入這個充滿熱情與創意的社群！
            </p>
            <br>
            <p>快來體驗吧，讓你的寶可夢故事更加精彩！</p>
            <br>
            <hr><br>
        </section>
        <section id="coin-claim">
            <h2>代幣領取區</h2>
            <div class="coin-box">
                <p>每5秒可領取一次抽卡代幣</p>
                <div id="claim-status"></div>
                <button id="claim-button" class="claim-button">領取代幣</button>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
            const cardGenerationLink = document.getElementById("card-generation-link");
            const pakageLink = document.getElementById("pakage-link");
            const bookletLink = document.getElementById("booklet-link");
            const forumLink = document.getElementById("forum-link");
            const customCardLink = document.getElementById("custom-card-link");

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
            forumLink.addEventListener("click", function (e) {
                if (!isLoggedIn) {
                    e.preventDefault();
                    alert("登入後即可使用");
                }
            });

            // 自製卡牌區權限控制
            customCardLink.addEventListener("click", function (e) {
                if (!isLoggedIn) {
                    e.preventDefault();
                    alert("登入後即可使用");
                }
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
            const claimButton = document.getElementById("claim-button");
            const claimStatus = document.getElementById("claim-status");
            const coinAmount = document.getElementById("coin-amount");
            let countdownTimer;

            // 立即禁用按鈕並開始檢查
            claimButton.disabled = true;
            if (isLoggedIn) {
                checkClaimStatus();
                setInterval(checkClaimStatus, 1000); // 每秒檢查一次狀態

                claimButton.addEventListener("click", async function () {
                    if (!claimButton.disabled) {
                        await claimCoin();
                    }
                });
            } else {
                claimStatus.innerHTML = "請先登入再領取代幣";
            }

            async function claimCoin() {
                claimButton.disabled = true;
                try {
                    const response = await fetch('../php/claim_coin.php', {
                        method: 'POST'
                    });
                    const data = await response.json();

                    if (data.success) {
                        coinAmount.textContent = data.coins;
                        claimStatus.innerHTML = `成功領取代幣！當前擁有 ${data.coins} 個代幣`;
                        checkClaimStatus(); // 立即檢查新狀態
                    } else {
                        claimStatus.innerHTML = data.message;
                    }
                } catch (error) {
                    claimStatus.innerHTML = "領取失敗，請稍後再試";
                }
            }

            async function checkClaimStatus() {
                try {
                    const response = await fetch('../php/check_claim_status.php');
                    const data = await response.json();

                    if (data.canClaim) {
                        claimButton.disabled = false;
                        claimStatus.innerHTML = "可以領取代幣";
                        claimButton.classList.add('ready-to-claim');
                    } else if (data.remainingTime > 0) {
                        claimButton.disabled = true;
                        claimStatus.innerHTML = `距離下次領取還有 ${Math.ceil(data.remainingTime)} 秒`;
                        claimButton.classList.remove('ready-to-claim');
                    }
                } catch (error) {
                    claimStatus.innerHTML = "無法檢查領取狀態";
                    claimButton.disabled = true;
                }
            }
        });
    </script>


</body>

</html>