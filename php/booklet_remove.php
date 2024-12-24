<?php
session_start();
if (!isset($_POST['booklet_id']) || empty($_POST['booklet_id'])) {
    echo json_encode(['status' => 'error', 'message' => '缺少必要參數']);
    exit;
}

$booklet_id = intval($_POST['booklet_id']);

try {
    require_once('db_connect.php');

    // MySQLi 開始事務
    $conn->autocommit(FALSE);

    // 開始交易
    $conn->begin_transaction();

    // 先刪除相關的子表數據（如果有的話）
    $stmt = $conn->prepare("DELETE FROM booklet_details WHERE booklet_id = ?");
    $stmt->execute([$booklet_id]);

    // 刪除主表數據
    $stmt = $conn->prepare("DELETE FROM booklets WHERE id = ?");
    $result = $stmt->execute([$booklet_id]);

    if ($result) {
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => '手冊已成功刪除']);
    } else {
        throw new Exception('刪除失敗');
    }

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['status' => 'error', 'message' => '刪除過程中發生錯誤：' . $e->getMessage()]);
}
?>