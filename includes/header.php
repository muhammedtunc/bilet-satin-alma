<?php
// IMPORTANT: This file must be saved as UTF-8 WITHOUT BOM and
// there must be no bytes (spaces/newlines/BOM) before the opening <?php above.
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Bilet Satın Alma Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="/index.php">BiletSatınAlma</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#nav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item"><a class="nav-link" href="/index.php">Ana Sayfa</a></li>
        </ul>
        <ul class="navbar-nav">
            <?php if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])): ?>
                <li class="nav-item"><a class="nav-link" href="/user/my_tickets.php">Hesabım</a></li>
                <li class="nav-item"><a class="nav-link" href="/logout.php">Çıkış</a></li>
            <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="/login.php">Giriş</a></li>
                <li class="nav-item"><a class="nav-link" href="/register.php">Kayıt</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<div class="container mt-4">
    <h1>Bilet Satın Alma Platformu</h1>
</div>
</body>
</html>
