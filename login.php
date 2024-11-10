<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

// 建立資料庫連線
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("資料庫連接失敗：" . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account = $_POST['username']; // 假設登入表單中的帳號欄位名稱為 'username'
    $password = $_POST['password'];

    // 使用正確的欄位名稱進行查詢
    $query = "SELECT * FROM account WHERE account = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("SQL 語法錯誤：" . $conn->error);
    }

    $stmt->bind_param("s", $account);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            echo "<script>alert('登入成功！'); window.location.href = 'index.html';</script>";
            exit();
        } else {
            echo "<script>alert('帳號錯誤或密碼錯誤'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('帳號錯誤或密碼錯誤'); window.history.back();</script>";
    }

    $stmt->close();
}
$conn->close();
?>
