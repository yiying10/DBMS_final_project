<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "表單提交成功！";
    exit();
} else {
    echo "非 POST 請求";
    exit();
}

<?php
// 資料庫連線設定
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連線
if ($conn->connect_error) {
    die("資料庫連線失敗：" . $conn->connect_error);
}

// 確認表單是否提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // 密碼加密

    // 檢查帳號是否已存在
    $checkUserQuery = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($checkUserQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 帳號已存在，重新導向並顯示錯誤訊息
        header("Location: register.html?error=1");
    } else {
        // 帳號不存在，插入新資料
        $insertUserQuery = "INSERT INTO users (name, username, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertUserQuery);
        $stmt->bind_param("sss", $name, $username, $password);

        if ($stmt->execute()) {
            // 註冊成功，重新導向並顯示成功訊息
            header("Location: register.html?success=1");
        } else {
            echo "錯誤：" . $stmt->error;
        }
    }

    $stmt->close();
}

$conn->close();
?>
