<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit;
}
include '../includes/db.php';

$trip_id = $_GET['trip_id'];
$stmt = $db->prepare("SELECT * FROM Trips WHERE id = :id");
$stmt->execute(['id' => $trip_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) die("Sefer bulunamadı.");

// occupied seats for active tickets of this trip
$occupied_stmt = $db->prepare("SELECT bs.seat_number FROM Booked_Seats bs JOIN Tickets t ON bs.ticket_id = t.id WHERE t.trip_id = :trip_id AND t.status = 'active'");
$occupied_stmt->execute(['trip_id' => $trip_id]);
$occupied = $occupied_stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $seat_number = (int)$_POST['seat_number'];
    if ($seat_number < 1 || $seat_number > (int)$trip['capacity'] || in_array($seat_number, $occupied)) {
        echo '<div class="alert alert-danger">Geçersiz veya dolu koltuk seçildi.</div>';
    } else {
        // validate coupon early so user gets immediate feedback if code is invalid
        $coupon_code = trim($_POST['coupon_code'] ?? '');
        $validated_coupon = null;
        if ($coupon_code !== '') {
            $checkStmt = $db->prepare("SELECT * FROM Coupons WHERE code = :code AND expire_date > CURRENT_TIMESTAMP AND usage_limit > 0 AND (company_id = :comp OR company_id IS NULL)");
            $checkStmt->execute(['code' => $coupon_code, 'comp' => $trip['company_id']]);
            $validated_coupon = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if (!$validated_coupon) {
                echo '<div class="alert alert-danger">Geçersiz veya kullanılamayan kupon kodu.</div>';
                // do not proceed with purchase
                return;
            }
        }

        $db->beginTransaction();
        try {
            $price = (float)$trip['price'];
            $coupon_id = null;
            if ($validated_coupon) {
                $price = $price - $validated_coupon['discount'];
                $coupon_id = $validated_coupon['id'];
                $db->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = :id")->execute(['id' => $coupon_id]);
            }

            $user_stmt = $db->prepare("SELECT balance FROM User WHERE id = :id");
            $user_stmt->execute(['id' => $_SESSION['user_id']]);
            $balance = (float)$user_stmt->fetchColumn();

            if ($balance < $price) {
                $db->rollBack();
                echo '<div class="alert alert-danger">Yetersiz bakiye.</div>';
            } else {
                $ticket_id = bin2hex(random_bytes(16));
                $created_at = date('Y-m-d H:i:s');

                $insert_ticket = $db->prepare("INSERT INTO Tickets (id, trip_id, user_id, status, total_price, created_at) VALUES (:id, :trip, :user, 'active', :price, :created_at)");
                $insert_ticket->execute(['id' => $ticket_id, 'trip' => $trip_id, 'user' => $_SESSION['user_id'], 'price' => $price, 'created_at' => $created_at]);

                $booked_seat_id = bin2hex(random_bytes(16));
                $insert_seat = $db->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at) VALUES (:id, :ticket, :seat, :created_at)");
                $insert_seat->execute(['id' => $booked_seat_id, 'ticket' => $ticket_id, 'seat' => $seat_number, 'created_at' => $created_at]);

                if ($coupon_id) {
                    $user_coupon_id = bin2hex(random_bytes(16));
                    $insert_user_coupon = $db->prepare("INSERT INTO User_Coupons (id, coupon_id, user_id, created_at) VALUES (:id, :coupon, :user, :created_at)");
                    $insert_user_coupon->execute(['id' => $user_coupon_id, 'coupon' => $coupon_id, 'user' => $_SESSION['user_id'], 'created_at' => $created_at]);
                }

                $db->prepare("UPDATE User SET balance = balance - :price WHERE id = :id")->execute(['price' => $price, 'id' => $_SESSION['user_id']]);

                $db->commit();
                header("Location: my_tickets.php");
                exit;
            }
        } catch (Exception $e) {
            $db->rollBack();
            echo '<div class="alert alert-danger">Satın alma sırasında hata oluştu.</div>';
        }
    }
}
include '../includes/header.php';
?>

<h2>Bilet Satın Al - <?php echo $trip['departure_city'] . ' -> ' . $trip['destination_city']; ?></h2>
<form method="POST">
    <div class="mb-3">
        <label>Koltuk Seç</label>
        <select name="seat_number" class="form-control">
            <?php for ($i = 1; $i <= $trip['capacity']; $i++): ?>
                <option value="<?php echo $i; ?>" <?php if (in_array($i, $occupied)) echo 'disabled'; ?>><?php echo $i; ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="mb-3">
        <label>Kupon Kodu (Opsiyonel)</label>
        <input type="text" name="coupon_code" class="form-control">
    </div>
    <div class="mb-3">
        <strong>Fiyat: <?php echo $trip['price']; ?> TL</strong>
    </div>
    <button type="submit" class="btn btn-primary">Satın Al</button>
</form>

<?php include '../includes/footer.php'; ?>