<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include __DIR__ . '/includes/db.php';

$ticket_id = $_GET['ticket_id'] ?? null;
if (!$ticket_id) {
    die('ticket_id eksik.');
}

// try autoload in likely locations
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
];

$autoloadFound = false;
foreach ($autoloadPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound || !class_exists('Dompdf\\Dompdf')) {
    die("PDF kütüphanesi (dompdf) yüklenmemiş veya autoload bulunamadı. Proje kökünde:\ncomposer require dompdf/dompdf");
}

use Dompdf\Dompdf;
use Dompdf\Options;

// Dompdf seçenekleri: UTF-8 uyumlu font ve HTML5 parser
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans'); // DejaVu Sans dompdf paketinde mevcuttur

$dompdf = new Dompdf($options);

// Ticket verisini al
$stmt = $db->prepare("SELECT t.*, tr.departure_city, tr.destination_city, tr.departure_time, c.name AS company_name, GROUP_CONCAT(bs.seat_number) AS seats FROM Tickets t JOIN Trips tr ON t.trip_id = tr.id JOIN Bus_Company c ON tr.company_id = c.id LEFT JOIN Booked_Seats bs ON bs.ticket_id = t.id WHERE t.id = :id AND t.user_id = :user GROUP BY t.id");
$stmt->execute(['id' => $ticket_id, 'user' => $_SESSION['user_id']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ticket) {
    die('Bilet bulunamadı veya bu bilet size ait değil.');
}

// HTML oluştur (meta charset ve font-family ile)
$html = '<!doctype html><html><head><meta charset="utf-8"><style>body{font-family: "DejaVu Sans", "DejaVu Sans Condensed", sans-serif;}</style></head><body>';
$html .= '<h2>Bilet Detayları</h2>';
$html .= '<p><strong>Firma:</strong> ' . htmlspecialchars($ticket['company_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
$html .= '<p><strong>Sefer:</strong> ' . htmlspecialchars($ticket['departure_city'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' → ' . htmlspecialchars($ticket['destination_city'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
$html .= '<p><strong>Kalkış:</strong> ' . date('d.m.Y H:i', strtotime($ticket['departure_time'])) . '</p>';
$html .= '<p><strong>Koltuk(lar):</strong> ' . ($ticket['seats'] ?? '-') . '</p>';
$html .= '<p><strong>Fiyat:</strong> ' . htmlspecialchars($ticket['total_price'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' TL</p>';
$html .= '<p><strong>Durum:</strong> ' . htmlspecialchars($ticket['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
$html .= '</body></html>';

// yükle ve render et
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('bilet_' . $ticket['id'] . '.pdf', ['Attachment' => false]);
exit;