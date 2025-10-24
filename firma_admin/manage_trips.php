<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'company_admin') {
    header("Location: ../login.php");
    exit;
}
include '../includes/db.php';

$company_id = $_SESSION['company_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Helper to normalize datetime-local (YYYY-MM-DDTHH:MM) to 'Y-m-d H:i:s'
    $normalize_dt = function($input) {
        $input = trim($input);
        if ($input === '') return null;
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $input);
        if ($dt === false) {
            try {
                $dt = new DateTime($input);
            } catch (Exception $e) {
                return null;
            }
        }
        return $dt->format('Y-m-d H:i:s');
    };

    if (isset($_POST['add'])) {
        // Required fields validation
        $dep_city = trim($_POST['departure_city'] ?? '');
        $dest_city = trim($_POST['destination_city'] ?? '');
        $dep_input = $_POST['departure_time'] ?? '';
        $arr_input = $_POST['arrival_time'] ?? '';
        $price = $_POST['price'] ?? null;
        $capacity = $_POST['capacity'] ?? null;

        $dep_time = $normalize_dt($dep_input);
        $arr_time = $normalize_dt($arr_input);

        if ($dep_city === '' || $dest_city === '' || $dep_time === null || $arr_time === null || $price === null || $capacity === null) {
            $error = 'Tüm zorunlu alanları doldurun ve tarih/saat formatını kontrol edin.';
        } else {
            $id = bin2hex(random_bytes(16));
            $created_date = date('Y-m-d H:i:s');

            $stmt = $db->prepare("INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity, created_date) VALUES (:id, :comp, :dep, :dest, :dep_time, :arr_time, :price, :capacity, :created_date)");
            $stmt->execute([
                'id' => $id,
                'comp' => $company_id,
                'dep' => $dep_city,
                'dest' => $dest_city,
                'dep_time' => $dep_time,
                'arr_time' => $arr_time,
                'price' => $price,
                'capacity' => $capacity,
                'created_date' => $created_date
            ]);
        }
    } elseif (isset($_POST['update'])) {
        $id = $_POST['id'] ?? null;
        $dep_city = trim($_POST['departure_city'] ?? '');
        $dest_city = trim($_POST['destination_city'] ?? '');
        $dep_input = $_POST['departure_time'] ?? '';
        $arr_input = $_POST['arrival_time'] ?? '';
        $price = $_POST['price'] ?? null;
        $capacity = $_POST['capacity'] ?? null;

        $dep_time = $normalize_dt($dep_input);
        $arr_time = $normalize_dt($arr_input);

        if (!$id || $dep_city === '' || $dest_city === '' || $dep_time === null || $arr_time === null || $price === null || $capacity === null) {
            $error = 'Güncelleme için tüm alanları doğru doldurun.';
        } else {
            $stmt = $db->prepare("UPDATE Trips SET departure_city = :dep, destination_city = :dest, departure_time = :dep_time, arrival_time = :arr_time, price = :price, capacity = :capacity WHERE id = :id AND company_id = :comp");
            $stmt->execute([
                'dep' => $dep_city,
                'dest' => $dest_city,
                'dep_time' => $dep_time,
                'arr_time' => $arr_time,
                'price' => $price,
                'capacity' => $capacity,
                'id' => $id,
                'comp' => $company_id
            ]);
        }
    } elseif (isset($_POST['delete'])) {
        $db->prepare("DELETE FROM Trips WHERE id = :id AND company_id = :comp")->execute(['id' => $_POST['id'], 'comp' => $company_id]);
    }
}

$stmt = $db->prepare("SELECT * FROM Trips WHERE company_id = :comp");
$stmt->execute(['comp' => $company_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<h2>Seferleri Yönet</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<h3>Yeni Sefer Ekle</h3>
<form method="POST" class="mb-3">
    <input type="hidden" name="add" value="1">
    <div class="row">
        <div class="col"><input type="text" name="departure_city" placeholder="Kalkış Şehri" class="form-control" required></div>
        <div class="col"><input type="text" name="destination_city" placeholder="Varış Şehri" class="form-control" required></div>
        <div class="col"><input type="datetime-local" name="departure_time" class="form-control" required></div>
        <div class="col"><input type="datetime-local" name="arrival_time" class="form-control" required></div>
        <div class="col"><input type="number" name="price" placeholder="Fiyat" class="form-control" required></div>
        <div class="col"><input type="number" name="capacity" placeholder="Kapasite" class="form-control" required></div>
        <div class="col"><button type="submit" class="btn btn-success">Ekle</button></div>
    </div>
</form>

<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Kalkış Şehri</th>
            <th>Varış Şehri</th>
            <th>Kalkış Zamanı</th>
            <th>Varış Zamanı</th>
            <th>Fiyat</th>
            <th>Kapasite</th>
            <th>İşlem</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($trips as $trip): ?>
            <tr>
                <form method="POST">
                    <td><?php echo htmlspecialchars($trip['id']); ?><input type="hidden" name="id" value="<?php echo htmlspecialchars($trip['id']); ?>"></td>
                    <td><input type="text" name="departure_city" value="<?php echo htmlspecialchars($trip['departure_city']); ?>" class="form-control" required></td>
                    <td><input type="text" name="destination_city" value="<?php echo htmlspecialchars($trip['destination_city']); ?>" class="form-control" required></td>
                    <td><input type="datetime-local" name="departure_time" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($trip['departure_time']))); ?>" class="form-control" required></td>
                    <td><input type="datetime-local" name="arrival_time" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($trip['arrival_time']))); ?>" class="form-control" required></td>
                    <td><input type="number" name="price" value="<?php echo htmlspecialchars($trip['price']); ?>" class="form-control" required></td>
                    <td><input type="number" name="capacity" value="<?php echo htmlspecialchars($trip['capacity']); ?>" class="form-control" required></td>
                    <td>
                        <button type="submit" name="update" class="btn btn-warning">Güncelle</button>
                        <button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Sil?');">Sil</button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>