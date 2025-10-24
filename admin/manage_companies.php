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
        $created_at = date('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO Bus_Company (id, name, logo_path, created_at) VALUES (:id, :name, :logo_path, :created_at)");
        $stmt->execute([
            'id' => $id,
            'name' => $_POST['name'],
            'logo_path' => $_POST['logo_path'] ?: null,
            'created_at' => $created_at
        ]);
    } elseif (isset($_POST['update'])) {
        $stmt = $db->prepare("UPDATE Bus_Company SET name = :name, logo_path = :logo_path WHERE id = :id");
        $stmt->execute(['name' => $_POST['name'], 'logo_path' => $_POST['logo_path'] ?: null, 'id' => $_POST['id']]);
    } elseif (isset($_POST['delete'])) {
        $db->prepare("DELETE FROM Bus_Company WHERE id = :id")->execute(['id' => $_POST['id']]);
    }
}

$stmt = $db->query("SELECT * FROM Bus_Company");
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<h2>Firmaları Yönet</h2>

<h3>Yeni Firma Ekle</h3>
<form method="POST">
    <input type="hidden" name="add" value="1">
    <div class="row">
        <div class="col"><input type="text" name="name" placeholder="Firma Adı" class="form-control" required></div>
        <div class="col"><input type="text" name="logo_path" placeholder="Logo Yolu" class="form-control"></div>
        <div class="col"><button type="submit" class="btn btn-success">Ekle</button></div>
    </div>
</form>

<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Ad</th>
            <th>Logo Yolu</th>
            <th>İşlem</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($companies as $company): ?>
            <tr>
                <form method="POST">
                    <td><?php echo $company['id']; ?><input type="hidden" name="id" value="<?php echo $company['id']; ?>"></td>
                    <td><input type="text" name="name" value="<?php echo htmlspecialchars($company['name']); ?>" class="form-control"></td>
                    <td><input type="text" name="logo_path" value="<?php echo htmlspecialchars($company['logo_path']); ?>" class="form-control"></td>
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