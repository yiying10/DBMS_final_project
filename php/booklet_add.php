<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '用戶未登入']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// 資料庫連線
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("資料庫連接失敗：" . $conn->connect_error);
}

$success = true;
$message = '';

try {
    foreach ($data as $card) {
        // 檢查卡牌是否已存在
        $checkStmt = $conn->prepare("
            SELECT * 
            FROM booklet 
            WHERE user_id = ? 
              AND pokemon_name = ?
        ");
        $checkStmt->bind_param("is", $user_id, $card['name']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        // 若不存在，則插入
        if ($checkResult->num_rows === 0) {
            // 這裡可視需要紀錄 log
            error_log("Image URL: " . $card['image_url']);
            error_log("Background URL: " . $card['background_image_url']);

            // 插入時多了 background_image_url 欄位
            $stmt = $conn->prepare("
                INSERT INTO booklet (
                    user_id, 
                    pokemon_name, 
                    rarity, 
                    type1, 
                    type2, 
                    image_url,
                    background_image_url
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            // 參數綁定：i (user_id) + sssss (5個字串欄位) + s (第6個字串欄位) 共7個
            $stmt->bind_param(
                "issssss",
                $user_id,
                $card['name'],
                $card['rarity'],
                $card['type1'],
                $card['type2'],
                $card['image_url'],
                $card['background_image_url']
            );

            if (!$stmt->execute()) {
                throw new Exception("新增卡片失敗：" . $stmt->error);
            }
        }
    }
} catch (Exception $e) {
    $success = false;
    $message = $e->getMessage();

    // 确保返回的消息是 JSON 格式
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit(); // 确保不再执行后续代码
}

echo json_encode([
    'success' => $success,
    'message' => $message
]);

$conn->close();
?>