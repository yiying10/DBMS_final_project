<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}

$name = $_GET['name'];
$stmt = $conn->prepare("SELECT * FROM pokemon_images WHERE Name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();
$pokemon = $result->fetch_assoc();

echo json_encode($pokemon);