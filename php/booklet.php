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

// 若想將寶可夢名稱的第一個字母改成大寫，可保留
foreach ($cards as &$card) {
    $card['pokemon_name'] = ucfirst($card['pokemon_name']);
}
unset($card);


?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8" />
    <title>我的卡冊</title>
    <link rel="stylesheet" href="../css/styles.css" />
    <link rel="stylesheet" href="../css/booklet.css" />

    <style>
        /* 縮圖容器 */
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .card-item {
            width: 170px;
            cursor: pointer;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
            background-color: #fff;
            padding: 5px;
        }

        .card-item:hover {
            transform: scale(1.04);
        }

        .card-image {
            width: 100%;
            height: auto;
        }

        .card-name {
            font-weight: bold;
        }

        /* 彈跳視窗 */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            /* 預設隱藏 */
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            min-width: 320px;
            max-width: 600px;
            position: relative;
            text-align: center;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 18px;
            cursor: pointer;
            background: none;
            border: none;
        }

        .modal-image {
            width: 100%;
            height: auto;
            border: 1px solid #ccc;
            border-radius: 8px;
            max-height: 700px;
        }

        .modal-info {
            margin-top: 10px;
        }

        .modal-actions {
            margin-top: 15px;
        }

        .remove-button,
        .download-button {
            margin: 0 10px;
            padding: 6px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .remove-button:hover,
        .download-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body data-page="booklet">
    <!-- 側邊欄 -->
    <nav class="sidebar">
        <ul>
            <li><a href="../php/home.php">首頁</a></li>
            <li><a href="../php/generate.php">卡牌生成區</a></li>
            <li><a href="../php/illustrated_book.php">卡牌圖鑑</a></li>
            <li><a href="../php/pakage.php" id="pakage-link">抽卡區</a></li>
            <li><a href="../php/booklet.php" id="booklet-link">我的卡冊</a></li>
            <li><a href="../php/reference.php">關於我們</a></li>
        </ul>
    </nav>

    <main class="content">
        <h1>我的卡冊</h1>
        <!-- 縮圖容器 -->
        <div class="card-container" id="cardContainer"></div>
    </main>

    <!-- 彈跳視窗（顯示放大卡牌） -->
    <div class="modal-overlay" id="cardModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeCardModal()">✕</button>
            <img id="modalImage" class="modal-image" src="" alt="放大卡牌圖" />
            <div class="modal-info">
                <p id="modalName"></p>
                <p id="modalRarity"></p>
                <p id="modalType"></p>
            </div>
            <div class="modal-actions">
                <button class="download-button" id="modalDownloadBtn">下載</button>
                <button class="remove-button" id="modalRemoveBtn">移除</button>
            </div>
        </div>
    </div>

    <script>
        // 從 PHP 取得卡片資料
        const phpCards = <?php echo json_encode($cards, JSON_UNESCAPED_UNICODE); ?>;
        console.log('PHP Cards:', phpCards);
        // 用來存放繪製好的 DataURL (縮圖 + 大圖)
        let cardDataList = [];

        // 頁面載入後開始繪製
        window.onload = function () {
            drawAllCards(phpCards);
        };

        /**
         * 為所有卡片生成「縮圖」「大圖」，並顯示在畫面上
         */
        function drawAllCards(cards) {
            const promises = cards.map(card => createBothImages(card));
            // 等全部繪製完成，再來把 DOM 建立好
            Promise.all(promises).then(results => {
                cardDataList = results; // results是一個陣列，每個元素都是 {...card, smallImage, largeImage}
                // 建立縮圖 DOM
                results.forEach(data => createCardItemDOM(data));
            }).catch(err => {
                console.error('繪製卡片時發生錯誤：', err);
            });
        }

        /**
         * 同時建立「縮圖(250x350)」與「放大圖(500x700)」
         */
        function createBothImages(card) {
            return Promise.all([
                drawCardDataURL(card, 250, 350),
                drawCardDataURL(card, 500, 700)
            ]).then(([smallImage, largeImage]) => {
                return {
                    ...card,
                    smallImage,
                    largeImage
                };
            });
        }

        /**
         * 繪製單張卡，並回傳該 Canvas 的 DataURL
         * 為避免彼此干擾，每次都用新的 Canvas
         */
        function drawCardDataURL(card, cWidth, cHeight) {
            return new Promise((resolve, reject) => {
                const canvas = document.createElement('canvas');
                canvas.width = cWidth;
                canvas.height = cHeight;
                const ctx = canvas.getContext('2d');

                const { pokemon_name, Rarity, Type1, Type2, image_url, background_image_url } = card;

                // 讀取背景
                const bgImg = new Image();
                bgImg.crossOrigin = 'anonymous';
                bgImg.onerror = () => reject(`無法載入背景：${background_image_url}`);
                bgImg.onload = () => {
                    // 清空
                    ctx.clearRect(0, 0, cWidth, cHeight);

                    // 畫背景 + 圓角
                    roundRect(ctx, 0, 0, cWidth, cHeight, 25);
                    ctx.save();
                    ctx.clip();
                    ctx.drawImage(bgImg, 0, 0, cWidth, cHeight);
                    ctx.restore();

                    // 白色內框
                    const margin = 20;
                    roundRect(ctx, margin, margin, cWidth - margin * 2, cHeight - margin * 2, 15);
                    ctx.save();
                    ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
                    ctx.fill();
                    ctx.restore();

                    // 讀取寶可夢圖片
                    fetch(image_url)
                        .then(response => {
                            if (!response.ok) throw new Error('寶可夢圖片不存在：' + image_url);
                            return true;
                        })
                        .then(() => {
                            const pokeImg = new Image();
                            pokeImg.crossOrigin = 'anonymous';
                            pokeImg.onload = () => {
                                // 畫寶可夢
                                const pokeW = cWidth * 0.6;
                                const pokeH = cHeight * 0.4;
                                const pokeX = (cWidth - pokeW) / 2;
                                const pokeY = margin + 10;
                                ctx.drawImage(pokeImg, pokeX, pokeY, pokeW, pokeH);

                                // 畫文字
                                drawCardText(ctx, cWidth, cHeight, card, pokeX, pokeY + pokeH);
                                // 回傳 DataURL
                                const url = canvas.toDataURL('image/png');
                                resolve(url);
                            };
                            pokeImg.onerror = () => reject(`無法載入寶可夢：${image_url}`);
                            pokeImg.src = image_url;
                        })
                        .catch(err => reject(err.message));
                };
                bgImg.src = background_image_url;
            });
        }

        /**
         * 在 Canvas 上繪製文字
         */
        function drawCardText(ctx, w, h, card, baseX, baseY) {
            const fontSizeTitle = w * 0.08;
            const fontSizeLabel = w * 0.05;
            const marginTop = h * 0.02;
            let y = baseX + pokeH + h * 0.05;
            // 卡名
            ctx.textAlign = 'left';
            ctx.fillStyle = '#333';
            ctx.font = `bold ${fontSizeTitle}px sans-serif`;
            let textY = baseY + marginTop + fontSizeTitle;
            ctx.fillText(card.pokemon_name, w / 2, textY);

            // 分隔線
            textY += marginTop;
            ctx.beginPath();
            ctx.strokeStyle = '#aaa';
            ctx.moveTo(w * 0.2, textY);
            ctx.lineTo(w * 0.8, textY);
            ctx.stroke();

            // 稀有度
            textY += marginTop + fontSizeLabel;
            ctx.font = `${fontSizeLabel}px sans-serif`;
            ctx.fillStyle = '#555';
            ctx.fillText(`稀有度: ${card.Rarity}`, w / 2, textY);

            // 屬性
            if (card.Type1) {
                textY += marginTop + fontSizeLabel;
                const t = card.Type2 ? `${card.Type1} / ${card.Type2}` : card.Type1;
                ctx.fillText(`屬性: ${t}`, w / 2, textY);
            }
        }

        /**
         * 畫出圓角矩形
         */
        function roundRect(ctx, x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + w - r, y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + r);
            ctx.lineTo(x + w, y + h - r);
            ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
            ctx.lineTo(x + r, y + h);
            ctx.quadraticCurveTo(x, y + h, x, y + h - r);
            ctx.lineTo(x, y + r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
        }

        /**
         * 建立「縮圖」的卡 DOM，放到頁面上
         * @param {*} cardObj  包含 smallImage, largeImage
         */
        function createCardItemDOM(cardObj) {
            const container = document.getElementById('cardContainer');
            const div = document.createElement('div');
            div.className = 'card-item';
            div.dataset.cardId = cardObj.card_id;

            // 縮圖
            const img = document.createElement('img');
            img.className = 'card-image';
            img.src = cardObj.smallImage;
            img.alt = cardObj.pokemon_name;

            // 名稱
            const pName = document.createElement('p');
            pName.className = 'card-name';
            pName.textContent = cardObj.pokemon_name;

            // 點擊時打開彈窗
            div.addEventListener('click', () => {
                openCardModal(cardObj.card_id);
            });

            div.appendChild(img);
            div.appendChild(pName);
            container.appendChild(div);
        }

        /**
         * 打開 Modal，顯示放大圖
         */
        function openCardModal(cardId) {
            console.log('Opening modal for card_id:', cardId);
            console.log('Available cards:', cardDataList);

            const cardObj = cardDataList.find(c => c.card_id === cardId);
            if (!cardObj) {
                console.error('找不到卡片:', cardId);
                return;
            }

            const modal = document.getElementById('cardModal');
            modal.classList.add('active');

            // 更新彈窗內容
            document.getElementById('modalImage').src = cardObj.largeImage;
            document.getElementById('modalName').textContent = cardObj.pokemon_name;
            document.getElementById('modalRarity').textContent = '稀有度: ' + cardObj.Rarity;
            let t = cardObj.Type2 ? `${cardObj.Type1} / ${cardObj.Type2}` : cardObj.Type1;
            document.getElementById('modalType').textContent = '屬性: ' + t;

            // 綁定下載/移除按鈕
            document.getElementById('modalDownloadBtn').onclick = () => downloadCard(cardObj);
            document.getElementById('modalRemoveBtn').onclick = () => removeCard(cardObj.card_id);
        }

        /**
         * 關閉 Modal
         */
        function closeCardModal() {
            document.getElementById('cardModal').classList.remove('active');
        }
        // 點擊背景關閉
        document.getElementById('cardModal').addEventListener('click', e => {
            if (e.target.id === 'cardModal') {
                closeCardModal();
            }
        });

        /**
         * 下載放大圖
         */
        function downloadCard(cardObj) {
            try {
                const link = document.createElement('a');
                link.href = cardObj.largeImage;
                const safeName = cardObj.pokemon_name.replace(/\s+/g, '_');
                link.download = safeName + '.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (err) {
                alert('下載失敗: ' + err);
            }
        }

        /**
         * 從卡冊移除
         */
        function removeCard(cardId) {
            if (!confirm('確定要移除這張卡片嗎？')) return;

            console.log('Removing card_id:', cardId);

            fetch('../php/booklet_remove.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ card_id: cardId })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // 從 cardDataList 中移除該卡片
                        cardDataList = cardDataList.filter(card => card.card_id !== cardId);

                        // 移除 DOM
                        const item = document.querySelector(`.card-item[data-card-id="${cardId}"]`);
                        if (item) item.remove();

                        // 關閉 Modal
                        closeCardModal();

                        alert('卡片已移除！');
                    } else {
                        alert('移除卡片失敗：' + data.message);
                    }
                })
                .catch(err => {
                    console.error('移除卡片時發生錯誤', err);
                    alert('發生錯誤，請稍後再試');
                });
        }
    </script>
</body>

</html>