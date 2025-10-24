<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit;
}
include '../includes/db.php';

$ticket_id = $_GET['ticket_id'];

// fetch ticket with created_at (t.* already includes created_at)
$stmt = $db->prepare("SELECT t.*, tr.departure_time FROM Tickets t JOIN Trips tr ON t.trip_id = tr.id WHERE t.id = :id AND t.user_id = :user AND t.status = 'active'");
$stmt->execute(['id' => $ticket_id, 'user' => $_SESSION['user_id']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) die("Bilet bulunamadı.");

$now = time();
$departure = strtotime($ticket['departure_time']);
if ($departure - $now < 3600) {  // 1 saat
    die("İptal için geç kaldınız.");
}

// İşlemleri transaction ile yap
$db->beginTransaction();
try {
    // Eğer bu bilet satın alma sırasında kupon kullanıldıysa,
    // buy_ticket kodunda Ticket.created_at ile User_Coupons.created_at aynı timestamp olarak kaydediliyordu.
    // Bu nedenle aynı created_at + user_id kombinasyonuyla User_Coupons kaydı aranacak.
    $couponRow = $db->prepare("SELECT coupon_id FROM User_Coupons WHERE user_id = :user AND created_at = :created_at LIMIT 1");
    $couponRow->execute(['user' => $_SESSION['user_id'], 'created_at' => $ticket['created_at']]);
    $coupon = $couponRow->fetch(PDO::FETCH_ASSOC);

    // ticket iptal et
    $db->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = :id")->execute(['id' => $ticket_id]);
    // iade
    $db->prepare("UPDATE User SET balance = balance + :price WHERE id = :id")->execute(['price' => $ticket['total_price'], 'id' => $_SESSION['user_id']]);
    // booked seats sil
    $db->prepare("DELETE FROM Booked_Seats WHERE ticket_id = :ticket_id")->execute(['ticket_id' => $ticket_id]);

    if ($coupon && !empty($coupon['coupon_id'])) {
        // kullanım limitini geri artır
        $db->prepare("UPDATE Coupons SET usage_limit = usage_limit + 1 WHERE id = :id")->execute(['id' => $coupon['coupon_id']]);
        // ilgili User_Coupons kaydını temizle (aynı user/coupon/saat kombinasyonu genelde tekil olmalı)
        $del = $db->prepare("DELETE FROM User_Coupons WHERE user_id = :user AND coupon_id = :coupon_id AND created_at = :created_at");
        $del->execute(['user' => $_SESSION['user_id'], 'coupon_id' => $coupon['coupon_id'], 'created_at' => $ticket['created_at']]);
    }

    $db->commit();
    header("Location: my_tickets.php");
    exit;
} catch (Exception $e) {
    $db->rollBack();
    die("İptal sırasında hata oluştu.");
}
?>