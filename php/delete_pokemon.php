<?php
session_start();
header('Content-Type: application/json');

// 檢查是否登入
if (!isset($_SESSION['user_name'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

// 資料庫連線設定
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '資料庫連接失敗']);
    exit;
}

$conn->set_charset("utf8mb4");

// 獲取POST數據
$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;
$name = isset($data['name']) ? $data['name'] : '';

// 檢查ID是否大於898
if ($id <= 898) {
    echo json_encode(['success' => false, 'message' => '無法刪除原始寶可夢']);
    exit;
}

// 開始事務
$conn->begin_transaction();

try {
    // 1. 先找到該寶可夢的ability (使用精確匹配)
    $sql = "SELECT Ability FROM ability WHERE Name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $abilities = [];
    while ($row = $result->fetch_assoc()) {
        $abilities[] = $row['Ability'];
    }

    // 2. 刪除ability_description中的記錄
    if (!empty($abilities)) {
        $sql = "DELETE FROM ability_description WHERE Name IN (" .
            str_repeat('?,', count($abilities) - 1) . '?)';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($abilities)), ...$abilities);
        $stmt->execute();
    }

    // 3. 刪除ability表中的記錄
    $sql = "DELETE FROM ability WHERE Name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();

    // 4. 刪除df_pokemon表中的記錄
    $sql = "DELETE FROM df_pokemon WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // 5. 刪除圖片文件
    $imagePath = "../images/pokemon_images/" . strtolower(str_replace(' ', '-', $name)) . ".png";
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }

    // 提交事務
    $conn->commit();
    echo json_encode(['success' => true, 'message' => '寶可夢已成功刪除']);

} catch (Exception $e) {
    // 發生錯誤時回滾事務
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => '刪除過程中發生錯誤：' . $e->getMessage()]);
}

$conn->close();
?>