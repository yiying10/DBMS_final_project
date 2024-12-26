<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../html/login.html');
    exit();
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
            max-width: 600px; /* 最大寬度 */
            margin: 20px auto; /* 自動邊距以居中 */
            padding: 20px; /* 內邊距 */
            border: 1px solid #ccc; /* 邊框 */
            border-radius: 8px; /* 圓角 */
            background-color: #f9f9f9; /* 背景顏色 */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* 陰影效果 */
        }

        .form-group {
            margin-bottom: 15px; /* 每個表單組的底部邊距 */
        }

        label {
            display: block; /* 使標籤顯示為塊級元素 */
            margin-bottom: 5px; /* 標籤底部邊距 */
            font-weight: bold; /* 加粗字體 */
        }

        input[type="file"],
        input[type="text"],
        select {
            width: 100%; /* 寬度100% */
            padding: 10px; /* 內邊距 */
            border: 1px solid #ccc; /* 邊框 */
            border-radius: 4px; /* 圓角 */
            box-sizing: border-box; /* 包含內邊距和邊框 */
        }

        button.submit-button {
            background-color: #4CAF50; /* 按鈕背景顏色 */
            color: white; /* 字體顏色 */
            padding: 10px 15px; /* 內邊距 */
            border: none; /* 無邊框 */
            border-radius: 4px; /* 圓角 */
            cursor: pointer; /* 鼠標指針 */
            font-size: 16px; /* 字體大小 */
        }

        button.submit-button:hover {
            background-color: #45a049; /* 懸停時的背景顏色 */
        }

        #preview-area {
            margin-top: 20px; /* 預覽區域的上邊距 */
            padding: 10px; /* 內邊距 */
            border: 1px dashed #ccc; /* 虛線邊框 */
            border-radius: 4px; /* 圓角 */
            background-color: #fff; /* 背景顏色 */
            text-align: center; /* 文字居中 */
        }
    </style>
</head>
<body>
    <!-- 包含導航欄等共用元素 -->
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
        <form id="custom-card-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="card-image">上傳卡牌圖片：</label>
                <input type="file" id="card-image" name="card-image" accept="image/*" required>
            </div>
            
            <div class="form-group">
                <label for="card-type">選擇屬性：</label>
                <select id="card-type" name="card-type" required>
                    <option value="">請選擇屬性</option>
                    <option value="fire">火</option>
                    <option value="water">水</option>
                    <option value="grass">草</option>
                    <option value="electric">電</option>
                    <!-- 可以添加更多屬性 -->
                </select>
            </div>
            
            <div class="form-group">
                <label for="card-name">卡牌名稱：</label>
                <input type="text" id="card-name" name="card-name" required>
            </div>
            
            <button type="submit" class="submit-button">創建卡牌</button>
        </form>
        
        <div id="preview-area">
            <!-- 這裡將顯示卡牌預覽 -->
        </div>
    </main>
    
    <script>
        // 處理表單提交和預覽的JavaScript代碼將在這裡
    </script>
</body>
</html> 