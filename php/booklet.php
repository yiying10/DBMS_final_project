<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 資料庫連線
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("資料庫連接失敗：" . $conn->connect_error);
}

// 獲取用戶的卡冊內容
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM booklet WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cards = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的卡冊</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/booklet.css">
</head>

<body data-page="booklet">
    <nav class="sidebar">
        <ul>
            <li><a href="../php/home.php">首頁</a></li>
            <li><a href="../php/generate.php">卡牌生成區</a></li>
            <li><a href="../php/illustrated_book.php">卡牌圖鑑</a></li>
            <li><a href="../php/pakage.php" id="pakage-link">抽卡區</a></li>
            <li><a href="../php/reference.php">關於我們</a></li>
            <li><a href="../php/booklet.php" id="booklet-link">我的卡冊</a></li>
        </ul>
    </nav>

    <main class="content">
        <h1>我的卡冊</h1>

        <div class="card-container">
            <?php foreach ($cards as $card): ?>
                <div class="card-item" data-card-id="<?php echo $card['id']; ?>">
                    <img src="<?php echo htmlspecialchars($card['image_url']); ?>"
                        alt="<?php echo htmlspecialchars($card['pokemon_name']); ?>">
                    <div class="card-info">
                        <p class="card-name"><?php echo htmlspecialchars($card['pokemon_name']); ?></p>
                        <p class="card-rarity"><?php echo htmlspecialchars($card['rarity']); ?></p>
                        <p class="card-type">
                            <?php echo htmlspecialchars($card['type1']); ?>
                            <?php echo $card['type2'] ? "/ " . htmlspecialchars($card['type2']) : ""; ?>
                        </p>
                        <button class="remove-button" onclick="removeCard(<?php echo $card['id']; ?>)">移除</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        function removeCard(cardId) {
            if (!confirm('確定要移除這張卡片嗎？')) {
                return;
            }

            const formData = new FormData();
            formData.append('card_id', cardId);

            fetch('../php/booklet_remove.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 移除卡片元素
                        const cardElement = document.querySelector(`[data-card-id="${cardId}"]`);
                        if (cardElement) {
                            cardElement.remove();
                        }
                        alert('卡片已成功移除！');
                    } else {
                        alert(data.message || '移除失敗，請稍後再試。');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('發生錯誤，請稍後再試。');
                });
        }
    </script>
</body>

</html>