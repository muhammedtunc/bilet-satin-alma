<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit;
}
include '../includes/db.php';

$stmt = $db->prepare("SELECT t.id, t.total_price, t.status, t.created_at, tr.departure_city, tr.destination_city, tr.departure_time, GROUP_CONCAT(bs.seat_number) AS seats FROM Tickets t JOIN Trips tr ON t.trip_id = tr.id LEFT JOIN Booked_Seats bs ON bs.ticket_id = t.id WHERE t.user_id = :user AND t.status = 'active' GROUP BY t.id");
$stmt->execute(['user' => $_SESSION['user_id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user_stmt = $db->prepare("SELECT balance FROM User WHERE id = :id");
$user_stmt->execute(['id' => $_SESSION['user_id']]);
$balance = $user_stmt->fetchColumn();

include '../includes/header.php';
?>

<h2>Hesabım</h2>
<p>Bakiye: <?php echo htmlspecialchars($balance); ?> TL</p>

<h3>Biletlerim</h3>
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Sefer</th>
            <th>Zaman</th>
            <th>Koltuk(lar)</th>
            <th>Fiyat</th>
            <th>Durum</th>
            <th>İşlem</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tickets as $ticket): ?>
            <tr>
                <td><?php echo htmlspecialchars($ticket['id']); ?></td>
                <td><?php echo htmlspecialchars($ticket['departure_city'] . ' -> ' . $ticket['destination_city']); ?></td>
                <td><?php echo date('d.m.Y H:i', strtotime($ticket['departure_time'])); ?></td>
                <td><?php echo htmlspecialchars($ticket['seats']); ?></td>
                <td><?php echo htmlspecialchars($ticket['total_price']); ?> TL</td>
                <td><?php echo htmlspecialchars($ticket['status']); ?></td>
                <td>
                    <a href="../generate_pdf.php?ticket_id=<?php echo urlencode($ticket['id']); ?>" class="btn btn-secondary">PDF</a>
                    <a href="cancel_ticket.php?ticket_id=<?php echo urlencode($ticket['id']); ?>" class="btn btn-danger" onclick="return confirm('İptal edilsin mi?');">İptal</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>