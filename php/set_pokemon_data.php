<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $_SESSION['selected_pokemon'] = $data;
    $_SESSION['has_generated'] = false;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>