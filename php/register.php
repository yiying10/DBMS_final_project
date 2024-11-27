<?php
// 開啟錯誤訊息顯示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 資料庫連線設定
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

// 建立連接
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連線
if ($conn->connect_error) {
    die("資料庫連線失敗：" . $conn->connect_error);
}

// 確認表單是否提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_name = $_POST['user_name'];
    $account = $_POST['account'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // 密碼加密

    // 檢查帳號是否已存在
    $checkUserQuery = "SELECT * FROM account WHERE account = ?";
    $stmt = $conn->prepare($checkUserQuery);

    if ($stmt) {
        $stmt->bind_param("s", $account);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // 帳號已存在，顯示錯誤訊息
            echo "<script>
                    alert('此帳號已註冊！');
                    window.history.back();
                  </script>";
        } else {
            // 帳號不存在，插入新資料
            $insertUserQuery = "INSERT INTO account (user_name, account, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insertUserQuery);
            $stmt->bind_param("sss", $user_name, $account, $password);

            if ($stmt->execute()) {
                echo "<script>
                        alert('註冊成功！');
                        window.location.href = '../php/home.php';
                      </script>";
            } else {
                echo "錯誤：" . $stmt->error;
            }
        }
        $stmt->close();
    } else {
        echo "錯誤：" . $conn->error;
    }
}

$conn->close();
?>