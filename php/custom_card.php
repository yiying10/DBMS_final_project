<?php
session_start();

// 數據庫連接代碼
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}
$conn->set_charset("utf8");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 查詢最大的自定義ID
    $maxIdQuery = "SELECT MAX(ID) as max_id FROM df_pokemon WHERE ID >= 899";
    $result = $conn->query($maxIdQuery);
    $row = $result->fetch_assoc();
    $newId = ($row['max_id'] === null) ? 899 : $row['max_id'] + 1;

    // 獲取表單數據
    $cardName = $_POST['card-name'];
    $cardType1 = $_POST['card-type'];
    $cardType2 = $_POST['card-secondary-type'];
    $cardRarity = $_POST['card-rarity'];
    $cardPower = $_POST['card-power'];

    // 獲取新增的表單數據
    $ability = $_POST['ability'];
    $abilityDescription = $_POST['ability-description'];

    // 處理上傳的圖片
    $image = $_FILES['card-image'];

    // 修改圖片目錄
    $uploadDir = "../images/pokemon_images/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // 獲取檔案資訊
    $imageFileType = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));

    // 檢查是否為允許的圖片格式
    $allowedTypes = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    if (!in_array($imageFileType, $allowedTypes)) {
        die('不支援的圖片格式');
    }

    // 使用卡牌名稱作為檔案名稱
    $newFileName = $cardName;  // 不轉換為小寫，保持原始名稱
    // 移除任何不安全的字符
    $newFileName = preg_replace('/[^\w\s-]/', '', $newFileName);
    // 替換空格為連字符
    $newFileName = str_replace(' ', '-', $newFileName);

    $uploadFile = $uploadDir . $newFileName . '.png';  // 分開加上副檔名

    // 直接移動上傳的檔案
    if (move_uploaded_file($image['tmp_name'], $uploadFile)) {
        $imageUrl = "images/pokemon_images/" . $newFileName . '.png';  // 確保 URL 中也正確加上副檔名
    } else {
        die('上傳圖片失敗');
    }

    // 準備變量
    $type2 = ($cardType2 !== '無') ? $cardType2 : '';
    $generation = 'Custom';

    // 開始事務處理
    $conn->begin_transaction();

    try {
        // 插入寶可夢數據
        $sql = "INSERT INTO df_pokemon (ID, Name, Type1, Type2, Rarity, Total, image_url, Generation) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssssss",
            $newId,
            $cardName,
            $cardType1,
            $type2,
            $cardRarity,
            $cardPower,
            $imageUrl,
            $generation
        );
        $stmt->execute();

        // 插入 ability 表數據
        $sqlAbility = "INSERT INTO ability (Ability, Hidden, Name) VALUES (?, 0, ?)";
        $stmtAbility = $conn->prepare($sqlAbility);
        $stmtAbility->bind_param("ss", $ability, $cardName);
        $stmtAbility->execute();

        // 插入 ability_description 表數據
        $sqlAbilityDesc = "INSERT INTO ability_description (Name, Generation, Description) VALUES (?, 'Custom', ?)";
        $stmtAbilityDesc = $conn->prepare($sqlAbilityDesc);
        $stmtAbilityDesc->bind_param("ss", $ability, $abilityDescription);
        $stmtAbilityDesc->execute();

        $conn->commit();

        // 關閉所有語句
        $stmt->close();
        $stmtAbility->close();
        $stmtAbilityDesc->close();

        echo "<script>
                alert('新寶可夢已成功添加到圖鑑！');
                window.location.href = '../php/illustrated_book.php';
              </script>";
        exit();
    } catch (Exception $e) {
        // 發生錯誤時回滾
        $conn->rollback();

        // 關閉所有已準備的語句
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($stmtAbility)) {
            $stmtAbility->close();
        }
        if (isset($stmtAbilityDesc)) {
            $stmtAbilityDesc->close();
        }

        echo "Error: " . $e->getMessage();
    }

    // 檢查卡牌名稱是否重複
    $checkName = "SELECT COUNT(*) as count FROM df_pokemon WHERE Name = ?";
    $stmtCheckName = $conn->prepare($checkName);
    $stmtCheckName->bind_param("s", $cardName);
    $stmtCheckName->execute();
    $nameResult = $stmtCheckName->get_result()->fetch_assoc();

    // 檢查能力名稱是否重複
    $checkAbility = "SELECT COUNT(*) as count FROM ability WHERE Ability = ?";
    $stmtCheckAbility = $conn->prepare($checkAbility);
    $stmtCheckAbility->bind_param("s", $ability);
    $stmtCheckAbility->execute();
    $abilityResult = $stmtCheckAbility->get_result()->fetch_assoc();

    // 檢查能力描述是否重複
    $checkDesc = "SELECT COUNT(*) as count FROM ability_description WHERE Description = ?";
    $stmtCheckDesc = $conn->prepare($checkDesc);
    $stmtCheckDesc->bind_param("s", $abilityDescription);
    $stmtCheckDesc->execute();
    $descResult = $stmtCheckDesc->get_result()->fetch_assoc();

    if ($nameResult['count'] > 0) {
        echo "<script>alert('卡牌名稱已存在！'); history.back();</script>";
        exit();
    } else if ($abilityResult['count'] > 0) {
        echo "<script>alert('能力名稱已存在！'); history.back();</script>";
        exit();
    } else if ($descResult['count'] > 0) {
        echo "<script>alert('能力描述已存在！'); history.back();</script>";
        exit();
    }

    // 關閉檢查用的語句
    $stmtCheckName->close();
    $stmtCheckAbility->close();
    $stmtCheckDesc->close();
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>自製卡牌區 - R~Pokemon Go！</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* 自製卡牌區樣式 */
        #custom-card-form {
            max-width: 600px;
            /* 最大寬度 */
            margin: 20px auto;
            /* 自動邊距以居中 */
            padding: 20px;
            /* 內邊距 */
            border: 1px solid #ccc;
            /* 邊框 */
            border-radius: 8px;
            /* 圓角 */
            background-color: #f9f9f9;
            /* 背景顏色 */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            /* 陰影效果 */
        }

        .form-group {
            margin-bottom: 15px;
            /* 每個表單組的底部邊距 */
        }

        label {
            display: block;
            /* 使標籤顯示為塊級元素 */
            margin-bottom: 5px;
            /* 標籤底部邊距 */
            font-weight: bold;
            /* 加粗字體 */
        }

        input[type="file"],
        input[type="text"],
        select {
            width: 100%;
            /* 寬度100% */
            padding: 10px;
            /* 內邊距 */
            border: 1px solid #ccc;
            /* 邊框 */
            border-radius: 4px;
            /* 圓角 */
            box-sizing: border-box;
            /* 包含內邊距和邊框 */
        }

        button.submit-button {
            background-color: #4CAF50;
            /* 按鈕背景顏色 */
            color: white;
            /* 字體顏色 */
            padding: 10px 15px;
            /* 內邊距 */
            border: none;
            /* 無邊框 */
            border-radius: 4px;
            /* 圓角 */
            cursor: pointer;
            /* 鼠標指針 */
            font-size: 16px;
            /* 字體大小 */
        }

        button.submit-button:hover {
            background-color: #45a049;
            /* 懸停時的背景顏色 */
        }

        #preview-area {
            margin-top: 20px;
            /* 預覽區域的上邊距 */
            padding: 10px;
            /* 內邊距 */
            border: 1px dashed #ccc;
            /* 虛線邊框 */
            border-radius: 4px;
            /* 圓角 */
            background-color: #fff;
            /* 背景顏色 */
            text-align: center;
            /* 文字居中 */
        }

        input[type="number"] {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        /* 移除數字入框的上下箭頭 */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body>
    <!-- 包含導航欄等共用元件 -->
    <nav class="sidebar">
        <ul>
            <li><a href="../php/home.php">首頁</a></li>
            <li><a href="../php/custom_card.php" id="custom-card-link">自製卡牌區</a></li>
            <li><a href="../php/generate.php" id="card-generation-link">卡牌生成區</a></li>
            <li><a href="../php/illustrated_book.php">卡牌圖鑑</a></li>
            <li><a href="../php/pakage.php" id="pakage-link">抽卡區</a></li>
            <li><a href="../php/booklet.php" id="booklet-link">卡冊</a></li>
            <li><a href="../php/forum.php" id="forum-link">論壇</a></li>
            <li><a href="../php/reference.php">關於我們</a></li>
        </ul>
    </nav>

    <main class="content">
        <h1>自製卡牌</h1>
        <form id="custom-card-form" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="card-image">傳卡牌圖片：</label>
                <input type="file" id="card-image" name="card-image" accept="image/*" required
                    onchange="previewImage(event)">
            </div>

            <div class="form-group" id="image-preview-container" style="display: none;">
                <label>預覽圖片：</label>
                <img id="image-preview" src="" alt="卡牌預覽"
                    style="max-width: 300px; max-height: 300px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div class="form-group">
                <label for="card-rarity">稀有度：</label>
                <select id="card-rarity" name="card-rarity" required>
                    <option value="Common">Common</option>
                    <option value="Baby">Baby</option>
                    <option value="Mythical">Mythical</option>
                    <option value="Legendary">Legendary</option>
                </select>
            </div>

            <div class="form-group">
                <label for="card-type">主要屬性：</label>
                <select id="card-type" name="card-type" required>
                    <option value="Ghost">Ghost</option>
                    <option value="Grass">Grass</option>
                    <option value="Psychic">Psychic</option>
                    <option value="Dark">Dark</option>
                    <option value="Bug">Bug</option>
                    <option value="Steel">Steel</option>
                    <option value="Rock">Rock</option>
                    <option value="Normal">Normal</option>
                    <option value="Fairy">Fairy</option>
                    <option value="Ground">Ground</option>
                    <option value="Poison">Poison</option>
                    <option value="Fire">Fire</option>
                    <option value="Ice">Ice</option>
                    <option value="Electric">Electric</option>
                    <option value="Water">Water</option>
                    <option value="Dragon">Dragon</option>
                    <option value="Fighting">Fighting</option>
                    <option value="Flying">Flying</option>
                </select>
            </div>

            <div class="form-group">
                <label for="card-secondary-type">次要屬性：</label>
                <select id="card-secondary-type" name="card-secondary-type">
                    <option value="無">無</option>
                    <option value="Ghost">Ghost</option>
                    <option value="Grass">Grass</option>
                    <option value="Psychic">Psychic</option>
                    <option value="Dark">Dark</option>
                    <option value="Bug">Bug</option>
                    <option value="Steel">Steel</option>
                    <option value="Rock">Rock</option>
                    <option value="Normal">Normal</option>
                    <option value="Fairy">Fairy</option>
                    <option value="Ground">Ground</option>
                    <option value="Poison">Poison</option>
                    <option value="Fire">Fire</option>
                    <option value="Ice">Ice</option>
                    <option value="Electric">Electric</option>
                    <option value="Water">Water</option>
                    <option value="Dragon">Dragon</option>
                    <option value="Fighting">Fighting</option>
                    <option value="Flying">Flying</option>
                </select>
            </div>

            <div class="form-group">
                <label for="card-power">能力值總和：</label>
                <input type="number" id="card-power" name="card-power" placeholder="請輸入1-1000之間的數值" min="1" max="1000"
                    required>
            </div>

            <div class="form-group">
                <label for="ability">能力名稱：</label>
                <input type="text" id="ability" name="ability" required>
            </div>

            <div class="form-group">
                <label for="ability-description">能力描述：</label>
                <input type="text" id="ability-description" name="ability-description" required>
            </div>

            <div class="form-group">
                <label for="card-name">卡牌名：</label>
                <input type="text" id="card-name" name="card-name" required>
            </div>

            <button type="submit" class="submit-button">創建卡牌</button>
        </form>

        <div id="preview-area">
            <!-- 這裡將顯示卡牌預覽 -->
        </div>
    </main>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            const previewContainer = document.getElementById('image-preview-container');
            const imagePreview = document.getElementById('image-preview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
            }
        }
    </script>
</body>

</html>