<?php
session_start();

// 資料庫連線設置
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

$db = new mysqli($servername, $username, $password, $dbname);

// 確認連線是否成功
if ($db->connect_error) {
    die("資料庫連接失敗：" . $db->connect_error);
}

// 檢查是否已登入
if (!isset($_SESSION['user_name'])) {
    header('Location: login.php');
    exit;
}

$user_name = $_SESSION['user_name'];

// 處理貼文更新
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'];
    $content = $_POST['content'];
    
    $stmt = $db->prepare("UPDATE forum_posts SET content = ? WHERE id = ? AND user_name = ?");
    if ($stmt) {
        $stmt->bind_param("sis", $content, $post_id, $user_name);
        if ($stmt->execute()) {
            header('Location: forum.php');
            exit;
        } else {
            die("更新貼文失敗：" . $stmt->error);
        }
        $stmt->close();
    } else {
        die("準備 SQL 語句失敗：" . $db->error);
    }
}

// 獲取貼文資料
$post_id = $_GET['id'];
$stmt = $db->prepare("SELECT * FROM forum_posts WHERE id = ? AND user_name = ?");
if ($stmt) {
    $stmt->bind_param("is", $post_id, $user_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    if (!$post) {
        header('Location: forum.php');
        exit;
    }
    $stmt->close();
} else {
    die("查詢貼文失敗：" . $db->error);
}

$db->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>編輯貼文</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/edit_post.css">
</head>
<body>
    <div class="edit-post-form">
        <h2>編輯貼文</h2>
        <form method="post">
            <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post['id']); ?>">
            <textarea name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>
            <button type="submit">更新</button>
            <a href="forum.php" class="cancel-btn">取消</a>
        </form>
    </div>
</body>
</html>
