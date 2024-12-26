<?php
session_start();

// 檢查是否登入
if (!isset($_SESSION['user_id'])) {
    die("User ID is not set in session.");
}

// 資料庫連線設定
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

// 背景圖片資料夾
$normal_dir = "../images/card_background/normal/";
$rare_dir = "../images/card_background/rare/";
$normal_images = array_diff(scandir($normal_dir), array('..', '.'));
$rare_images = array_diff(scandir($rare_dir), array('..', '.'));

// 假設你在此拿到了 $selected_pokemon['ability']、['rarity'] 等欄位
// 這邊只是示範取最短的 ability
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

// 圖片檔名處理
$imageFileName = $selected_pokemon['name'] . '.png';
$pokemonImagePath = "../images/pokemon_images/" . strtolower(str_replace(' ', '-', $imageFileName));

$selected_pokemon['ability'] = getShortestAbility($selected_pokemon['name'], $conn);
$selected_pokemon['image_url'] = $pokemonImagePath;

?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>卡牌生成</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/generate.css">
</head>

<body data-page="generate">
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
                <!-- 這裡可以改大一點, 例如 300x450, 400x600 etc. -->
                <canvas id="cardCanvas" width="450" height="675"></canvas>
                <div class="save-options">
                    <button onclick="downloadCard()" class="download-btn">下載卡牌</button>
                    <button onclick="addToBooklet()" class="add-to-booklet-btn">加入卡冊</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 從後端 PHP 帶出的寶可夢資訊
        let currentPokemonData = <?php echo json_encode($selected_pokemon); ?>;
        let selectedBackground = '';

        function switchBackgroundType(type) {
            document.querySelectorAll('.background-type-selector button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`button[onclick="switchBackgroundType('${type}')"]`).classList.add('active');

            document.getElementById('normal-backgrounds').style.display = (type === 'normal') ? 'grid' : 'none';
            document.getElementById('rare-backgrounds').style.display = (type === 'rare') ? 'grid' : 'none';
        }

        function selectBackground(bgPath) {
            selectedBackground = bgPath;
            document.querySelectorAll('.background-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            const selectedBg = document.querySelector(`.background-option[onclick*="${bgPath}"]`);
            if (selectedBg) {
                selectedBg.classList.add('selected');
            }
            generateCard(currentPokemonData, '../images/card_background/' + bgPath);
        }

        // 核心：等比例縮放
        function generateCard(pokemonData, backgroundSrc) {
            const canvas = document.getElementById('cardCanvas');
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const bgImg = new Image();
            bgImg.crossOrigin = "Anonymous";
            bgImg.onerror = function () {
                console.error('背景圖片載入失敗:', backgroundSrc);
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

                // 內層白底
                const margin = Math.min(canvas.width, canvas.height) * 0.06;
                const innerW = canvas.width - margin * 2;
                const innerH = canvas.height - margin * 2;
                const innerRadius = Math.min(canvas.width, canvas.height) * 0.03;
                roundRect(ctx, margin, margin, innerW, innerH, innerRadius);
                ctx.save();
                ctx.fillStyle = 'rgba(255, 255, 255, 0.98)';
                ctx.fill();
                ctx.restore();

                // 寶可夢圖檔名
                const displayName = pokemonData.name || '';
                const imageFileName = displayName.toLowerCase().replace(/\s+/g, '-');
                const pokemonImagePath = `../images/pokemon_images/${imageFileName}.png`;

                // 檢查
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
                            // 寶可夢圖位置 & 大小
                            const pokeX = canvas.width * 0.16;
                            const pokeY = canvas.height * 0.15;
                            const pokeW = canvas.width * 0.68;
                            const pokeH = canvas.height * 0.35;
                            ctx.drawImage(pokemonImg, pokeX, pokeY, pokeW, pokeH);

                            drawCardText(ctx, canvas, pokemonData, pokeX, pokeY, pokeW, pokeH);
                        };
                        pokemonImg.src = pokemonImagePath;
                    })
                    .catch(error => {
                        console.log('跳過卡片生成:', error.message);
                    });
            };
            bgImg.src = backgroundSrc;
        }

        // 文字全部等比例繪製
        function drawCardText(ctx, canvas, pokemonData, pokeX, pokeY, pokeW, pokeH) {
            let y = pokeY + pokeH + canvas.height * 0.05;

            // 名稱
            ctx.textAlign = 'left';
            ctx.fillStyle = '#333';
            ctx.font = `bold ${canvas.width * 0.08}px "Cinzel", "Noto Sans TC", serif`;
            const xName = canvas.width * 0.18;
            ctx.fillText(pokemonData.name || '', xName, y);

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

            // 特性
            if (pokemonData.ability && pokemonData.ability.name) {
                y += canvas.height * 0.04;
                ctx.font = `${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
                ctx.fillText('特性', xName, y);

                ctx.font = `bold ${canvas.width * 0.05}px "Cinzel", "Noto Sans TC", serif`;
                ctx.fillText(pokemonData.ability.name, xName + canvas.width * 0.15, y);

                if (pokemonData.ability.description) {
                    y += canvas.height * 0.04;
                    ctx.font = `${canvas.width * 0.04}px "Cinzel", "Noto Sans TC", serif`;
                    const maxWidth = canvas.width * 0.64;
                    wrapText(ctx, pokemonData.ability.description, xName, y, maxWidth, canvas.height * 0.05);
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

        // 下載卡片
        function downloadCard() {
            try {
                const canvas = document.getElementById('cardCanvas');
                const selectedBg = document.querySelector('.background-option.selected');
                if (!selectedBg) {
                    alert("請先選擇一個背景！");
                    return;
                }
                const bgPath = selectedBg.getAttribute('onclick').match(/'([^']+)'/)[1];
                const bgName = bgPath.split('/').pop().split('.')[0];

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

        // 加入卡冊
        function addToBooklet() {
            const selectedBg = document.querySelector('.background-option.selected');
            if (!selectedBg) {
                alert("請先選擇一個背景！");
                return;
            }

            const bgPath = selectedBg.getAttribute('onclick').match(/'([^']+)'/)[1];
            const backgroundImageUrl = '../images/card_background/' + bgPath;

            const payload = [{
                name: currentPokemonData.name,
                rarity: currentPokemonData.rarity,
                type1: currentPokemonData.type1,
                type2: currentPokemonData.type2 || '',
                image_url: currentPokemonData.image_url,
                background_image_url: backgroundImageUrl,
                Ability: currentPokemonData.ability ? currentPokemonData.ability.name : '',
                ability_description: currentPokemonData.ability ? currentPokemonData.ability.description : ''
            }];

            fetch('../php/booklet_add.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(response => {
                    // 先檢查響應的內容類型
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    }
                    // 如果不是 JSON，則讀取文本內容
                    return response.text().then(text => {
                        throw new Error('伺服器返回非 JSON 格式：' + text);
                    });
                })
                .then(data => {
                    if (data.success) {
                        alert('卡片已成功加入卡冊！');
                    } else {
                        alert('加入卡冊失敗: ' + (data.message || '未知錯誤'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('發生錯誤：' + error.message);
                });
        }

        // 預設載入第一張背景
        window.onload = function () {
            const firstBg = document.querySelector('.background-option');
            if (firstBg) {
                const bgPath = firstBg.getAttribute('onclick').match(/'([^']+)'/)[1];
                selectBackground(bgPath);
            }
        };
    </script>
</body>

</html>