<?php
session_start();
$is_logged_in = isset($_SESSION['user_name']);
$coins = 0; // 預設代幣數量為0

// 開啟錯誤顯示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 資料庫連線設定
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

// 建立連線
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連線
if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}

// 設定字符集
$conn->set_charset("utf8mb4");

// 如果用戶已登入，獲取代幣數量
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT COALESCE(coins, 0) as coins FROM account WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($coins);
    $stmt->fetch();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>寶可夢圖鑑</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/illustrated_book.css">
</head>

<body data-page="illustrated_book">
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

    <!-- Sidebar -->
    <nav class="sidebar">
        <ul>
            <li><a href="../php/home.php">首頁</a></li>
            <li><a href="../php/generate.php" id="card-generation-link" onclick="checkLogin(event)">卡牌生成區</a></li>
            <li><a href="../php/illustrated_book.php">卡牌圖鑑</a></li>
            <li><a href="../php/pakage.php" id="pakage-link" onclick="checkLogin(event)">抽卡區</a></li>
            <li><a href="../php/booklet.php" id="booklet-link" onclick="checkLogin(event)">卡冊</a></li>
            <li><a href="../php/forum.php" id="forum-link" onclick="checkLogin(event)">論壇</a></li>
            <li><a href="../php/reference.php">關於我們</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="content">
        <h1>卡牌圖鑑</h1>
        <div class="pokedex-container">
            <div class="search-filter-section">
                <input type="text" class="search-box" placeholder="搜尋寶可夢...">

                <div class="filter-group">
                    <div class="filter-title">稀有度</div>
                    <div class="filter-options">
                        <input type="radio" id="rare-all" name="rarity" class="filter-checkbox" checked>
                        <label for="rare-all" class="filter-label">全部</label>

                        <?php
                        $sql = "SELECT DISTINCT Rarity FROM df_pokemon WHERE Rarity IS NOT NULL AND Rarity != ''";
                        $result = $conn->query($sql);
                        if (!$result) {
                            echo "稀有度查詢錯誤: " . $conn->error;
                        } else {
                            while ($row = $result->fetch_assoc()) {
                                $rarity = $row['Rarity'];
                                echo '<input type="radio" id="rare-' . strtolower($rarity) . '" name="rarity" class="filter-checkbox">';
                                echo '<label for="rare-' . strtolower($rarity) . '" class="filter-label">' . $rarity . '</label>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <div class="filter-group">
                    <div class="filter-title">屬性</div>
                    <div class="filter-options">
                        <input type="radio" id="type-all" name="type" class="filter-checkbox" checked>
                        <label for="type-all" class="filter-label">全部</label>

                        <?php
                        $sql = "SELECT DISTINCT Type1 FROM df_pokemon WHERE Type1 IS NOT NULL AND Type1 != ''";
                        $result = $conn->query($sql);
                        if (!$result) {
                            echo "屬性查詢錯誤: " . $conn->error;
                        } else {
                            while ($row = $result->fetch_assoc()) {
                                $type = $row['Type1'];
                                echo '<input type="radio" id="type-' . strtolower($type) . '" name="type" class="filter-checkbox">';
                                echo '<label for="type-' . strtolower($type) . '" class="filter-label">' . $type . '</label>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="pokemon-grid">
                <?php
                // 顯示 SQL 查詢
                $sql = "SELECT * FROM df_pokemon 
                        WHERE image_url IS NOT NULL AND image_url != '' 
                        AND Type1 IS NOT NULL AND Type1 != ''
                        AND Rarity IS NOT NULL AND Rarity != ''";

                echo "<!-- SQL 查詢: $sql -->";

                $result = $conn->query($sql);
                if (!$result) {
                    echo "寶可夢查詢錯誤: " . $conn->error;
                } else {
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $name = $row['Name'];
                            $rarity = $row['Rarity'];
                            $type = $row['Type1'];

                            // 檢查圖片是否存在
                            $imageFileName = strtolower(str_replace(' ', '-', $name));
                            $imagePath = "../images/pokemon_images/{$imageFileName}.png";

                            if (file_exists($imagePath)) {
                                echo '<div class="pokemon-card" 
                                          data-name="' . htmlspecialchars($name) . '"
                                          data-rarity="' . htmlspecialchars(strtolower($rarity)) . '"
                                          data-type="' . htmlspecialchars(strtolower($type)) . '">';
                                echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($name) . '" 
                                          style="width: 150px; height: 150px; object-fit: contain;">';
                                echo '<h3>' . htmlspecialchars($name) . '</h3>';
                                echo '<button class="detail-btn" onclick="showDetails(\'' . htmlspecialchars($name) . '\', \'' .
                                    htmlspecialchars($rarity) . '\', \'' . htmlspecialchars($type) . '\', \'' .
                                    htmlspecialchars($imagePath) . '\', \'' . htmlspecialchars($row['Type2']) . '\', \'' .
                                    htmlspecialchars($row['Total']) . '\')">詳細資訊</button>';
                                echo '<button class="generate-btn" onclick="redirectToCardGenerator(\'' .
                                    htmlspecialchars($name) . '\', \'' .
                                    htmlspecialchars($rarity) . '\', \'' .
                                    htmlspecialchars($type) . '\', \'' .
                                    htmlspecialchars($imagePath) . '\', \'' .
                                    htmlspecialchars($row['Type2']) . '\')">生成卡牌</button>';
                                echo '</div>';
                            }
                        }
                    } else {
                        echo "<p>沒有找到任何寶可夢。</p>";
                    }
                }
                ?>
            </div>
        </div>
    </main>

    <script src="../js/illustrated_book.js"></script>

    <?php
    // 在檔案結尾關閉資料庫連線
    $conn->close();
    ?>

    <!-- 在 body 結束前添加模態框 -->
    <div id="pokemonModal" class="modal">
        <div class="modal-content">
            <div class="pokemon-details">
                <img id="modalImage" class="pokemon-image-large" src="" alt="">
                <div class="pokemon-info">
                    <h2 id="modalName"></h2>
                    <div class="info-row">
                        <span class="info-label">稀有度：</span>
                        <span id="modalRarity"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">主要屬性：</span>
                        <span id="modalType1"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">次要屬性：</span>
                        <span id="modalType2"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">能力值總和：</span>
                        <span id="modalTotal"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="close-modal-btn" onclick="closeModal()">關閉</button>
            </div>
        </div>
    </div>

    <script>
        function redirectToCardGenerator(name, rarity, type, imagePath, type2) {
            <?php if (!$is_logged_in): ?>
                alert("登入後即可使用");
                return;
            <?php endif; ?>
            // 如果已登入，則執行跳轉邏輯
            window.location.href = `../php/card_generator.php?name=${encodeURIComponent(name)}&rarity=${encodeURIComponent(rarity)}&type=${encodeURIComponent(type)}&imagePath=${encodeURIComponent(imagePath)}&type2=${encodeURIComponent(type2)}`;
        }

        function checkLogin(event) {
            <?php if (!$is_logged_in): ?>
                event.preventDefault();
                alert("登入後即可使用");
            <?php endif; ?>
        }
    </script>
</body>

</html>