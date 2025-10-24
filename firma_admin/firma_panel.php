<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'company_admin') {
    header("Location: ../login.php");
    exit;
}
include '../includes/db.php';
include '../includes/header.php';
?>

<h2>Firma Admin Paneli</h2>
<a href="manage_trips.php" class="btn btn-primary">Seferleri Yönet</a>
<a href="manage_coupons.php" class="btn btn-primary">Kuponları Yönet</a>

<?php include '../includes/footer.php'; ?>