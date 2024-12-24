<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 若未登入則擋下
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => '用戶未登入'
    ]);
    exit;
}

// 讀取前端送來的 JSON
$postData = json_decode(file_get_contents('php://input'), true);
if (!$postData || !isset($postData['card_id'])) {
    echo json_encode([
        'success' => false,
        'message' => '缺少 card_id'
    ]);
    exit;
}

$cardId = (int) $postData['card_id'];
$user_id = $_SESSION['user_id'];

// 連線資料庫
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => '資料庫連接失敗: ' . $conn->connect_error
    ]);
    exit;
}

// 執行刪除
$stmt = $conn->prepare("DELETE FROM booklet WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $cardId, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // 成功刪除
        echo json_encode([
            'success' => true,
            'message' => '卡片已刪除'
        ]);
    } else {
        // 沒刪到任何資料，可能該卡不屬於此用戶
        echo json_encode([
            'success' => false,
            'message' => '沒有權限或卡片不存在'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => '刪除失敗: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
