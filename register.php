<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

include 'includes/db.php';

$errors = [];
$old = ['full_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    $old['full_name'] = $full_name;
    $old['email'] = $email;

    if ($full_name === '') $errors[] = 'İsim boş olamaz.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçersiz e-posta.';
    if ($password === '') $errors[] = 'Şifre boş olamaz.';
    if ($password !== $password2) $errors[] = 'Şifreler eşleşmiyor.';

    if (empty($errors)) {
        $check = $db->prepare("SELECT 1 FROM User WHERE email = :email");
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            $errors[] = 'Bu e-posta zaten kayıtlı.';
        } else {
            $id = bin2hex(random_bytes(16));
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $created_at = date('Y-m-d H:i:s');

            $stmt = $db->prepare("INSERT INTO User (id, full_name, email, role, password, company_id, balance, created_at) VALUES (:id, :full_name, :email, 'user', :password, NULL, 800, :created_at)");
            $stmt->execute([
                'id' => $id,
                'full_name' => $full_name,
                'email' => $email,
                'password' => $hash,
                'created_at' => $created_at
            ]);

            header('Location: login.php');
            exit;
        }
    }
}

// Artık yönlendirmeler tamamlandı; çıktı üretilebilir
include 'includes/header.php';
?>

<h2>Kayıt Ol</h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" class="mb-4">
    <div class="form-group">
        <label>İsim</label>
        <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($old['full_name']); ?>">
    </div>
    <div class="form-group">
        <label>E-posta</label>
        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($old['email']); ?>">
    </div>
    <div class="form-group">
        <label>Şifre</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Şifre (Tekrar)</label>
        <input type="password" name="password2" class="form-control" required>
    </div>
    <button class="btn btn-primary" type="submit">Kayıt Ol</button>
</form>

<?php include 'includes/footer.php'; ?>