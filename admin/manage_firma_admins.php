<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../includes/db.php';

$companies_stmt = $db->query("SELECT * FROM Bus_Company");
$companies = $companies_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $id = bin2hex(random_bytes(16));
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $company_id = $_POST['company_id'] ?: null;
        $balance = 0.0;
        $created_at = date('Y-m-d H:i:s');

        $stmt = $db->prepare("INSERT INTO User (id, full_name, email, role, password, company_id, balance, created_at) VALUES (:id, :full_name, :email, 'company_admin', :password, :company_id, :balance, :created_at)");
        $stmt->execute(['id' => $id, 'full_name' => $full_name, 'email' => $email, 'password' => $password, 'company_id' => $company_id, 'balance' => $balance, 'created_at' => $created_at]);
    } elseif (isset($_POST['update'])) {
        $stmt = $db->prepare("UPDATE User SET full_name = :full_name, email = :email, company_id = :company_id WHERE id = :id AND role = 'company_admin'");
        $stmt->execute(['full_name' => $_POST['full_name'], 'email' => $_POST['email'], 'company_id' => $_POST['company_id'] ?: null, 'id' => $_POST['id']]);
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $db->prepare("UPDATE User SET password = :password WHERE id = :id")->execute(['password' => $password, 'id' => $_POST['id']]);
        }
    } elseif (isset($_POST['delete'])) {
        $db->prepare("DELETE FROM User WHERE id = :id AND role = 'company_admin'")->execute(['id' => $_POST['id']]);
    }
}

$stmt = $db->query("SELECT u.*, c.name AS company_name FROM User u LEFT JOIN Bus_Company c ON u.company_id = c.id WHERE u.role = 'company_admin'");
$firma_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<h2>Firma Adminleri Yönet</h2>

<h3>Yeni Firma Admin Ekle</h3>
<form method="POST">
    <input type="hidden" name="add" value="1">
    <div class="row">
        <div class="col"><input type="text" name="full_name" placeholder="Ad Soyad" class="form-control" required></div>
        <div class="col"><input type="email" name="email" placeholder="Email" class="form-control" required></div>
        <div class="col"><input type="password" name="password" placeholder="Şifre" class="form-control" required></div>
        <div class="col">
            <select name="company_id" class="form-control" required>
                <option value="">Firma Seç</option>
                <?php foreach ($companies as $comp): ?>
                    <option value="<?php echo $comp['id']; ?>"><?php echo htmlspecialchars($comp['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col"><button type="submit" class="btn btn-success">Ekle</button></div>
    </div>
</form>

<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Ad Soyad</th>
            <th>Email</th>
            <th>Şifre (Değiştir)</th>
            <th>Firma</th>
            <th>İşlem</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($firma_admins as $admin): ?>
            <tr>
                <form method="POST">
                    <td><?php echo $admin['id']; ?><input type="hidden" name="id" value="<?php echo $admin['id']; ?>"></td>
                    <td><input type="text" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" class="form-control"></td>
                    <td><input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" class="form-control"></td>
                    <td><input type="password" name="password" placeholder="Yeni Şifre (Opsiyonel)" class="form-control"></td>
                    <td>
                        <select name="company_id" class="form-control">
                            <?php foreach ($companies as $comp): ?>
                                <option value="<?php echo $comp['id']; ?>" <?php if ($comp['id'] == $admin['company_id']) echo 'selected'; ?>><?php echo htmlspecialchars($comp['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
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