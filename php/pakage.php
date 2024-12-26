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
    // 檢查是否是加��卡冊的請求
    if (isset($_POST['action']) && $_POST['action'] === 'add_to_booklet') {
        header('Content-Type: application/json');

        try {
            $cards = json_decode($_POST['cards'], true);
            if (!$cards) {
                throw new Exception('無效的卡片數據');
            }

            // 開始資料庫交易
            $conn->begin_transaction();

            // 插入卡片到用戶的卡冊
            $insert_sql = "INSERT INTO user_cards (user_id, pokemon_name, rarity, type1, type2) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);

            foreach ($cards as $card) {
                $stmt->bind_param(
                    "issss",
                    $user_id,
                    $card['Name'],
                    $card['Rarity'],
                    $card['Type1'],
                    $card['Type2']
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

            // 扣除金幣
            $update_sql = "UPDATE account SET coins = coins - 10 WHERE user_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("i", $user_id);
            $result = $stmt->execute();

            if (!$result) {
                throw new Exception("更新金幣失敗");
            }

            // 生成卡片
            $random_pack = array_rand($card_packs);
            $random_cards = generateRandomCards($card_packs[$random_pack], $conn);

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

    // 修改 SQL 查詢，只選擇有對應圖片的寶可夢
    $image_names = array_map(function ($name) {
        return str_replace('-', ' ', $name);
    }, $available_images);

    $placeholders_rarity = implode(',', array_fill(0, count($rarities), '?'));
    $query = "SELECT Name, Rarity, Type1, Type2 
              FROM df_pokemon 
              WHERE Rarity IN ($placeholders_rarity)
              AND LOWER(REPLACE(Name, ' ', '-')) IN ('" .
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
            $imageFileName = strtolower(str_replace(' ', '-', $row['Name']));
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
            <li><a href="../php/custom_card.php" id="custom-card-link">自製卡牌區</a></li>
            <li><a href="../php/generate.php">卡牌生成區</a></li>
            <li><a href="../php/illustrated_book.php">卡牌圖鑑</a></li>
            <li><a href="../php/pakage.php">抽卡區</a></li>
            <li><a href="../php/booklet.php">卡冊</a></li>
            <li><a href="../php/forum.php" id="forum-link">論壇</a></li>
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
                                    <img src="../images/card_back.png" alt="card back" width="250" height="350">
                                </div>
                                <div class="card-front">
                                    <canvas class="card-canvas" width="250" height="350"></canvas>
                                    <input type="hidden" class="card-data"
                                        value="<?php echo htmlspecialchars(json_encode($card)); ?>">
                                    <div class="card-info">
                                        <p class="card-name"><?php echo htmlspecialchars($card['Name']); ?></p>
                                        <p class="card-rarity"><?php echo htmlspecialchars($card['Rarity']); ?></p>
                                        <p class="card-type">
                                            <?php
                                            echo htmlspecialchars($card['Type1']);
                                            if (!empty($card['Type2'])) {
                                                echo ' / ' . htmlspecialchars($card['Type2']);
                                            }
                                            ?>
                                        </p>
                                    </div>
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

    <script>
        // 隨機選擇景圖片
        function getBackgroundByRarity(rarity) {
            // 定義每個稀有度對應的背景圖片目錄和可用的背景圖片名稱
            const backgroundFiles = {
                'Common': {
                    path: '../images/card_background/normal/',
                    files: [
                        'darkblue.png',
                        'darkgreen.png',
                        'lightblue.png',
                        'pink.png',
                        'red.png',
                        'tiffany.png',
                        'violet.png'
                    ]
                },
                'Baby': {
                    path: '../images/card_background/normal/',
                    files: [
                        'darkblue.png',
                        'darkgreen.png',
                        'lightblue.png',
                        'pink.png',
                        'red.png',
                        'tiffany.png',
                        'violet.png'
                    ]
                },
                'Rare': {
                    path: '../images/card_background/normal/',
                    files: [
                        'darkblue.png',
                        'darkgreen.png',
                        'lightblue.png',
                        'pink.png',
                        'red.png',
                        'tiffany.png',
                        'violet.png'
                    ]
                },
                'Lengendary': {
                    path: '../images/card_background/rare/',
                    files: [
                        'blackblue.png',
                        'blue.png',
                        'bluepurple.png',
                        'colorful.png',
                        'metalpurple.png',
                        'pink.png',
                        'razer.png',
                        'red.png',
                        'silver.png',
                        'whiteblue.png'
                    ]
                },
                'Mythical': {
                    path: '../images/card_background/rare/',
                    files: [
                        'blackblue.png',
                        'blue.png',
                        'bluepurple.png',
                        'colorful.png',
                        'metalpurple.png',
                        'pink.png',
                        'razer.png',
                        'red.png',
                        'silver.png',
                        'whiteblue.png'
                    ]
                }
            };

            // 獲取對應稀有度的配置
            const config = backgroundFiles[rarity] || backgroundFiles['Common'];

            // 從可用文件列表中隨機選擇一個
            const randomFile = config.files[Math.floor(Math.random() * config.files.length)];

            // 返回完整的圖片路徑
            return config.path + randomFile;
        }

        // 定義繪製卡片的函數
        async function drawCardDataURL(cardData, width, height) {
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');

            try {
                console.log('Drawing card:', cardData);

                // 先繪製背景
                const backgroundImage = await loadImage(getBackgroundByRarity(cardData.Rarity));
                ctx.drawImage(backgroundImage, 0, 0, width, height);

                // 再繪製寶可夢圖片
                const pokemonImage = await loadImage(cardData.image_url);
                const pokemonSize = Math.min(width * 0.8, height * 0.6);
                const x = (width - pokemonSize) / 2;
                const y = height * 0.2;
                ctx.drawImage(pokemonImage, x, y, pokemonSize, pokemonSize);

                // 繪製卡片信息
                ctx.fillStyle = 'black';
                ctx.textAlign = 'center';
                ctx.font = 'bold 20px Arial';
                ctx.fillText(cardData.pokemon_name, width / 2, height * 0.15);
                ctx.font = '16px Arial';
                ctx.fillText(cardData.Rarity, width / 2, height * 0.85);
                let typeText = cardData.Type1;
                if (cardData.Type2) {
                    typeText += ' / ' + cardData.Type2;
                }
                ctx.fillText(typeText, width / 2, height * 0.9);

                console.log('Card drawn successfully:', cardData.pokemon_name);
                return canvas.toDataURL('image/png');
            } catch (error) {
                console.error('Error in drawCardDataURL:', error);
                throw error;
            }
        }

        // 輔助函數：載入圖片
        function loadImage(url) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.crossOrigin = 'Anonymous';
                img.onload = () => {
                    console.log('Image loaded successfully:', url);
                    resolve(img);
                };
                img.onerror = () => {
                    console.error('Failed to load image:', url);
                    // 只重新抽取這一張卡片
                    reDrawSingleCard()
                        .then(newCardData => {
                            // 使用新卡片數據重新加載圖片
                            return loadImage(newCardData.image_url);
                        })
                        .then(resolve)
                        .catch(reject);
                };
                img.src = url;
            });
        }

        // 新增重抽單張卡片的函數
        function reDrawSingleCard() {
            return fetch('pakage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    // 只返回第一張卡片的數據
                    return data.cards[0];
                });
        }

        // 添加重新抽卡函數
        function reDrawCards() {
            console.log('Redrawing cards due to image load failure');
            fetch('pakage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    window.cardData = data;
                    if (window.cardData && window.cardData.cards.length > 0) {
                        processAndDrawCards(window.cardData.cards);
                    }
                })
                .catch(error => {
                    console.error('Error redrawing cards:', error);
                });
        }

        // 將卡片處理邏輯抽取成單獨的函數
        function processAndDrawCards(cards) {
            console.log('開始處理卡片數據:', cards);

            // 獲取或創建結果容器
            let cardResults = document.getElementById('cardResults');
            if (!cardResults) {
                cardResults = document.createElement('div');
                cardResults.id = 'cardResults';
                cardResults.className = 'card-results';
                document.querySelector('main').appendChild(cardResults);
            }

            // 生成 HTML
            const htmlContent = `
                <h2>你抽到的卡片：</h2>
                <div class="card-container">
                    ${cards.map(card => `
                        <div class="card-item">
                            <div class="card-inner">
                                <div class="card-back">
                                    <img src="../images/card_back.png" alt="card back" width="250" height="350">
                                </div>
                                <div class="card-front">
                                    <canvas class="card-canvas" width="250" height="350"></canvas>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                <button class="collection-button">加入卡冊</button>
                <button class="draw-button">再抽一次</button>
            `;

            cardResults.innerHTML = htmlContent;

            // 處理卡片圖像
            cards.forEach((card, index) => {
                const canvas = document.querySelectorAll('.card-canvas')[index];
                const ctx = canvas.getContext('2d');

                // 載入卡片
                const loadCard = async () => {
                    try {
                        // 載入背景圖片
                        const backgroundImg = new Image();
                        const rarityConfig = backgroundConfig[card.Rarity] || backgroundConfig['Common'];
                        const randomFile = rarityConfig.files[Math.floor(Math.random() * rarityConfig.files.length)];
                        const backgroundPath = `${rarityConfig.path}${randomFile}`;

                        await new Promise((resolve, reject) => {
                            backgroundImg.onload = resolve;
                            backgroundImg.onerror = reject;
                            backgroundImg.src = backgroundPath;
                        });

                        // 載入寶可夢圖片
                        const pokemonImg = new Image();
                        const imagePath = card.image_url;

                        await new Promise((resolve, reject) => {
                            pokemonImg.onload = resolve;
                            pokemonImg.onerror = reject;
                            pokemonImg.src = imagePath;
                        });

                        // 繪製卡片
                        ctx.drawImage(backgroundImg, 0, 0, canvas.width, canvas.height);

                        // 計算寶可夢圖片的位置和大小
                        const pokemonSize = Math.min(canvas.width * 0.8, canvas.height * 0.6);
                        const x = (canvas.width - pokemonSize) / 2;
                        const y = canvas.height * 0.2;

                        ctx.drawImage(pokemonImg, x, y, pokemonSize, pokemonSize);

                        // 添加文字
                        ctx.fillStyle = 'black';
                        ctx.textAlign = 'center';

                        // 寶可夢名稱
                        ctx.font = 'bold 20px Arial';
                        ctx.fillText(card.Name, canvas.width / 2, canvas.height * 0.15);

                        // 稀有度
                        ctx.font = '16px Arial';
                        ctx.fillText(card.Rarity, canvas.width / 2, canvas.height * 0.85);

                        // 屬性
                        let typeText = card.Type1;
                        if (card.Type2) {
                            typeText += ' / ' + card.Type2;
                        }
                        ctx.fillText(typeText, canvas.width / 2, canvas.height * 0.9);

                    } catch (error) {
                        console.error('載入卡片時發生錯誤:', error);
                        ctx.fillStyle = 'red';
                        ctx.textAlign = 'center';
                        ctx.fillText('載入失敗', canvas.width / 2, canvas.height / 2);
                    }
                };

                loadCard();
            });

            // 添加卡片翻轉事件
            document.querySelectorAll('.card-item').forEach(card => {
                card.addEventListener('click', function () {
                    this.querySelector('.card-inner').classList.add('card-flipped');
                });
            });

            // 隱藏卡包容器
            const packContainer = document.getElementById('packContainer');
            if (packContainer) {
                packContainer.style.display = 'none';
            }
        }

        // 添加背景圖片配置
        const backgroundConfig = {
            'Common': {
                path: '../images/card_background/normal/',
                files: ['darkblue.png', 'darkgreen.png', 'lightblue.png', 'pink.png', 'red.png', 'tiffany.png', 'violet.png']
            },
            'Rare': {
                path: '../images/card_background/normal/',
                files: ['darkblue.png', 'darkgreen.png', 'lightblue.png', 'pink.png', 'red.png', 'tiffany.png', 'violet.png']
            },
            'Legendary': {
                path: '../images/card_background/rare/',
                files: ['blackblue.png', 'blue.png', 'bluepurple.png', 'colorful.png', 'metalpurple.png', 'pink.png', 'razer.png', 'red.png', 'silver.png', 'whiteblue.png']
            }
        };

        // 初始化卡片
        function initializeCards() {
            console.log('Initializing cards');
            const cardItems = document.querySelectorAll('.card-item');

            cardItems.forEach((cardItem, index) => {
                const cardInner = cardItem.querySelector('.card-inner');
                const canvas = cardItem.querySelector('.card-canvas');
                const ctx = canvas.getContext('2d');

                // 如果有對應的卡片數據
                if (cardDataList[index]) {
                    // 將預先生成的卡片圖像繪製到canvas上
                    const img = new Image();
                    img.onload = () => {
                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    };
                    img.src = cardDataList[index].dataUrl;

                    // 添加點擊事件來翻轉卡片
                    cardItem.addEventListener('click', function () {
                        if (!cardInner.classList.contains('card-flipped')) {
                            cardInner.classList.add('card-flipped');
                        }
                    });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // 定義抽卡函數
            function drawCard() {
                // 顯示確認對話框
                const userConfirmed = confirm('確定要花10皮卡幣抽卡嗎?');
                if (!userConfirmed) {
                    return;
                }

                // 發送 AJAX 請求
                fetch('pakage.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=draw_card'
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // 更新金幣顯示
                            document.getElementById('coin-amount').textContent = data.new_balance;

                            // 處理卡片顯示
                            window.cardData = data;
                            if (data.cards && data.cards.length > 0) {
                                const packContainer = document.getElementById('packContainer');
                                if (packContainer) {
                                    packContainer.style.display = 'none';
                                }
                                processAndDrawCards(data.cards);
                            }
                        } else {
                            alert(data.message || '抽卡失敗');
                        }
                    })
                    .catch(error => {
                        console.error('抽卡時發生錯誤:', error);
                        alert('發生錯誤，請稍後再試');
                    });
            }

            // 綁定加入卡冊按鈕事件
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('collection-button')) {
                    // 獲取所有卡片數據
                    const cards = window.cardData.cards.map(card => ({
                        name: card.Name,
                        rarity: card.Rarity,
                        type1: card.Type1,
                        type2: card.Type2 || null,
                        image_url: card.image_url,
                        background_image_url: `${backgroundConfig[card.Rarity].path}${backgroundConfig[card.Rarity].files[Math.floor(Math.random() * backgroundConfig[card.Rarity].files.length)]}`,
                        Ability: card.Ability || null,
                        ability_description: card.Description || null
                    }));

                    // 發送到 booklet_add.php 而不是 user_cards
                    fetch('../php/booklet_add.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(cards)
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('成功加入卡冊！');
                            } else {
                                alert('加入卡冊失敗：' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('加入卡冊時發生錯誤:', error);
                            alert('發生錯誤，請稍後再試');
                        });
                }

                // 修改再抽一次按鈕的事件處理
                if (e.target.classList.contains('draw-button')) {
                    e.preventDefault();
                    drawCard(); // 直接調用抽卡函數
                }
            });

            // 為卡包按鈕添加點擊事件
            const packButtons = document.querySelectorAll('.pack-button');
            packButtons.forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    drawCard(); // 直接調用抽卡函數
                });
            });
        });

        // 全局變數存放卡片數據
        let cardDataList = [];
    </script>


    <style>
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin: 20px 0;
            min-height: 400px;
            /* 確保容器有足夠高度 */
        }

        .card-item {
            width: 250px;
            height: 350px;
            perspective: 1000px;
            cursor: pointer;
            z-index: 1;
        }

        .card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.6s;
            transform-style: preserve-3d;
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
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: white;
            /* 添加背景色 */
        }

        .card-back img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }

        .card-front {
            transform: rotateY(180deg);
        }

        .card-canvas {
            width: 100%;
            height: 100%;
            border-radius: 10px;
            display: block;
            /* 確保 canvas 正確顯示 */
        }

        .card-info {
            position: absolute;
            bottom: 10px;
            left: 0;
            width: 100%;
            text-align: center;
            background: rgba(255, 255, 255, 0.9);
            padding: 5px;
            border-radius: 0 0 10px 10px;
        }

        .card-name,
        .card-rarity,
        .card-type {
            margin: 5px 0;
            color: black;
            /* 確保文字顏色可見 */
        }

        .draw-button,
        .collection-button {
            margin: 20px 10px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
        }

        .draw-button:hover,
        .collection-button:hover {
            background-color: #45a049;
        }

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
            transform: scale(1.05);
        }

        .pack-button img {
            width: 300px;
            height: auto;
        }

        .card-inner {
            position: relative;
            width: 250px;
            height: 350px;
            transition: transform 0.6s;
            transform-style: preserve-3d;
            cursor: pointer;
        }

        .card-back,
        .card-front {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
        }

        .card-front {
            transform: rotateY(180deg);
            background-color: white;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card-inner.card-flipped {
            transform: rotateY(180deg);
        }

        .card-canvas {
            width: 100%;
            height: 100%;
            display: block;
        }

        .card-info {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.9);
        }

        .card-back img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .pack-container {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin: 50px 0;
        }

        .card-results {
            text-align: center;
            margin: 20px auto;
            max-width: 1200px;
        }

        .draw-button,
        .collection-button {
            margin: 20px 10px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }

        .card-canvas {
            width: 100%;
            height: 100%;
            border-radius: 10px;
            display: block;
        }

        .card-front {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .card-inner {
            transition: transform 0.6s;
            transform-style: preserve-3d;
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
        }

        .card-front {
            transform: rotateY(180deg);
        }
    </style>
    <script src="../js/pakage.js"></script>
</body>

</html>