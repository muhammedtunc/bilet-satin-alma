<?php
session_start();

// Check if already logged in BEFORE any output or includes
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

include 'includes/db.php';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $db->prepare("SELECT * FROM User WHERE email = :email");
        $stmt->execute(['email' => mb_strtolower($email)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['company_id'] = $user['company_id'] ?? null;
            header('Location: index.php');
            exit;
        } else {
            $error = 'E-posta veya şifre hatalı.';
        }
    } else {
        $error = 'E-posta ve şifre girin.';
    }
}

// Now safe to output HTML
include 'includes/header.php';
?>

<h2>Giriş Yap</h2>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="POST">
    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Şifre</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Giriş Yap</button>
</form>

<?php include 'includes/footer.php'; ?>