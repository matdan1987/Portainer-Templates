<?php
session_start();
require_once __DIR__ . '/config.php'; // Config laden

// 🗄️ Datenbankverbindung herstellen (Hybrid: MySQL & SQLite ready)
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            if (DB_TYPE === 'sqlite') {
                // SQLite Logik (Vorbereitung)
                $dbDir = dirname(DB_SQLITE_PATH);
                if (!is_dir($dbDir)) { mkdir($dbDir, 0777, true); }
                $dsn = "sqlite:" . DB_SQLITE_PATH;
                $db = new PDO($dsn);
            } else {
                // MySQL Logik (Standard)
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $db = new PDO($dsn, DB_USER, DB_PASS);
            }

            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Tabellen-Erstellung: Unterscheidung zwischen MySQL und SQLite Syntax
            $idType = (DB_TYPE === 'sqlite') ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
            $engine = (DB_TYPE === 'sqlite') ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $db->exec("CREATE TABLE IF NOT EXISTS templates (
                id $idType,
                title VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                image VARCHAR(512) NOT NULL,
                logo VARCHAR(512),
                note TEXT,
                categories VARCHAR(512),
                type TINYINT DEFAULT 1,
                platforms VARCHAR(255),
                labels VARCHAR(512),
                ports VARCHAR(255),
                volumes TEXT,
                env TEXT
            ) $engine");

            // Index separat erstellen (fängt Fehler ab, falls er schon existiert)
            try {
                // MySQL braucht eine Längenangabe bei TEXT/VARCHAR für Index, SQLite nicht zwingend
                // Wir nutzen hier einen einfachen Trick und prüfen die Existenz nicht hart
                $db->exec("CREATE UNIQUE INDEX idx_name ON templates(name)"); 
            } catch (Exception $e) { /* Index existiert wahrscheinlich schon, ignorieren */ }

        } catch (PDOException $e) {
            error_log("Datenbankfehler: " . $e->getMessage());
            die("⚠️ Datenbankverbindung fehlgeschlagen. Prüfen Sie die config.php.");
        }
    }
    return $db;
}

// 🔒 Authentifizierung prüfen (Jetzt sicher über config.php)
function requireAuth() {
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        header('Location: login.php');
        exit;
    }
}

// 🧩 Template als JSON für Portainer formatieren
function formatTemplateForPortainer($template) {
    $result = [
        'id' => (int)$template['id'],
        'type' => (int)$template['type'],
        'title' => $template['title'],
        'name' => $template['name'],
        'image' => $template['image'],
        'description' => $template['note'] ?? '',
    ];
    
    // Optionale Felder hinzufügen, wenn vorhanden
    if (!empty($template['logo'])) {
        $result['logo'] = $template['logo'];
    }
    if (!empty($template['categories'])) {
        $result['categories'] = array_map('trim', explode(',', $template['categories']));
    }
    if (!empty($template['platforms'])) {
        $result['platforms'] = array_map('trim', explode(',', $template['platforms']));
    }
    if (!empty($template['labels'])) {
        parse_str($template['labels'], $labels);
        $result['labels'] = $labels;
    }
    if (!empty($template['ports'])) {
        $result['ports'] = array_map('trim', explode(',', $template['ports']));
    }
    if (!empty($template['volumes'])) {
        $volumes = [];
        $volLines = array_filter(array_map('trim', explode("\n", $template['volumes'])));
        foreach ($volLines as $line) {
            $parts = explode('|', $line, 2);
            if (count($parts) === 2) {
                $volumes[] = [
                    'container' => trim($parts[0]),
                    'bind' => trim($parts[1])
                ];
            }
        }
        if (!empty($volumes)) {
            $result['volumes'] = $volumes;
        }
    }
    if (!empty($template['env'])) {
        $env = [];
        $envLines = array_filter(array_map('trim', explode("\n", $template['env'])));
        foreach ($envLines as $line) {
            $parts = explode('|', $line, 4);
            if (!empty($parts[0])) {
                $envItem = [
                    'name' => trim($parts[0])
                ];
                if (isset($parts[1])) $envItem['label'] = trim($parts[1]);
                if (isset($parts[2])) $envItem['default'] = trim($parts[2]);
                if (isset($parts[3])) $envItem['preset'] = (trim($parts[3]) === '1');
                $env[] = $envItem;
            }
        }
        if (!empty($env)) {
            $result['env'] = $env;
        }
    }
    
    return $result;
}

// 📤 Portainer-kompatibles JSON generieren
function generatePortainerJson() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM templates ORDER BY title");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [
        'version' => '3',
        'templates' => []
    ];
    
    foreach ($templates as $template) {
        $result['templates'][] = formatTemplateForPortainer($template);
    }
    
    return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

// 🏷️ Kategorien aus allen Templates extrahieren
function getAllCategories() {
    $db = getDB();
    $stmt = $db->query("SELECT DISTINCT categories FROM templates WHERE categories IS NOT NULL AND categories != ''");
    $allCategories = [];
    while ($row = $stmt->fetch()) {
        $cats = array_map('trim', explode(',', $row['categories']));
        foreach ($cats as $cat) {
            if ($cat && !in_array($cat, $allCategories)) {
                $allCategories[] = $cat;
            }
        }
    }
    sort($allCategories);
    return $allCategories;
}

// 🔍 Templates suchen und filtern
function getTemplates($search = '', $category = '') {
    $db = getDB();
    $sql = "SELECT * FROM templates WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND (title LIKE ? OR name LIKE ? OR categories LIKE ? OR note LIKE ?)";
        $likeSearch = "%$search%";
        $params = array_fill(0, 4, $likeSearch);
    }
    
    if ($category && $category !== 'Alle') {
        $sql .= " AND categories LIKE ?";
        $params[] = "%$category%";
    }
    
    $sql .= " ORDER BY title";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 🔎 Template nach ID holen
function getTemplateById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM templates WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ➕ Neues Template erstellen
function createTemplate($data) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO templates (title, name, image, logo, note, categories, type, platforms, labels, ports, volumes, env) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['title'],
        $data['name'],
        $data['image'],
        $data['logo'] ?? '',
        $data['note'] ?? '',
        $data['categories'] ?? '',
        (int)($data['type'] ?? 1),
        $data['platforms'] ?? '',
        $data['labels'] ?? '',
        $data['ports'] ?? '',
        $data['volumes'] ?? '',
        $data['env'] ?? ''
    ]);
}

// ✏️ Template aktualisieren
function updateTemplate($id, $data) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE templates SET title = ?, name = ?, image = ?, logo = ?, note = ?, categories = ?, type = ?, platforms = ?, labels = ?, ports = ?, volumes = ?, env = ? WHERE id = ?");
    return $stmt->execute([
        $data['title'],
        $data['name'],
        $data['image'],
        $data['logo'] ?? '',
        $data['note'] ?? '',
        $data['categories'] ?? '',
        (int)($data['type'] ?? 1),
        $data['platforms'] ?? '',
        $data['labels'] ?? '',
        $data['ports'] ?? '',
        $data['volumes'] ?? '',
        $data['env'] ?? '',
        $id
    ]);
}

// 🗑️ Template löschen
function deleteTemplate($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM templates WHERE id = ?");
    return $stmt->execute([$id]);
}

// 👁️ Template-Daten für die Anzeige aufbereiten
function prepareTemplateForDisplay($template) {
    if (!$template) return null;
    
    $display = $template;
    $display['categories_array'] = !empty($template['categories']) ? array_map('trim', explode(',', $template['categories'])) : [];
    $display['ports_array'] = !empty($template['ports']) ? array_map('trim', explode(',', $template['ports'])) : [];
    $display['volumes_formatted'] = !empty($template['volumes']) ? trim($template['volumes']) : '';
    // Env-Variablen formatieren für Anzeige
$display['env_formatted'] = !empty($template['env']) ? trim($template['env']) : '';
$display['env_display'] = [];
if (!empty($template['env'])) {
    $lines = explode("\n", trim($template['env']));
    foreach ($lines as $line) {
        if (trim($line)) {
            $parts = explode('|', $line);
            $name = trim($parts[0] ?? '');
            $label = trim($parts[1] ?? '');
            $default = trim($parts[2] ?? '');
            $preset = isset($parts[3]) && trim($parts[3]) === '1';
            
            if ($name) {
                $display['env_display'][] = [
                    'name' => $name,
                    'label' => $label ?: $name,
                    'default' => $default,
                    'preset' => $preset
                ];
            }
        }
    }
}
    $display['type_string'] = $template['type'] == 1 ? 'Container' : ($template['type'] == 2 ? 'Swarm' : 'Stack');
    
    return $display;
}
?>
