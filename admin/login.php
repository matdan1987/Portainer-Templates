<?php
require_once 'config.php'; // Config für Konstanten laden
session_start();

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Sicherer Abgleich mit Config-Werten
    if ($username === ADMIN_USER && password_verify($password, ADMIN_PASS_HASH)) {
        $_SESSION['authenticated'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Ungültige Anmeldeinformationen';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portainer Template CMS - Login</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="app-shell">
    <div class="login-form">
        <h2>Portainer Template CMS</h2>
        <p>Bitte melden Sie sich an, um auf den Admin-Bereich zuzugreifen.</p>
        
        <?php if ($error): ?>
        <div style="color: var(--danger); margin-bottom: 15px; padding: 10px; background: rgba(239,68,64,0.1); border-radius: 8px; border: 1px solid var(--danger);">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username" class="form-label">Benutzername</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Passwort</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary login-btn">Anmelden</button>
        </form>
    </div>
</div>
</body>
</html>
