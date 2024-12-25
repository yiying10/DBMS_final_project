<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '使用者未登入']);
    exit;
}

$user_id = $_SESSION['user_id'];

// 获取传入的扣费数据
$data = json_decode(file_get_contents('php://input'), true);
$cost = $data['cost'] ?? 0;

if ($cost <= 0) {
    echo json_encode(['success' => false, 'message' => '無效的扣款金額']);
    exit;
}

// 数据库连接
$conn = new mysqli("localhost", "root", "", "Pokemon");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '數據庫連接失敗']);
    exit;
}

// 扣除金币，检查余额是否充足
$sql = "UPDATE account SET coins = coins - ? WHERE user_id = ? AND coins >= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $cost, $user_id, $cost);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // 更新成功，返回新余额
    $stmt->close();
    $sql_balance = "SELECT coins FROM account WHERE user_id = ?";
    $stmt_balance = $conn->prepare($sql_balance);
    $stmt_balance->bind_param("i", $user_id);
    $stmt_balance->execute();
    $stmt_balance->bind_result($new_balance);
    $stmt_balance->fetch();

    echo json_encode(['success' => true, 'message' => '付款成功', 'new_balance' => $new_balance]);
    $stmt_balance->close();
} else {
    echo json_encode(['success' => false, 'message' => '餘額不足']);
}

$conn->close();
?>
