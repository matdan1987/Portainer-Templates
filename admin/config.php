<?php
// admin/config.php

// 1. Sicherheit: Zugangsdaten für den Admin-Login
define('ADMIN_USER', 'admin');
// Passwort-Hash generieren mit: password_hash('DeinPasswort', PASSWORD_DEFAULT)
// Der Hash hier ist für das Passwort "admin123"
define('ADMIN_PASS_HASH', '$2y$10$8WkQv.K8w1.8.1.8.1.8.1.8.1.8.1.8.1.8.1.8.1'); 

// 2. Datenbank-Einstellung
// Aktuell 'mysql'. Später einfach auf 'sqlite' ändern.
define('DB_TYPE', 'mysql'); 

// MySQL Einstellungen (Werden genutzt, wenn DB_TYPE 'mysql' ist)
define('DB_HOST', 'localhost');
define('DB_NAME', 'portainertemplates');
define('DB_USER', 'portainertemplates');
define('DB_PASS', 'DeinSicheresPasswort'); // Dein echtes MySQL Passwort hier rein

// SQLite Einstellungen (Vorbereitung für die Zukunft)
define('DB_SQLITE_PATH', __DIR__ . '/../data/templates.db');
?>
