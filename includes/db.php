<?php
try {
    $db = new PDO('sqlite:/var/www/html/db/BiletSatinAlmaPlatformuDB');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    error_log("Veritabanı hatası: " . $e->getMessage(), 3, '/var/www/html/error.log');
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>