<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../includes/db.php';
include '../includes/header.php';
?>

<h2>Admin Paneli</h2>
<a href="manage_companies.php" class="btn btn-primary">Firmaları Yönet</a>
<a href="manage_firma_admins.php" class="btn btn-primary">Firma Adminleri Yönet</a>
<a href="manage_coupons.php" class="btn btn-primary">Kuponları Yönet</a>

<?php include '../includes/footer.php'; ?>