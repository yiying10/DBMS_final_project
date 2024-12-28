<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

try {
    $db = new PDO('mysql:host=localhost;dbname=pokemon;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user_id = $_SESSION['user_id'];

    // 檢查時間差
    $sql = "SELECT TIMESTAMPDIFF(SECOND, last_claim_time, NOW()) as time_diff, coins FROM account WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user['time_diff'] < 5) {
        echo json_encode([
            'success' => false,
            'message' => "還需等待 " . (5 - $user['time_diff']) . " 秒"
        ]);
        exit;
    }

    // 更新代幣數量和領取時間
    $new_coins = $user['coins'] + 1;
    $sql = "UPDATE account SET coins = ?, last_claim_time = NOW() WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$new_coins, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'coins' => $new_coins,
            'message' => '成功領取皮卡幣！'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '領取失敗']);
    }

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '領取失敗']);
}
?>