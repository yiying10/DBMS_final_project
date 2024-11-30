<?php
header('Content-Type: image/png');
header('Access-Control-Allow-Origin: *');

if (isset($_GET['url'])) {
    $imageUrl = urldecode($_GET['url']);
    echo file_get_contents($imageUrl);
}
?> 