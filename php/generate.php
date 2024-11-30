<?php
session_start();

// 資料庫連線設定
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

// 建立連線
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連線
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 設定編碼
$conn->set_charset("utf8mb4");

$is_logged_in = isset($_SESSION['user_name']);
$selected_pokemon = isset($_SESSION['selected_pokemon']) ? $_SESSION['selected_pokemon'] : null;

if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

if (!$selected_pokemon) {
    header("Location: illustrated_book.php");
    exit();
}

// 獲取背景圖片選項
$normal_dir = "../images/card_background/normal/";
$rare_dir = "../images/card_background/rare/";
$normal_images = array_diff(scandir($normal_dir), array('..', '.'));
$rare_images = array_diff(scandir($rare_dir), array('..', '.'));

// 在頁面加載時處理 ability 數據
function getShortestAbility($pokemonName, $conn)
{
    $sql = "SELECT a.Ability, ad.Description 
            FROM ability a 
            LEFT JOIN ability_description ad ON a.Ability = ad.Name 
            WHERE a.Name = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $pokemonName);
    $stmt->execute();
    $result = $stmt->get_result();

    $abilities = [];
    while ($row = $result->fetch_assoc()) {
        $abilities[] = [
            'name' => $row['Ability'],
            'description' => $row['Description']
        ];
    }

    if (!empty($abilities)) {
        $shortestAbility = $abilities[0];
        foreach ($abilities as $ability) {
            if (strlen($ability['name']) < strlen($shortestAbility['name'])) {
                $shortestAbility = $ability;
            }
        }
        return $shortestAbility;
    }

    return null;
}

// 將能力添加到寶可夢數據中
if ($selected_pokemon) {
    $selected_pokemon['ability'] = getShortestAbility($selected_pokemon['name'], $conn);
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
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;700&family=Noto+Sans+TC:wght@400;500;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Cinzel', 'Noto Sans TC', serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Cinzel', 'Noto Sans TC', serif;
            font-weight: 700;
        }

        .sidebar a {
            font-family: 'Cinzel', 'Noto Sans TC', serif;
            font-weight: 500;
        }

        .background-type-selector button {
            font-family: 'Cinzel', 'Noto Sans TC', serif;
            font-weight: 500;
            letter-spacing: 1px;
        }

        .save-options button {
            font-family: 'Cinzel', 'Noto Sans TC', serif;
            font-weight: 500;
            letter-spacing: 1px;
        }

        .background-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            padding: 20px;
        }

        .background-option {
            width: 120px;
            height: 120px;
            border: 2px solid #ccc;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }

        .background-option:hover {
            transform: translateY(-5px);
            border-color: #007bff;
        }

        .background-option.selected {
            border-color: #007bff;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
        }

        .background-option img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .canvas-container {
            margin-top: 20px;
            text-align: center;
        }

        #cardCanvas {
            border: 1px solid #ccc;
            margin: 20px auto;
            border-radius: 25px;
        }

        .save-options {
            margin-top: 20px;
        }

        .save-options button {
            padding: 10px 20px;
            margin: 0 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .save-options button:hover {
            background-color: #0056b3;
        }

        .background-type-selector {
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #ddd;
            display: inline-flex;
            border-radius: 5px;
            overflow: hidden;
        }

        .background-type-selector button {
            padding: 8px 25px;
            background-color: #f8f9fa;
            border: none;
            cursor: pointer;
            position: relative;
        }

        .background-type-selector button:first-child {
            border-right: 1px solid #ddd;
        }

        .background-type-selector button.active {
            background-color: #007bff;
            color: white;
        }

        .background-section {
            display: none;
        }

        .background-section.active {
            display: block;
        }
    </style>
</head>

<body data-page="generate">
    <!-- Sidebar -->
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

    <div class="content">
        <div class="left-panel">
            <h2>選擇卡牌背景</h2>
            <div class="background-type-selector">
                <button onclick="switchBackgroundType('normal')" class="active">純色背景</button>
                <button onclick="switchBackgroundType('rare')">閃亮背景</button>
            </div>

            <div id="normal-backgrounds" class="background-section background-options active">
                <?php foreach ($normal_images as $bg): ?>
                    <div class="background-option" onclick="selectBackground('normal/<?php echo $bg; ?>')">
                        <img src="<?php echo $normal_dir . $bg; ?>" alt="純色背景選項">
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="rare-backgrounds" class="background-section background-options">
                <?php foreach ($rare_images as $bg): ?>
                    <div class="background-option" onclick="selectBackground('rare/<?php echo $bg; ?>')">
                        <img src="<?php echo $rare_dir . $bg; ?>" alt="閃亮背景選項">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="right-panel">
            <div class="canvas-container">
                <canvas id="cardCanvas" width="500" height="700"></canvas>
                <div class="save-options">
                    <button onclick="downloadCard()" class="download-btn">下載卡牌</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 確保 pokemonData 是全局可用的
        let currentPokemonData = <?php echo json_encode($selected_pokemon); ?>;
        console.log('Pokemon Data:', currentPokemonData); // 添加調試信息

        function switchBackgroundType(type) {
            document.querySelectorAll('.background-type-selector button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`button[onclick="switchBackgroundType('${type}')"]`).classList.add('active');

            document.getElementById('normal-backgrounds').style.display = type === 'normal' ? 'grid' : 'none';
            document.getElementById('rare-backgrounds').style.display = type === 'rare' ? 'grid' : 'none';
        }

        function selectBackground(bgPath) {
            document.querySelectorAll('.background-option').forEach(opt => {
                opt.classList.remove('selected');
            });

            const selectedBg = document.querySelector(`.background-option[onclick*="${bgPath}"]`);
            if (selectedBg) {
                selectedBg.classList.add('selected');
            }

            generateCard(currentPokemonData, '../images/card_background/' + bgPath);
        }

        function generateCard(pokemonData, backgroundName) {
            const canvas = document.getElementById('cardCanvas');
            const ctx = canvas.getContext('2d');
            console.log('Generating card with background:', backgroundName);

            // 處理寶可夢名稱：
            // displayName 保持原始格式（大寫開頭，空格）用於顯示
            // imageFileName 轉小寫且空格換成-，用於圖片路徑
            const displayName = pokemonData.name; // 保持原始名稱
            const imageFileName = pokemonData.name.toLowerCase().replace(/\s+/g, '-'); // 轉小寫且空格換成-
            const pokemonImagePath = `../images/pokemon_images/${imageFileName}.png`;
            console.log('Pokemon image path:', pokemonImagePath);

            const bgImg = new Image();
            bgImg.crossOrigin = "Anonymous";
            bgImg.onerror = function () {
                console.error('Background image failed to load:', backgroundName);
            };
            bgImg.onload = function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                // 背景
                ctx.save();
                roundRect(ctx, 0, 0, canvas.width, canvas.height, 25);
                ctx.clip();
                ctx.drawImage(bgImg, 0, 0, canvas.width, canvas.height);
                ctx.restore();

                // 白色底圖
                ctx.save();
                roundRect(ctx, 30, 30, canvas.width - 60, canvas.height - 60, 15);
                ctx.fillStyle = 'rgba(255, 255, 255, 0.98)';
                ctx.fill();
                ctx.restore();

                // 檢查圖片是否存在和稀有度是否存在
                fetch(pokemonImagePath)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Image not found');
                        }
                        if (!pokemonData.rarity) {
                            throw new Error('No rarity defined');
                        }
                        return true;
                    })
                    .then(() => {
                        // 圖片存在且有稀有度，繪製完整卡片
                        const pokemonImg = new Image();
                        pokemonImg.crossOrigin = "Anonymous";
                        pokemonImg.onload = function () {
                            const imgWidth = canvas.width - 160;
                            const imgHeight = (canvas.height - 140) * 0.5;
                            ctx.drawImage(pokemonImg, 80, 90, imgWidth, imgHeight);
                            drawCardText(imgHeight, displayName);
                        };
                        pokemonImg.src = pokemonImagePath;
                    })
                    .catch((error) => {
                        // 圖片不存在或無稀有度，不繪製任何內容
                        console.log('Skipping card generation:', error.message);
                    });
            };
            bgImg.src = backgroundName;

            function drawCardText(imgHeight, displayName) {
                // 文字排版
                ctx.textAlign = 'left';
                let y = imgHeight + 120;

                // 標題（使用原始名稱，保持大寫開頭）
                ctx.fillStyle = '#333';
                ctx.font = 'bold 28px "Cinzel", "Noto Sans TC", serif';
                ctx.fillText(displayName, 90, y);

                // 分隔線
                y += 20;
                ctx.beginPath();
                ctx.strokeStyle = '#ddd';
                ctx.lineWidth = 1;
                ctx.moveTo(90, y);
                ctx.lineTo(canvas.width - 90, y);
                ctx.stroke();

                // 稀有度
                y += 35;
                ctx.font = '22px "Cinzel", "Noto Sans TC", serif';
                ctx.fillStyle = '#333';
                ctx.fillText('稀有度', 90, y);
                ctx.font = 'bold 22px "Cinzel", "Noto Sans TC", serif';
                ctx.fillText(pokemonData.rarity, 180, y);

                // 屬性（如果有）
                if (pokemonData.type1) {
                    y += 35;
                    ctx.font = '22px "Cinzel", "Noto Sans TC", serif';
                    ctx.fillText('屬性', 90, y);
                    ctx.font = 'bold 22px "Cinzel", "Noto Sans TC", serif';
                    const types = pokemonData.type2
                        ? `${pokemonData.type1} / ${pokemonData.type2}`
                        : pokemonData.type1;
                    ctx.fillText(types, 180, y);
                }

                // 特性和描述（如果有）
                if (pokemonData.ability && pokemonData.ability.name) {
                    y += 35;
                    ctx.font = '22px "Cinzel", "Noto Sans TC", serif';
                    ctx.fillText('特性', 90, y);
                    ctx.font = 'bold 22px "Cinzel", "Noto Sans TC", serif';
                    ctx.fillText(pokemonData.ability.name, 180, y);

                    if (pokemonData.ability.description) {
                        y += 35;
                        ctx.font = '18px "Cinzel", "Noto Sans TC", serif';
                        const maxWidth = canvas.width - 180;
                        wrapText(ctx, pokemonData.ability.description, 90, y, maxWidth, 25);
                    }
                }
            }
        }

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

        function downloadCard() {
            try {
                const canvas = document.getElementById('cardCanvas');
                const selectedBg = document.querySelector('.background-option.selected');
                const bgPath = selectedBg.getAttribute('onclick').match(/'([^']+)'/)[1];
                const bgName = bgPath.split('/').pop().split('.')[0];

                // 將寶可夢名稱中的空格替換為底線
                const safePokemonName = currentPokemonData.name.replace(/\s+/g, '_');
                const fileName = `${safePokemonName}_${bgName}.png`;

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

        // 頁面加載時自動選擇第一個背景
        window.onload = function () {
            const firstBg = document.querySelector('.background-option');
            if (firstBg) {
                const bgPath = firstBg.getAttribute('onclick').match(/'([^']+)'/)[1];
                selectBackground(bgPath);
            }
        };

        // 在 script 標籤內添加文字換行函數
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
    </script>
</body>

</html>