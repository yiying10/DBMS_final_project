<?php
session_start();
if (isset($_GET['name'])) {
    $_SESSION['selected_pokemon'] = $_GET['name'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}