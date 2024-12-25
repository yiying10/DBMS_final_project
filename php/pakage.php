<?php
session_start();
$is_logged_in = isset($_SESSION['user_name']);

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

// 定義可能的卡包類型和對應稀有度
$card_packs = [
    '普通卡包' => ['Common'],
    '稀有卡包' => ['Rare', 'Common'],
    '傳說卡包' => ['Legendary', 'Rare', 'Common']
];

// 預設不顯示卡片
$random_cards = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 隨機選擇一個卡包類型
    $random_pack = array_rand($card_packs);
    $random_cards = generateRandomCards($card_packs[$random_pack], $conn);
}

// 隨機生成卡片
function generateRandomCards($rarities, $conn)
{
    $placeholders = implode(',', array_fill(0, count($rarities), '?'));
    $query = "SELECT Name, Rarity, Type1, Type2, image_url 
              FROM df_pokemon 
              WHERE Rarity IN ($placeholders) 
              ORDER BY RAND() 
              LIMIT 5";

    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param(str_repeat('s', count($rarities)), ...$rarities);
        $stmt->execute();
        $result = $stmt->get_result();

        $cards = [];
        while ($row = $result->fetch_assoc()) {
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
    <title>抽卡區</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/pakage.css">
</head>

<body>
    <nav class="sidebar">
        <ul>
            <li><a href="../php/home.php">首頁</a></li>
            <li><a href="../php/generate.php">卡牌生成區</a></li>
            <li><a href="../php/illustrated_book.php">卡牌圖鑑</a></li>
            <li><a href="../php/pakage.php">抽卡區</a></li>
            <li><a href="../php/booklet.php">卡冊</a></li>
            <li><a href="../php/reference.php">關於我們</a></li>
        </ul>
    </nav>

    <main class="content">
        <h1>抽卡區</h1>

        <div id="packContainer" class="pack-container">
            <div class="pack-item">
                <form method="POST" action="pakage.php" class="draw-form">
                    <button type="submit" class="pack-button">
                        <img src="../images/pakage.png" alt="卡包">
                    </button>
                </form>
            </div>

            <div class="pack-item">
                <form method="POST" action="pakage.php" class="draw-form">
                    <button type="submit" class="pack-button">
                        <img src="../images/pakage.png" alt="卡包">
                    </button>
                </form>
            </div>

            <div class="pack-item">
                <form method="POST" action="pakage.php" class="draw-form">
                    <button type="submit" class="pack-button">
                        <img src="../images/pakage.png" alt="卡包">
                    </button>
                </form>
            </div>
        </div>

        <?php if (!empty($random_cards)): ?>
            <div id="cardResults" class="card-results">
                <h2>你抽到的卡片：</h2>
                <div class="card-container">
                    <?php foreach ($random_cards as $card): ?>
                        <div class="card-item">
                            <div class="card-inner">
                                <div class="card-back">
                                    <img src="../images/card_back.png" width="100" height="auto">
                                </div>
                                <div class="card-front">
                                    <img src="<?php echo htmlspecialchars($card['image_url']); ?>"
                                        alt="<?php echo htmlspecialchars($card['Name']); ?>">
                                    <p class="card-info card-name">
                                        <?php echo htmlspecialchars($card['Name']); ?>
                                    </p>
                                    <p class="card-info card-rarity">
                                        <?php echo htmlspecialchars($card['Rarity']); ?>
                                    </p>
                                    <p class="card-info card-type">
                                        <?php echo htmlspecialchars($card['Type1']); ?>
                                        <?php echo $card['Type2'] ? "/ " . htmlspecialchars($card['Type2']) : ""; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="collection-button">加入卡冊</button>
                <form method="POST" action="pakage.php">
                    <button type="submit" class="draw-button">再抽一次</button>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <script src="../js/pakage.js"></script>

    <style>
        .content {
            background: none;
        }

        .pack-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 50px;
            margin: 50px auto;
            max-width: 1200px;
        }

        .pack-item {
            flex: 0 0 auto;
        }

        .pack-button {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .pack-button:hover {
            transform: scale(1.1);
        }

        .pack-button img {
            width: 300px;
            height: auto;
        }
    </style>
</body>

</html>