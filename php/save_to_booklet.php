<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '資料庫連接失敗']);
    exit();
}

$user_id = $_SESSION['user_id'];
$pokemon_name = $data['pokemonName'];
$background = $data['background'];

$stmt = $conn->prepare("INSERT INTO booklet (user_id, pokemon_name, card_background) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $pokemon_name, $background);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '保存失敗']);
}

$stmt->close();
$conn->close();