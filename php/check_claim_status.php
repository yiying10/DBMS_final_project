<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['canClaim' => false, 'message' => '請先登入']);
    exit;
}

try {
    $db = new PDO('mysql:host=localhost;dbname=pokemon;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_id = $_SESSION['user_id'];
    
    // 獲取當前時間和最後領取時間
    $sql = "SELECT TIMESTAMPDIFF(SECOND, last_claim_time, NOW()) as time_diff FROM account WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $time_diff = $result['time_diff'];
    
    if ($time_diff >= 5) {
        // 如果已經過了5秒或更多
        echo json_encode(['canClaim' => true]);
    } else {
        // 如果還沒到5秒，計算剩餘時間
        $remaining = 5 - $time_diff;
        echo json_encode([
            'canClaim' => false, 
            'remainingTime' => $remaining
        ]);
    }
    
} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['canClaim' => false, 'message' => '檢查狀態失敗']);
}
?> 