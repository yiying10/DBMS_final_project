<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 資料庫連線
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("資料庫連接失敗：" . $conn->connect_error);
}

// 取得用戶的卡冊內容
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM booklet WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cards = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8" />
    <title>我的卡冊</title>
    <link rel="stylesheet" href="../css/styles.css" />
    <link rel="stylesheet" href="../css/booklet.css" />
</head>

<body data-page="booklet">
    <!-- 側邊欄 -->
    <nav class="sidebar">
        <ul>
            <li><a href="../php/home.php">首頁</a></li>
            <li><a href="../php/generate.php">卡牌生成區</a></li>
            <li><a href="../php/illustrated_book.php">卡牌圖鑑</a></li>
            <li><a href="../php/pakage.php" id="pakage-link">抽卡區</a></li>
            <li><a href="../php/reference.php">關於我們</a></li>
            <li><a href="../php/booklet.php" id="booklet-link">我的卡冊</a></li>
        </ul>
    </nav>

    <main class="content">
        <h1>我的卡冊</h1>

        <!-- 卡片列表容器 -->
        <div class="card-container">
            <?php foreach ($cards as $card): ?>
                <div 
                     class="card-item" 
                     data-card-id="<?php echo $card['id']; ?>"
                     onclick="viewCard(
                         '<?php echo htmlspecialchars($card['image_url']); ?>',
                         '<?php echo htmlspecialchars($card['pokemon_name']); ?>',
                         '<?php echo htmlspecialchars($card['rarity']); ?>',
                         '<?php echo htmlspecialchars($card['type1']); ?>',
                         '<?php echo htmlspecialchars($card['type2']); ?>'
                     )"
                >
                    <img 
                         src="<?php echo htmlspecialchars($card['image_url']); ?>"
                         alt="<?php echo htmlspecialchars($card['pokemon_name']); ?>" 
                         class="card-image" 
                    />
                    <div class="card-info">
                        <p class="card-name"><?php echo htmlspecialchars($card['pokemon_name']); ?></p>
                        <p class="card-rarity"><?php echo htmlspecialchars($card['rarity']); ?></p>
                        <p class="card-type">
                            <?php 
                                echo htmlspecialchars($card['type1']); 
                                echo $card['type2'] ? "/ " . htmlspecialchars($card['type2']) : ""; 
                            ?>
                        </p>
                        <!-- 移除卡片按鈕：需要阻止冒泡，避免點到 card-item 時也觸發 onclick -->
                        <button class="remove-button" onclick="event.stopPropagation(); removeCard(<?php echo $card['id']; ?>)">
                            移除
                        </button>
                        <!-- 下載卡片按鈕（此範例只是直接下載圖片） -->
                        <button class="download-button" onclick="event.stopPropagation(); downloadCard('<?php echo htmlspecialchars($card['image_url']); ?>')">
                            下載
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 卡片預覽區域 -->
        <div class="preview-container">
            <h2>卡片預覽</h2>
            <div class="preview-card">
                <img src="" alt="卡片預覽" class="preview-image" />
                <div class="preview-info">
                    <p class="preview-name"></p>
                    <p class="preview-rarity"></p>
                    <p class="preview-type"></p>
                </div>
            </div>
        </div>
    </main>

    <style>
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-item {
            width: 150px;
            cursor: pointer;
            border: 1px solid #ccc;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .card-item:hover {
            transform: scale(1.05);
        }
        .card-image {
            width: 100%;
            height: auto;
        }
        .remove-button,
        .download-button {
            margin-top: 5px;
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .remove-button:hover,
        .download-button:hover {
            background-color: #0056b3;
        }
        .preview-container {
            margin-top: 20px;
        }
        .preview-card {
            display: flex;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
            width: 400px;
            padding: 10px;
        }
        .preview-image {
            width: 150px;
            height: auto;
        }
        .preview-info {
            margin-left: 20px;
        }
        .preview-name,
        .preview-rarity,
        .preview-type {
            margin: 0;
        }
    </style>

    <script>
        /**
         * 點擊卡片時，更新右側「卡片預覽」區域
         * @param {string} imageUrl  寶可夢圖片路徑
         * @param {string} name      寶可夢名稱
         * @param {string} rarity    稀有度
         * @param {string} type1     屬性1
         * @param {string} type2     屬性2
         */
        function viewCard(imageUrl, name, rarity, type1, type2) {
            const previewImage = document.querySelector('.preview-image');
            const previewName = document.querySelector('.preview-name');
            const previewRarity = document.querySelector('.preview-rarity');
            const previewType = document.querySelector('.preview-type');

            // 更新預覽圖
            previewImage.src = imageUrl;

            // 更新文字資訊
            previewName.textContent = name;
            previewRarity.textContent = rarity;

            // 如果有第二屬性，就用 "/ " 連接
            const types = type2 ? `${type1} / ${type2}` : type1;
            previewType.textContent = types;
        }

        /**
         * 從卡冊移除指定 card_id 的卡
         * @param {number} cardId
         */
        function removeCard(cardId) {
            if (!confirm('確定要移除這張卡片嗎？')) {
                return;
            }
            // 這裡以 fetch 方式呼叫後端 PHP (見 booklet_remove.php)
            fetch('../php/booklet_remove.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ card_id: cardId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('卡片已移除！');
                    // 將畫面上的卡片 DOM 刪除
                    const cardItem = document.querySelector(`.card-item[data-card-id="${cardId}"]`);
                    if (cardItem) {
                        cardItem.remove();
                    }
                } else {
                    alert('移除卡片失敗：' + data.message);
                }
            })
            .catch(err => {
                console.error('移除卡片時發生錯誤', err);
                alert('移除卡片時發生錯誤');
            });
        }

        /**
         * 下載卡片（簡易示範：直接下載該寶可夢圖。若需下載 Canvas，請改到 generate.php）
         * @param {string} imageUrl
         */
        function downloadCard(imageUrl) {
            const link = document.createElement('a');
            link.href = imageUrl;
            link.download = 'my_pokemon.png'; // 檔名可自行決定
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
