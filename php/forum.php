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
$is_logged_in = isset($_SESSION['user_name']);
$coins = 0; // 預設代幣數量為0

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT COALESCE(coins, 0) as coins FROM account WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($coins);
    $stmt->fetch();
    $stmt->close();
}

if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

// 發佈貼文功能
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && empty($_POST['post_id'])) {
    $content = trim($_POST['content']);
    $user_name = $_SESSION['user_name'];

    if (empty($content)) {
        die("貼文內容不能為空！");
    }

    $stmt = $db->prepare("INSERT INTO forum_posts (user_name, content, created_at) VALUES (?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("ss", $user_name, $content);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: forum.php");
            exit();
        } else {
            die("插入貼文失敗：" . $stmt->error);
        }
    } else {
        die("準備插入語句失敗：" . $db->error);
    }
}

// 提交評論功能
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['comment_content'])) {
    $post_id = $_POST['post_id'];
    $content = trim($_POST['comment_content']);
    $user_name = $_SESSION['user_name'];

    if (empty($content)) {
        die("評論內容不能為空！");
    }

    $stmt = $db->prepare("SELECT id FROM forum_posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        die("無效的 post_id！");
    }
    $stmt->close();

    $stmt = $db->prepare("INSERT INTO comments (post_id, user_name, content, created_at) VALUES (?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("iss", $post_id, $user_name, $content);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: forum.php");
            exit();
        } else {
            die("插入評論失敗：" . $stmt->error);
        }
    } else {
        die("準備插入語句失敗：" . $db->error);
    }
}

// 刪除貼文及其相關留言
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $post_id = $_POST['post_id'];
    $user_name = $_SESSION['user_name'];

    // 刪除留言
    $stmt = $db->prepare("DELETE FROM comments WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    if (!$stmt->execute()) {
        die("刪除留言失敗：" . $stmt->error);
    }
    $stmt->close();

    // 刪除貼文
    $stmt = $db->prepare("DELETE FROM forum_posts WHERE id = ? AND user_name = ?");
    $stmt->bind_param("is", $post_id, $user_name);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: forum.php");
        exit();
    } else {
        die("刪除貼文失敗：" . $stmt->error);
    }
}

// 獲取所有貼文和評論
$posts = [];
$result = $db->query("SELECT * FROM forum_posts ORDER BY created_at DESC");
if ($result) {
    while ($post = $result->fetch_assoc()) {
        $post_id = $post['id'];
        $comments_result = $db->query("SELECT * FROM comments WHERE post_id = $post_id ORDER BY created_at ASC");
        $post['comments'] = $comments_result ? $comments_result->fetch_all(MYSQLI_ASSOC) : [];
        $posts[] = $post;
    }
    $result->close();
}

$db->close();
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R~Pokemon Go！論壇</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/forum.css">
</head>

<body data-page="forum">
    <header>
        <div class="user-info">
            <ul>
                <?php if ($is_logged_in): ?>
                    <div class="user-info">
                        <span class="coin-display">
                            <img src="../images/coin-icon.png" alt="代幣" class="coin-icon">
                            <span id="coin-amount"><?php echo $coins; ?></span>
                        </span>
                        <p class="welcome">歡迎, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        <a href="../php/logout.php">
                            <button class="login-button">登出</button>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="../html/login.html" class="login-button-link">
                        <button class="login-button">登入 / 註冊</button>
                    </a>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <nav class="sidebar">
        <ul>
            <li><a href="../php/home.php">首頁</a></li>
            <li><a href="../php/custom_card.php" id="custom-card-link">新增寶可夢</a></li>
            <li><a href="../php/generate.php" id="card-generation-link">卡牌生成區</a></li>
            <li><a href="../php/illustrated_book.php">寶可夢圖鑑</a></li>
            <li><a href="../php/pakage.php" id="pakage-link">抽卡區</a></li>
            <li><a href="../php/booklet.php" id="booklet-link">卡冊</a></li>
            <li><a href="../php/forum.php" id="forum-link">論壇</a></li>
            <li><a href="../php/reference.php">關於我們</a></li>
        </ul>
    </nav>

    <main class="content">
        <h1>寶可夢論壇</h1>

        <!-- 發佈貼文 -->
        <?php if ($is_logged_in): ?>
            <div class="post-form">
                <form action="forum.php" method="post">
                    <textarea name="content" placeholder="分享你的想法..." required></textarea>
                    <button type="submit" class="submit-button">發布</button>
                </form>
            </div>
        <?php else: ?>
            <div class="login-notice">
                <p>請先登入後再發文</p>
            </div>
        <?php endif; ?>

        <!-- 貼文區 -->
        <div class="posts-container">
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-header">
                        <span class="username"><?php echo htmlspecialchars($post['user_name']); ?></span>
                        <span class="post-time"><?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></span>

                        <!-- 編輯與刪除按鈕 -->
                        <?php if ($post['user_name'] === $_SESSION['user_name']): ?>
                            <div class="post-actions">
                                <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="edit-btn">編輯</a>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" name="delete_post" class="delete-btn"
                                        onclick="return confirm('確定要刪除這篇貼文及其留言嗎？')">刪除</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                    </div>

                    <!-- 評論區域 -->
                    <div class="comments-section">
                        <?php foreach ($post['comments'] as $comment): ?>
                            <div class="comment">
                                <div class="comment-header">
                                    <span class="username"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                    <span
                                        class="comment-time"><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></span>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- 發佈評論 -->
                        <?php if ($is_logged_in): ?>
                            <form class="comment-form" method="post">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <textarea name="comment_content" placeholder="發表評論..." required></textarea>
                                <button type="submit" class="comment-btn">評論</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>

</html>