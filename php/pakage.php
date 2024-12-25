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

    // 如果是 AJAX 請求，返回 JSON 數據
    if (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
    ) {
        header('Content-Type: application/json');
        echo json_encode([
            'cards' => $random_cards,
            'backgrounds' => [
                'Common' => '../images/card_background/normal/',
                'Rare' => '../images/card_background/rare/',
                'Legendary' => '../images/card_background/rare/'
            ]
        ]);
        exit;
    }

    // 在 PHP 部分添加處理加入卡冊的 AJAX 請求
    if (isset($_POST['action']) && $_POST['action'] === 'add_to_booklet') {
        $user_id = $_SESSION['user_id'];
        $cards = json_decode($_POST['cards'], true);

        $success = true;
        $message = '';

        foreach ($cards as $card) {
            $stmt = $conn->prepare("INSERT INTO booklet (user_id, pokemon_name, Rarity, Type1, Type2, image_url, background_image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");

            // 使用卡片中保存的實際背景路徑
            $background_path = $card['background_image_url'];

            $stmt->bind_param(
                "issssss",
                $user_id,
                $card['Name'],
                $card['Rarity'],
                $card['Type1'],
                $card['Type2'],
                $card['image_url'],
                $background_path
            );

            if (!$stmt->execute()) {
                $success = false;
                $message = $stmt->error;
                break;
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
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
        // 隨機選擇背景圖片
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
                console.log('創建新的 cardResults 容器');
                cardResults = document.createElement('div');
                cardResults.id = 'cardResults';
                cardResults.className = 'card-results';
                document.querySelector('main').appendChild(cardResults);
            }

            // 確保容器可見
            cardResults.style.display = 'block';
            cardResults.style.visibility = 'visible';
            cardResults.style.opacity = '1';

            // 隱藏卡包容器
            const packContainer = document.getElementById('packContainer');
            if (packContainer) {
                packContainer.style.display = 'none';
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

            console.log('準備插入的 HTML:', htmlContent);
            cardResults.innerHTML = htmlContent;
            console.log('HTML 已插入');

            // 處理卡片圖像
            cards.forEach((cardData, index) => {
                console.log('處理卡片:', cardData);
                const canvas = document.querySelectorAll('.card-canvas')[index];
                const ctx = canvas.getContext('2d');

                // 載入背景圖片
                const loadCard = () => {
                    const backgroundImg = new Image();
                    backgroundImg.onerror = () => {
                        console.error('背景圖片載入失敗:', cardData.Rarity);
                        // 重試載入背景
                        setTimeout(loadCard, 500);
                    };

                    backgroundImg.onload = () => {
                        ctx.drawImage(backgroundImg, 0, 0, canvas.width, canvas.height);

                        // 載入寶可夢圖片
                        const pokemonImg = new Image();
                        pokemonImg.onerror = () => {
                            console.error('寶可夢圖片載入失敗:', cardData.Name);
                            // 重試載入寶可夢圖片
                            setTimeout(loadCard, 500);
                        };

                        pokemonImg.onload = () => {
                            // 清除畫布
                            ctx.clearRect(0, 0, canvas.width, canvas.height);
                            // 重新繪製背景
                            ctx.drawImage(backgroundImg, 0, 0, canvas.width, canvas.height);

                            // 計算寶可夢圖片的位置和大小
                            const pokemonSize = Math.min(canvas.width * 0.8, canvas.height * 0.6);
                            const x = (canvas.width - pokemonSize) / 2;
                            const y = canvas.height * 0.2;

                            // 繪製寶可夢圖片
                            ctx.drawImage(pokemonImg, x, y, pokemonSize, pokemonSize);

                            // 添加文字信息
                            ctx.fillStyle = 'black';
                            ctx.textAlign = 'center';

                            // 寶可夢名稱
                            ctx.font = 'bold 20px Arial';
                            ctx.fillText(cardData.Name, canvas.width / 2, canvas.height * 0.15);

                            // 稀有度
                            ctx.font = '16px Arial';
                            ctx.fillText(cardData.Rarity, canvas.width / 2, canvas.height * 0.85);

                            // 屬性
                            let typeText = cardData.Type1;
                            if (cardData.Type2) {
                                typeText += ' / ' + cardData.Type2;
                            }
                            ctx.fillText(typeText, canvas.width / 2, canvas.height * 0.9);

                            console.log(`卡片 ${cardData.Name} 繪製完成`);

                            // 保存背景圖片路徑到卡片數據中
                            cardData.background_image_url = backgroundPath;
                        };

                        // 確保圖片路徑正確
                        const imagePath = cardData.image_url.startsWith('../')
                            ? cardData.image_url
                            : `../images/pokemon_images/${cardData.Name.toLowerCase()}.png`;

                        console.log('嘗試載入寶可夢圖片:', imagePath);
                        pokemonImg.src = imagePath;
                    };

                    // 根據稀有度選擇背景
                    const rarityBackground = window.cardData.backgrounds[cardData.Rarity];
                    const backgroundColors = ['red', 'blue', 'green', 'purple', 'tiffany', 'darkgreen', 'darkblue', 'violet'];
                    const randomColor = backgroundColors[Math.floor(Math.random() * backgroundColors.length)];
                    const backgroundPath = `${rarityBackground}${randomColor}.png`;

                    console.log('嘗試載入背景圖片:', backgroundPath);
                    backgroundImg.src = backgroundPath;
                };

                // 開始載入卡片
                loadCard();
            });

            // 添加卡片翻轉事件
            document.querySelectorAll('.card-item').forEach(card => {
                card.addEventListener('click', function () {
                    console.log('卡片被點擊');
                    this.querySelector('.card-inner').classList.add('card-flipped');
                });
            });
        }

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
            // 綁定加入卡冊按鈕事件
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('collection-button')) {
                    const cards = window.cardData.cards;

                    fetch('pakage.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=add_to_booklet&cards=${encodeURIComponent(JSON.stringify(cards))}`
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
                    e.preventDefault(); // 防止表單提交

                    // 隱藏當前卡片結果
                    const cardResults = document.getElementById('cardResults');
                    if (cardResults) {
                        cardResults.style.display = 'none';
                    }

                    // 顯示卡包容器
                    const packContainer = document.getElementById('packContainer');
                    if (packContainer) {
                        packContainer.style.display = 'flex';
                    }

                    // 重置卡包按鈕狀態
                    const packButtons = document.querySelectorAll('.pack-button');
                    packButtons.forEach(button => {
                        button.disabled = false;
                    });
                }
            });

            // 為卡包按鈕添加點擊事件
            const packButtons = document.querySelectorAll('.pack-button');
            packButtons.forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();

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
                            if (data.cards && data.cards.length > 0) {
                                // 隱藏卡包容器
                                const packContainer = document.getElementById('packContainer');
                                if (packContainer) {
                                    packContainer.style.display = 'none';
                                }

                                processAndDrawCards(data.cards);
                            }
                        })
                        .catch(error => {
                            console.error('抽卡時發生錯誤:', error);
                            alert('發生錯誤，請稍後再試');
                        });
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
</body>

</html>