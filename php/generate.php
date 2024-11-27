<?php
session_start();
$is_logged_in = isset($_SESSION['user_name']);

if (!$is_logged_in) {
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
    <style>
        .content {
            display: flex;
            background-color: #f5f6fa;
            min-height: 100vh;
        }

        .left-panel {
            width: 300px;
            background-color: #102a49;
            padding: 20px;
            color: white;
            height: 100vh;
            overflow-y: auto;
            position: fixed;
            left: 0;
        }

        .right-panel {
            flex: 1;
            margin-left: 500px;
            transition: margin-left 0.3s ease;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .right-panel.sidebar-collapsed {
            margin-left: 300px;
        }

        .search-form {
            width: 100%;
            margin-bottom: 20px;
        }

        .search-form select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }

        .search-form button {
            width: 100%;
            padding: 10px;
            background-color: #1e4b8d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-form button:hover {
            background-color: #2c5aa0;
        }

        .card-list {
            max-height: calc(100vh - 100px);
            overflow-y: auto;
            width: 100%;
            padding: 0;
            list-style: none;
        }

        .card-item {
            background-color: white;
            margin: 10px 0;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }

        .card-item img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-right: 15px;
        }

        .card-info {
            flex: 1;
            color: #000;
        }

        .card-info p {
            margin: 5px 0;
            color: #000;
        }

        .card-item button {
            background-color: #102a49;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .card-item button:hover {
            background-color: #1e4b8d;
        }

        .canvas-container {
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        #downloadButton {
            background-color: #102a49;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s;
        }

        #downloadButton:hover {
            background-color: #1e4b8d;
        }

        .sidebar {
            width: 200px;
            height: 100vh;
            position: fixed;
            left: 0;
            background-color: #102a49;
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar.collapsed {
            transform: translateX(-200px);
        }

        .toggle-btn {
            position: fixed;
            left: 200px;
            top: 10px;
            background-color: #102a49;
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            z-index: 1000;
            transition: left 0.3s ease;
            border-radius: 0 5px 5px 0;
        }

        .toggle-btn.collapsed {
            left: 0;
        }

        .content {
            margin-left: 200px;
            transition: margin-left 0.3s ease;
        }

        .content.full-width {
            margin-left: 0;
        }

        .left-panel {
            width: 300px;
            background-color: #102a49;
            padding: 20px;
            color: white;
            height: 100vh;
            overflow-y: auto;
            position: fixed;
            left: 200px;
            transition: left 0.3s ease;
        }

        .left-panel h2 {
            margin-top: 0;
            padding-top: 0;
        }

        .left-panel.sidebar-collapsed {
            left: 0;
        }

        .right-panel {
            margin-left: 500px;
            transition: margin-left 0.3s ease;
        }

        .right-panel.sidebar-collapsed {
            margin-left: 300px;
        }
    </style>
</head>

<body>
    <button class="toggle-btn" onclick="toggleSidebar()">
        <span id="toggle-icon">◀</span>
    </button>

    <nav class="sidebar">
        <ul>
            <li><a href="../php/home.php">首頁</a></li>
            <li><a href="../php/generate.php">卡牌生成區</a></li>
            <li><a href="../php/cards.php">卡牌圖鑑</a></li>
            <li><a href="../php/reference.php">關於我們</a></li>
        </ul>
    </nav>

    <main class="content">
        <div class="left-panel">
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
                <button id="downloadButton" style="display:none;" onclick="downloadCard()">下載卡牌</button>
            </div>
        </div>
    </main>

    <script src="../js/generate_card.js"></script>
    <script>
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