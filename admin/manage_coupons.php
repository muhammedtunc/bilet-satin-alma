<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $id = bin2hex(random_bytes(16));
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['expire_date']);
        if ($dt === false) $dt = new DateTime($_POST['expire_date']);
        $expire = $dt->format('Y-m-d H:i:s');
        $created_at = date('Y-m-d H:i:s');

        $stmt = $db->prepare("INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, company_id, created_at) VALUES (:id, :code, :discount, :limit, :exp, NULL, :created_at)");
        $stmt->execute([
            'id' => $id,
            'code' => $_POST['code'],
            'discount' => $_POST['discount'],
            'limit' => $_POST['usage_limit'],
            'exp' => $expire,
            'created_at' => $created_at
        ]);
    } elseif (isset($_POST['update'])) {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['expire_date']);
        if ($dt === false) $dt = new DateTime($_POST['expire_date']);
        $expire = $dt->format('Y-m-d H:i:s');

        $stmt = $db->prepare("UPDATE Coupons SET code = :code, discount = :discount, usage_limit = :limit, expire_date = :exp WHERE id = :id AND company_id IS NULL");
        $stmt->execute([
            'code' => $_POST['code'],
            'discount' => $_POST['discount'],
            'limit' => $_POST['usage_limit'],
            'exp' => $expire,
            'id' => $_POST['id']
        ]);
    } elseif (isset($_POST['delete'])) {
        $db->prepare("DELETE FROM Coupons WHERE id = :id AND company_id IS NULL")->execute(['id' => $_POST['id']]);
    }
}

$stmt = $db->query("SELECT * FROM Coupons WHERE company_id IS NULL");
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<h2>Global Kuponları Yönet</h2>

<h3>Yeni Kupon Ekle</h3>
<form method="POST">
    <input type="hidden" name="add" value="1">
    <div class="row">
        <div class="col"><input type="text" name="code" placeholder="Kod" class="form-control" required></div>
        <div class="col"><input type="number" step="0.01" name="discount" placeholder="İndirim" class="form-control" required></div>
        <div class="col"><input type="number" name="usage_limit" placeholder="Kullanım Limiti" class="form-control" required></div>
        <div class="col"><input type="datetime-local" name="expire_date" class="form-control" required></div>
        <div class="col"><button type="submit" class="btn btn-success">Ekle</button></div>
    </div>
</form>

<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Kod</th>
            <th>İndirim</th>
            <th>Limits</th>
            <th>Son Tarih</th>
            <th>İşlem</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($coupons as $coupon): ?>
            <tr>
                <form method="POST">
                    <td><?php echo $coupon['id']; ?><input type="hidden" name="id" value="<?php echo $coupon['id']; ?>"></td>
                    <td><input type="text" name="code" value="<?php echo htmlspecialchars($coupon['code']); ?>" class="form-control"></td>
                    <td><input type="number" step="0.01" name="discount" value="<?php echo $coupon['discount']; ?>" class="form-control"></td>
                    <td><input type="number" name="usage_limit" value="<?php echo $coupon['usage_limit']; ?>" class="form-control"></td>
                    <td><input type="datetime-local" name="expire_date" value="<?php echo date('Y-m-d\TH:i', strtotime($coupon['expire_date'])); ?>" class="form-control"></td>
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