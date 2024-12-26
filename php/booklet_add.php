<?php
header('Content-Type: application/json');
session_start();

try {
    // 檢查用戶是否登入
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('用戶未登入');
    }

    $user_id = $_SESSION['user_id'];

    // 獲取 POST 數據
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if ($data === null) {
        throw new Exception('無效的 JSON 數據');
    }

    // 資料庫連接設置
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "pokemon";

    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");

    if ($conn->connect_error) {
        throw new Exception("數據庫連接失敗: " . $conn->connect_error);
    }

    // 修改 SQL 語句，只插入必要欄位
    $sql = "INSERT INTO booklet (card_id, user_id, pokemon_name, background_image_url) 
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("SQL 準備失敗: " . $conn->error);
    }

    // 在 foreach 循環之前，先查詢最大的 card_id
    $maxIdQuery = "SELECT MAX(CAST(card_id AS UNSIGNED)) as max_id FROM booklet";
    $result = $conn->query($maxIdQuery);
    $row = $result->fetch_assoc();
    $maxId = $row['max_id'] ?? 0;  // 如果沒有記錄，從 0 開始

    foreach ($data as $card) {
        // 遞增 card_id
        $maxId++;
        $card_id = (string) $maxId;

        // 修改 bind_param 參數
        $stmt->bind_param(
            "siss",
            $card_id,
            $user_id,
            $card['name'],
            $card['background_image_url']
        );

        if (!$stmt->execute()) {
            throw new Exception("執行失敗: " . $stmt->error);
        }
    }

    // 返回成功響應
    echo json_encode([
        'success' => true,
        'message' => '卡片已成功加入卡冊',
        'card_id' => $card_id    // 返回生成的 card_id
    ]);

} catch (Exception $e) {
    // 返回錯誤響應
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// 關閉資料庫連接
if (isset($stmt))
    $stmt->close();
if (isset($conn))
    $conn->close();
?>