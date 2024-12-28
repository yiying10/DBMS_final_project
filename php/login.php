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

session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account = $_POST['username'];
    $password = $_POST['password'];

    // 驗證資料庫中的帳號與密碼
    $query = "SELECT * FROM account WHERE account = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $account);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_name'] = $row['user_name'];
            $_SESSION['user_id'] = $row['user_id'];
            header("Location: ../php/home.php");
            exit();
        }
    }
    echo "<script>alert('帳號或密碼錯誤！'); window.location.href='../html/login.html';</script>";
}

?>