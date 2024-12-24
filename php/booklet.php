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

// 將每個寶可夢名稱的第一個字母改成大寫
foreach ($cards as &$card) {
    $card['pokemon_name'] = ucfirst($card['pokemon_name']);
}
unset($card); // 解除引用

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

<script>
    /**
     * 從卡冊移除指定 card_id 的卡
     */
    function removeCard(cardId) {
        if (!confirm('確定要移除這張卡片嗎？')) {
            return;
        }
        fetch('../php/booklet_remove.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ card_id: cardId })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('卡片已移除！');
                    const cardItem = document.querySelector(`.card-item[data-card-id="${cardId}"]`);
                    if (cardItem) cardItem.remove();
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
     * 點擊卡片時，生成並繪製卡片到 Canvas（等比例縮放字體）
     */
    function generateCard(imageUrl, name, rarity, type1, type2, background_image_url) {
        const pokemonData = {
            name,
            rarity,
            type1,
            type2,
            ability: {
                name: "",
                description: ""
            }
        };

        const canvas = document.getElementById('cardCanvas');
        if (!canvas) {
            console.error('找不到 #cardCanvas');
            return;
        }
        const ctx = canvas.getContext('2d');

        // 寶可夢圖片檔名
        const displayName = pokemonData.name;
        const imageFileName = displayName.toLowerCase().replace(/\s+/g, '-');
        const pokemonImagePath = `../images/pokemon_images/${imageFileName}.png`;

        // 載入背景圖
        const bgImg = new Image();
        bgImg.crossOrigin = "Anonymous";
        bgImg.onerror = function () {
            console.error('背景圖片載入失敗:', background_image_url);
        };
        bgImg.onload = function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // 外層圓角
            const outerRadius = Math.min(canvas.width, canvas.height) * 0.04;
            roundRect(ctx, 0, 0, canvas.width, canvas.height, outerRadius);
            ctx.save();
            ctx.clip();
            ctx.drawImage(bgImg, 0, 0, canvas.width, canvas.height);
            ctx.restore();

            // 內層白底，邊距固定取最短邊的 6%
            const margin = Math.min(canvas.width, canvas.height) * 0.06;
            const innerW = canvas.width - margin * 2;
            const innerH = canvas.height - margin * 2;
            const innerRadius = Math.min(canvas.width, canvas.height) * 0.03;
            roundRect(ctx, margin, margin, innerW, innerH, innerRadius);
            ctx.save();
            ctx.fillStyle = 'rgba(255, 255, 255, 0.98)';
            ctx.fill();
            ctx.restore();

            // 檢查寶可夢圖片
            fetch(pokemonImagePath)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('找不到指定寶可夢圖片：' + pokemonImagePath);
                    }
                    if (!pokemonData.rarity) {
                        throw new Error('No rarity defined');
                    }
                    return true;
                })
                .then(() => {
                    const pokemonImg = new Image();
                    pokemonImg.crossOrigin = "Anonymous";
                    pokemonImg.onload = function () {
                        // 繪製寶可夢
                        const pokeX = canvas.width * 0.16;
                        const pokeY = canvas.height * 0.15;
                        const pokeW = canvas.width * 0.68;
                        const pokeH = canvas.height * 0.35;
                        ctx.drawImage(pokemonImg, pokeX, pokeY, pokeW, pokeH);

                        // 繪製文字
                        drawCardText(ctx, canvas, pokemonData, pokeX, pokeY, pokeW, pokeH);
                    };
                    pokemonImg.src = pokemonImagePath;
                })
                .catch(err => {
                    console.log('跳過卡片生成:', err.message);
                });
        };
        bgImg.src = background_image_url;
    }

    /**
     * 繪製文字 (等比例字體)
     */
    function drawCardText(ctx, canvas, pokemonData, pokeX, pokeY, pokeW, pokeH) {
        // 名稱擺在寶可夢下方 + 一些間距
        let y = pokeY + pokeH + canvas.height * 0.05;

        // 寶可夢名稱
        ctx.textAlign = 'left';
        ctx.fillStyle = '#333';
        ctx.font = `bold ${canvas.width * 0.08}px "Cinzel", "Noto Sans TC", serif`;
        let xName = canvas.width * 0.18;
        ctx.fillText(pokemonData.name, xName, y);

        // 分隔線
        y += canvas.height * 0.02;
        ctx.beginPath();
        ctx.strokeStyle = '#ddd';
        ctx.lineWidth = 1;
        ctx.moveTo(xName, y);
        ctx.lineTo(canvas.width * 0.82, y);
        ctx.stroke();

        // 稀有度
        y += canvas.height * 0.04;
        ctx.font = `${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
        ctx.fillStyle = '#333';
        ctx.fillText('稀有度', xName, y);

        ctx.font = `bold ${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
        ctx.fillText(pokemonData.rarity || '', xName + canvas.width * 0.15, y);

        // 屬性
        if (pokemonData.type1) {
            y += canvas.height * 0.04;
            ctx.font = `${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
            ctx.fillText('屬性', xName, y);

            ctx.font = `bold ${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
            const types = pokemonData.type2
                ? `${pokemonData.type1} / ${pokemonData.type2}`
                : pokemonData.type1;
            ctx.fillText(types, xName + canvas.width * 0.15, y);
        }

        // 若有特性（這裡是空的示範）
        if (pokemonData.ability && pokemonData.ability.name) {
            y += canvas.height * 0.04;
            ctx.font = `${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
            ctx.fillText('特性', xName, y);

            ctx.font = `bold ${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
            ctx.fillText(pokemonData.ability.name, xName + canvas.width * 0.15, y);

            // 如果有描述
            if (pokemonData.ability.description) {
                y += canvas.height * 0.04;
                ctx.font = `${canvas.width * 0.04}px "Cinzel", "Noto Sans TC", serif`;
                const maxWidth = canvas.width * 0.64;
                wrapText(ctx, pokemonData.ability.description, xName, y, maxWidth, canvas.height * 0.05);
            }
        }
    }

    /**
     * 圓角矩形
     */
    function roundRect(ctx, x, y, width, height, radius) {
        ctx.beginPath();
        ctx.moveTo(x + radius, y);
        ctx.lineTo(x + width - radius, y);
        ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
        ctx.lineTo(x + width, y + height - radius);
        ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
        ctx.lineTo(x + radius, y + height);
        ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
        ctx.lineTo(x, y + radius);
        ctx.quadraticCurveTo(x, y, x + radius, y);
        ctx.closePath();
    }

    /**
     * 自動換行：以「每字」為單位 (可自行改成以空白分隔)
     */
    function wrapText(ctx, text, x, y, maxWidth, lineHeight) {
        const words = text.split('');
        let line = '';
        for (let n = 0; n < words.length; n++) {
            const testLine = line + words[n];
            const metrics = ctx.measureText(testLine);
            const testWidth = metrics.width;
            if (testWidth > maxWidth && n > 0) {
                ctx.fillText(line, x, y);
                line = words[n];
                y += lineHeight;
            } else {
                line = testLine;
            }
        }
        ctx.fillText(line, x, y);
    }

    /**
     * 下載卡片 (Canvas 上的內容)
     */
    function downloadCard(imageUrl, background_image_url) {
        const canvas = document.getElementById('cardCanvas');
        if (!canvas) {
            console.error('找不到 #cardCanvas');
            return;
        }
        try {
            const fileName = `${imageUrl.split('/').pop().split('.')[0].charAt(0).toUpperCase() +
                imageUrl.split('/').pop().split('.')[0].slice(1)
                }_${background_image_url.split('/').pop().split('.')[0]}.png`;

            const dataURL = canvas.toDataURL('image/png', 1.0);
            const link = document.createElement('a');
            link.download = fileName;
            link.href = dataURL;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } catch (error) {
            console.error('下載過程發生錯誤:', error);
            alert('下載失敗: ' + error.message);
        }
    }
</script>

<body data-page="booklet">
    <!-- 側邊欄 -->
    <nav class="sidebar">
        <ul>
            <li><a href="../php/home.php">首頁</a></li>
            <li><a href="../php/generate.php">卡牌生成區</a></li>
            <li><a href="../php/illustrated_book.php">卡牌圖鑑</a></li>
            <li><a href="../php/pakage.php" id="pakage-link">抽卡區</a></li>
            <li><a href="../php/booklet.php" id="booklet-link">我的卡冊</a></li>
            <li><a href="../php/forum.php" id="forum-link">論壇</a></li>
            <li><a href="../php/reference.php">關於我們</a></li>
        </ul>
    </nav>

    <main class="content">
        <h1>我的卡冊</h1>

        <!-- 卡片列表容器 -->
        <div class="card-container">
            <?php foreach ($cards as $card): ?>
                <div class="card-item" data-card-id="<?php echo $card['id']; ?>" onclick="generateCard(
                         '<?php echo htmlspecialchars($card['image_url']); ?>',
                         '<?php echo htmlspecialchars($card['pokemon_name']); ?>',
                         '<?php echo htmlspecialchars($card['rarity']); ?>',
                         '<?php echo htmlspecialchars($card['type1']); ?>',
                         '<?php echo htmlspecialchars($card['type2']); ?>',
                         '<?php echo htmlspecialchars($card['background_image_url']); ?>'
                     )">
                    <img src="<?php echo htmlspecialchars($card['image_url']); ?>"
                        alt="<?php echo htmlspecialchars($card['pokemon_name']); ?>" class="card-image" />
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
                        <button class="remove-button"
                            onclick="event.stopPropagation(); removeCard(<?php echo $card['id']; ?>)">
                            移除
                        </button>
                        <!-- 下載卡片按鈕：下載 Canvas 上繪製好的卡片 -->
                        <button class="download-button"
                            onclick="event.stopPropagation(); downloadCard('<?php echo htmlspecialchars($card['image_url']); ?>','<?php echo htmlspecialchars($card['background_image_url']); ?>')">
                            下載
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Canvas：請酌情調整大小 (越大看起來越清晰) -->
        <canvas id="cardCanvas" width="250" height="380" style="border: 1px solid #ccc; margin-top: 20px;"></canvas>

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

        .card-info {
            padding: 5px;
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
    </style>

</body>

</html>