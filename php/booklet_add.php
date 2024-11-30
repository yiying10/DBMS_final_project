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
        $stmt = $conn->prepare("INSERT INTO booklet (user_id, pokemon_name, rarity, type1, type2, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "isssss",
            $user_id,
            $card['name'],
            $card['rarity'],
            $card['type1'],
            $card['type2'],
            $card['image_url']
        );

        if (!$stmt->execute()) {
            throw new Exception("新增卡片失敗");
        }
    }
} catch (Exception $e) {
    $success = false;
    $message = $e->getMessage();
}

echo json_encode([
    'success' => $success,
    'message' => $message
]);

$conn->close();
?>