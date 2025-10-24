<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

$departure_city = $_GET['departure_city'] ?? '';
$destination_city = $_GET['destination_city'] ?? '';

$trips = [];
if ($departure_city && $destination_city) {
    $stmt = $db->prepare("SELECT t.*, b.name AS company_name FROM Trips t JOIN Bus_Company b ON t.company_id = b.id WHERE departure_city = :dep AND destination_city = :arr");
    $stmt->execute(['dep' => $departure_city, 'arr' => $destination_city]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<h2>Sefer Ara</h2>
<form method="GET">
    <div class="row">
        <div class="col-md-4">
            <label>Kalkış Şehri</label>
            <input type="text" name="departure_city" class="form-control" value="<?php echo htmlspecialchars($departure_city); ?>">
        </div>
        <div class="col-md-4">
            <label>Varış Şehri</label>
            <input type="text" name="destination_city" class="form-control" value="<?php echo htmlspecialchars($destination_city); ?>">
        </div>
        <div class="col-md-4 mt-4">
            <button type="submit" class="btn btn-primary">Ara</button>
        </div>
    </div>
</form>

<?php if ($trips): ?>
    <h3>Seferler</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Firma</th>
                <th>Kalkış Şehri</th>
                <th>Varış Şehri</th>
                <th>Kalkış Zamanı</th>
                <th>Fiyat</th>
                <th>Kapasite</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trips as $trip): ?>
                <tr>
                    <td><?php echo htmlspecialchars($trip['company_name']); ?></td>
                    <td><?php echo htmlspecialchars($trip['departure_city']); ?></td>
                    <td><?php echo htmlspecialchars($trip['destination_city']); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?></td>
                    <td><?php echo $trip['price']; ?> TL</td>
                    <td><?php echo $trip['capacity']; ?></td>
                    <td>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'user'): ?>
                            <a href="user/buy_ticket.php?trip_id=<?php echo $trip['id']; ?>" class="btn btn-success">Bilet Al</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-warning">Giriş Yap</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>