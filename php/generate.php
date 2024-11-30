<?php
session_start();
$is_logged_in = isset($_SESSION['user_name']);
$has_generated = isset($_SESSION['has_generated']);

if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

if (!$has_generated && !isset($_SESSION['selected_pokemon'])) {
    echo "<script>alert('請先從圖鑑選擇寶可夢進行生成！'); window.location.href='illustrated_book.php';</script>";
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

// 初始化變數
$rarity_options = [];
$type_options = [];
$search_results = [];
$selected_rarity = "";
$selected_type = "";

// 獲取分類選項（稀有度和屬性）
$rarity_query = "SELECT DISTINCT Rarity FROM df_pokemon WHERE Rarity IS NOT NULL";
$type_query = "SELECT DISTINCT Type1 FROM df_pokemon WHERE Type1 IS NOT NULL";

$rarity_result = $conn->query($rarity_query);
$type_result = $conn->query($type_query);

while ($row = $rarity_result->fetch_assoc()) {
    $rarity_options[] = $row['Rarity'];
}
while ($row = $type_result->fetch_assoc()) {
    $type_options[] = $row['Type1'];
}

// 獲取背景圖片選項
$background_dir = "../images/card_background/";
$background_images = array_diff(scandir($background_dir), array('..', '.'));

// 如果是從圖鑑過來的，獲取選中的寶可夢
$selected_pokemon = isset($_SESSION['selected_pokemon']) ? $_SESSION['selected_pokemon'] : null;

// 處理篩選請求
// 處理篩選請求
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_rarity = $_POST['rarity'];
    $selected_type = $_POST['type'];

    $query = "SELECT Name, Rarity, Type1, Type2 FROM df_pokemon WHERE Rarity = ? AND Type1 = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $selected_rarity, $selected_type);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // 將圖片轉換為 base64 格式
        $image_path = "../images/pokemon_images/" . strtolower($row['Name']) . ".png";
        try {
            if (file_exists($image_path)) {
                $image_data = base64_encode(file_get_contents($image_path));
                $row['image_url'] = 'data:image/png;base64,' . $image_data;
            } else {
                // 如果找不到對應的圖片，使用一個簡單的錯誤提示
                $row['image_url'] = '';
                error_log("找不到圖片: " . $image_path);
            }
        } catch (Exception $e) {
            $row['image_url'] = '';
            error_log("圖片處理錯誤: " . $e->getMessage());
        }
        $search_results[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>卡牌生成</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/generate.css">
    <style>
        .background-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }

        .background-option {
            cursor: pointer;
            border: 2px solid transparent;
            padding: 5px;
        }

        .background-option.selected {
            border-color: #007bff;
        }

        .background-option img {
            width: 100%;
            height: auto;
        }

        .save-options {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>

<body data-page="generate">
    <button class="toggle-btn" onclick="toggleSidebar()">
        <span id="toggle-icon">◀</span>
    </button>

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
        <div class="left-panel">
            <h2>選擇卡牌背景</h2>
            <div class="background-options">
                <?php foreach ($background_images as $bg): ?>
                    <div class="background-option" onclick="selectBackground('<?php echo $bg; ?>')">
                        <img src="<?php echo $background_dir . $bg; ?>" alt="背景選項">
                    </div>
                <?php endforeach; ?>
            </div>

            <h2>選擇分類生成卡牌</h2>
            <form method="POST" action="generate.php" class="search-form">
                <label for="rarity">選擇稀有度:</label>
                <select id="rarity" name="rarity" required>
                    <option value="">-- 請選擇 --</option>
                    <?php foreach ($rarity_options as $rarity): ?>
                        <option value="<?php echo htmlspecialchars($rarity); ?>" <?php echo ($rarity == $selected_rarity) ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($rarity); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="type">選擇屬性:</label>
                <select id="type" name="type" required>
                    <option value="">-- 請選擇 --</option>
                    <?php foreach ($type_options as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($type == $selected_type) ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">篩選</button>
            </form>

            <?php if (!empty($search_results)): ?>
                <ul class="card-list">
                    <?php foreach ($search_results as $pokemon): ?>
                        <li class="card-item">
                            <img src="<?php echo htmlspecialchars($pokemon['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($pokemon['Name']); ?>">
                            <div class="card-info">
                                <p>名稱: <?php echo htmlspecialchars($pokemon['Name']); ?></p>
                                <p>稀有度: <?php echo htmlspecialchars($pokemon['Rarity']); ?></p>
                                <p>屬性:
                                    <?php echo htmlspecialchars($pokemon['Type1']); ?>
                                    <?php echo $pokemon['Type2'] ? " / " . htmlspecialchars($pokemon['Type2']) : ""; ?>
                                </p>
                            </div>
                            <button onclick="generateCard(
                                '<?php echo htmlspecialchars($pokemon['image_url']); ?>',
                                '<?php echo htmlspecialchars($pokemon['Name']); ?>',
                                '<?php echo htmlspecialchars($pokemon['Rarity']); ?>',
                                '<?php echo htmlspecialchars($pokemon['Type1']); ?>',
                                '<?php echo htmlspecialchars($pokemon['Type2'] ?? ''); ?>'
                            )">生成卡牌</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="right-panel">
            <div class="canvas-container">
                <canvas id="cardCanvas" width="500" height="700"></canvas>
                <div class="save-options" style="display:none;">
                    <button onclick="downloadCard()">下載卡牌</button>
                    <button onclick="saveToBooklet()">保存到卡冊</button>
                </div>
            </div>
        </div>
    </main>

    <script src="../js/generate_card.js"></script>
    <script>
        let selectedBackground = '';

        function selectBackground(bgName) {
            selectedBackground = bgName;
            document.querySelectorAll('.background-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }

        function generateCard(imageUrl, name, rarity, type1, type2) {
            if (!selectedBackground) {
                alert('請先選擇卡牌背景！');
                return;
            }

            const canvas = document.getElementById('cardCanvas');
            const ctx = canvas.getContext('2d');

            // 清空畫布
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // 載入背景圖片
            const bgImg = new Image();
            bgImg.onload = function () {
                ctx.drawImage(bgImg, 0, 0, canvas.width, canvas.height);

                // 繪製內層米色底圖
                ctx.fillStyle = '#f5f5dc';
                roundRect(ctx, 30, 30, canvas.width - 60, canvas.height - 60, 20);

                // 載入寶可夢圖片
                const pokemonImg = new Image();
                pokemonImg.onload = function () {
                    // 在上半部分繪製寶可夢圖片
                    const imgWidth = canvas.width - 100;
                    const imgHeight = (canvas.height - 100) * 0.6;
                    ctx.drawImage(pokemonImg, 50, 50, imgWidth, imgHeight);

                    // 添加文字描述
                    ctx.fillStyle = '#000';
                    ctx.font = '20px Arial';
                    let y = imgHeight + 100;
                    ctx.fillText(`名稱: ${name}`, 60, y);
                    ctx.fillText(`稀有度: ${rarity}`, 60, y + 30);
                    ctx.fillText(`屬性: ${type1}${type2 ? ' / ' + type2 : ''}`, 60, y + 60);

                    // 顯示保存選項
                    document.querySelector('.save-options').style.display = 'flex';
                };
                pokemonImg.src = imageUrl;
            };
            bgImg.src = '../images/card_background/' + selectedBackground;

            $_SESSION['has_generated'] = true;
        }

        function roundRect(ctx, x, y, width, height, radius) {
            ctx.beginPath();
            ctx.moveTo(x + radius, y);
            ctx.arcTo(x + width, y, x + width, y + height, radius);
            ctx.arcTo(x + width, y + height, x, y + height, radius);
            ctx.arcTo(x, y + height, x, y, radius);
            ctx.arcTo(x, y, x + width, y, radius);
            ctx.closePath();
            ctx.fill();
        }

        function saveToBooklet() {
            const canvas = document.getElementById('cardCanvas');
            const imageData = canvas.toDataURL('image/png');

            fetch('save_to_booklet.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    imageData: imageData,
                    background: selectedBackground,
                    pokemonName: currentPokemonName
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('成功保存到卡冊！');
                    } else {
                        alert('保存失敗：' + data.message);
                    }
                });
        }

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const content = document.querySelector('.content');
            const toggleBtn = document.querySelector('.toggle-btn');
            const leftPanel = document.querySelector('.left-panel');
            const rightPanel = document.querySelector('.right-panel');
            const toggleIcon = document.getElementById('toggle-icon');

            sidebar.classList.toggle('collapsed');
            content.classList.toggle('full-width');
            toggleBtn.classList.toggle('collapsed');
            leftPanel.classList.toggle('sidebar-collapsed');
            rightPanel.classList.toggle('sidebar-collapsed');

            // 更改箭頭方向
            toggleIcon.textContent = sidebar.classList.contains('collapsed') ? '▶' : '◀';
        }
    </script>
</body>

</html>