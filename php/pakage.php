<?php
session_start();
$is_logged_in = isset($_SESSION['user_name']);
$coins = 0; // 預設代幣數量為0

if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

// 資料庫連線設置
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("資料庫連接失敗：" . $conn->connect_error);
}

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

// 定義可能的卡包類型和對應稀有度
$card_packs = [
    '普通卡包' => ['Common'],
    '稀有卡包' => ['Rare', 'Common'],
    '傳說卡包' => ['Legendary', 'Rare', 'Common']
];

// 預設不顯示卡片
$random_cards = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 檢查是否是加求
    if (isset($_POST['action']) && $_POST['action'] === 'add_to_booklet') {
        header('Content-Type: application/json');

        try {
            $cards = json_decode($_POST['cards'], true);
            if (!$cards) {
                throw new Exception('無效的卡片數據');
            }

            // 開始資料庫交易
            $conn->begin_transaction();

            // 獲取當前最大的 card_id
            $max_id_sql = "SELECT COALESCE(MAX(CAST(card_id AS UNSIGNED)), 0) as max_id FROM booklet";
            $result = $conn->query($max_id_sql);
            $row = $result->fetch_assoc();
            $current_max_id = $row['max_id'];

            // 插入卡片到用戶的卡冊
            $insert_sql = "INSERT INTO booklet (user_id, card_id, pokemon_name, background_image_url) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);

            foreach ($cards as $index => $card) {
                $new_card_id = $current_max_id + $index + 1;
                // 使用前端傳來的 backgroundUrl
                $background_url = $card['backgroundUrl'];

                $stmt->bind_param(
                    "isss",
                    $user_id,
                    $new_card_id,
                    $card['pokemon_name'],
                    $background_url
                );

                if (!$stmt->execute()) {
                    throw new Exception('插入卡片失敗');
                }
            }

            // 提交交易
            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => '成功加入卡冊'
            ]);
            exit;

        } catch (Exception $e) {
            // 如果出錯，回滾交易
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => '加入卡冊失敗：' . $e->getMessage()
            ]);
            exit;
        }
    }

    // 處理抽卡請求的代碼保持不變
    if (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
    ) {
        header('Content-Type: application/json');

        // 檢查餘額
        if ($coins < 10) {
            echo json_encode([
                'success' => false,
                'message' => '餘額不足，需要10個金幣才能抽卡'
            ]);
            exit;
        }

        try {
            // 開始資料庫交易
            $conn->begin_transaction();

            // 除金幣
            $update_sql = "UPDATE account SET coins = coins - 10 WHERE user_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("i", $user_id);

            if (!$stmt->execute()) {
                throw new Exception("更新金幣失敗");
            }

            // 生成卡片
            $random_pack = array_rand($card_packs);
            $random_cards = generateRandomCards($card_packs[$random_pack], $conn);

            if (empty($random_cards)) {
                throw new Exception("生成卡片失敗");
            }

            // 提交交易
            $conn->commit();

            echo json_encode([
                'success' => true,
                'cards' => $random_cards,
                'new_balance' => $coins - 10
            ]);
            exit;

        } catch (Exception $e) {
            // 如果出錯，回滾交易
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => '抽卡過程中發生錯誤: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}

// 隨機生成卡片
function generateRandomCards($rarities, $conn)
{
    // 先獲取 pokemon_images 目錄下所有可用的圖片
    $image_dir = "../images/pokemon_images/";
    $available_images = array_map(
        function ($file) {
            return pathinfo($file, PATHINFO_FILENAME);
        },
        array_filter(
            scandir($image_dir),
            function ($file) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'png';
            }
        )
    );

    // 修改 SQL 查詢以匹配需求
    $placeholders_rarity = implode(',', array_fill(0, count($rarities), '?'));
    $query = "SELECT 
                p.Name as pokemon_name,
                p.Rarity as rarity,
                p.Type1 as type1,
                p.Type2 as type2,
                a.Ability as Ability,
                ad.Description as Description
              FROM df_pokemon p
              LEFT JOIN ability a ON p.Name = a.Name
              LEFT JOIN ability_description ad ON a.Ability = ad.Name
              WHERE p.Rarity IN ($placeholders_rarity)
              AND LOWER(REPLACE(p.Name, ' ', '-')) IN ('" .
        implode("','", array_map(function ($name) {
            return strtolower($name);
        }, $available_images)) . "')
              ORDER BY RAND() 
              LIMIT 5";

    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param(str_repeat('s', count($rarities)), ...$rarities);
        $stmt->execute();
        $result = $stmt->get_result();

        $cards = [];
        while ($row = $result->fetch_assoc()) {
            // 生成本地圖片路徑
            $imageFileName = strtolower(str_replace(' ', '-', $row['pokemon_name']));
            $row['image_url'] = "../images/pokemon_images/{$imageFileName}.png";
            $cards[] = $row;
        }
        return $cards;
    }

    return [];
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?php echo htmlspecialchars($user_id); ?>">
    <title>抽卡區</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/pakage.css">
</head>

<body>
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
<<<<<<< HEAD
            <li><a href="../php/custom_card.php" id="custom-card-link">自製卡牌區</a></li>
            <li><a href="../php/generate.php" id="card-generation-link">卡牌生成區</a></li>
=======
            <li><a href="../php/custom_card.php">自製卡牌區</a></li>
            <li><a href="../php/generate.php">卡牌生成區</a></li>
>>>>>>> 04edab16843efa9a6ce17f985241eb5ae5f8550e
            <li><a href="../php/illustrated_book.php">卡牌圖鑑</a></li>
            <li><a href="../php/pakage.php" id="pakage-link">抽卡區</a></li>
            <li><a href="../php/booklet.php" id="booklet-link">卡冊</a></li>
            <li><a href="../php/forum.php" id="forum-link">論壇</a></li>
            <li><a href="../php/reference.php">關於我們</a></li>
        </ul>
    </nav>

    <main class="content">
        <h1>抽卡區</h1>

        <!-- 卡包容器 -->
        <div id="packContainer" class="pack-container">
            <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="pack-item">
                    <button class="pack-button">
                        <img src="../images/pakage.png" alt="卡包">
                    </button>
                </div>
            <?php endfor; ?>
        </div>

        <!-- 卡片結果容器 -->
        <div id="cardResults" class="card-results" style="display: none;">
            <h2>你抽到的卡片：</h2>
            <div class="card-container"></div>
            <div class="button-container">
                <button class="collection-button">加入卡冊</button>
                <button class="draw-button">再抽一次</button>
            </div>
        </div>

        <!-- PHP 數據傳遞給 JavaScript -->
        <?php if (isset($random_cards) && !empty($random_cards)): ?>
            <input type="hidden" id="php-cards-data" value="<?php echo htmlspecialchars(json_encode($random_cards)); ?>">
        <?php endif; ?>
    </main>

    <style>
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin: 20px 0;
            min-height: 400px;
        }

        .card-item {
            width: 250px;
            height: 350px;
            perspective: 1000px;
            cursor: pointer;
        }

        .card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: center;
            transition: transform 0.6s;
            transform-style: preserve-3d;
            transform-origin: center;
        }

        .card-inner.card-flipped {
            transform: rotateY(180deg);
        }

        .card-back,
        .card-front {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            border-radius: 10px;
            border: none;
        }

        .card-front {
            transform: rotateY(180deg);
        }

        .pack-container {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin: 50px 0;
        }

        .pack-button {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .pack-button:hover {
            transform: scale(1.05);
        }

        .pack-button img {
            width: 300px;
            height: auto;
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .collection-button,
        .draw-button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .collection-button:hover,
        .draw-button:hover {
            background-color: #45a049;
        }
    </style>

    <script src="../js/pakage.js"></script>
</body>

</html>