<?php
require_once 'functions.php';
requireAuth();

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['json_url'])) {
    $url = $_POST['json_url'];
    // JSON laden (timeout setzen)
    $ctx = stream_context_create(['http'=> ['timeout' => 5]]);
    $json = @file_get_contents($url, false, $ctx);
    
    if ($json) {
        $data = json_decode($json, true);
        if (isset($data['templates']) && is_array($data['templates'])) {
            $count = 0;
            $skipped = 0;
            $db = getDB();
            
            // Vorbereitetes Statement für Insert
            $stmt = $db->prepare("INSERT INTO templates (title, name, image, logo, note, categories, type, platforms, env, volumes, ports) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Vorbereitetes Statement für Check
            $check = $db->prepare("SELECT id FROM templates WHERE name = ?");

            foreach ($data['templates'] as $t) {
                // Daten mappen und bereinigen
                $title = $t['title'] ?? 'Unbekannt';
                // Name generieren falls nicht vorhanden (wichtig für interne ID)
                $name = $t['name'] ?? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $title)); 
                $image = $t['image'] ?? '';
                
                // Überspringen wenn kein Image da ist
                if (!$image) continue;

                $logo = $t['logo'] ?? '';
                $note = $t['description'] ?? ($t['note'] ?? '');
                $cats = isset($t['categories']) ? (is_array($t['categories']) ? implode(',', $t['categories']) : $t['categories']) : '';
                $type = $t['type'] ?? 1;
                $platform = isset($t['platform']) ? $t['platform'] : 'linux';
                
                // Environment Variablen konvertieren
                $envStr = '';
                if (isset($t['env']) && is_array($t['env'])) {
                    foreach ($t['env'] as $e) {
                        $n = $e['name'] ?? '';
                        $l = $e['label'] ?? $n;
                        $d = $e['default'] ?? '';
                        $p = isset($e['set']) ? $e['set'] : ''; // manche nutzen 'set' statt default
                        if(!$d) $d = $p;
                        if ($n) $envStr .= "$n|$l|$d|0\n";
                    }
                }

                // Volumes konvertieren
                $volStr = '';
                if (isset($t['volumes']) && is_array($t['volumes'])) {
                    foreach ($t['volumes'] as $v) {
                         $c = $v['container'] ?? '';
                         $b = $v['bind'] ?? '';
                         if ($c) $volStr .= "$c|$b\n";
                    }
                }
                
                // Ports konvertieren
                $portStr = '';
                if (isset($t['ports']) && is_array($t['ports'])) {
                    $portStr = implode(',', $t['ports']);
                }

                // Prüfen ob Template schon existiert
                $check->execute([$name]);
                if (!$check->fetch()) {
                    try {
                        $stmt->execute([$title, $name, $image, $logo, $note, $cats, $type, $platform, $envStr, $volStr, $portStr]);
                        $count++;
                    } catch (Exception $e) {
                        // Fehler bei einzelnen Templates ignorieren
                    }
                } else {
                    $skipped++;
                }
            }
            $message = "Import abgeschlossen: $count neu angelegt, $skipped übersprungen (bereits vorhanden).";
            $msgType = 'success';
        } else {
            $message = "Ungültiges JSON-Format. 'templates' Array nicht gefunden.";
            $msgType = 'error';
        }
    } else {
        $message = "Konnte URL nicht laden (Timeout oder ungültig).";
        $msgType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Import - Portainer Template CMS</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="app-shell">
    <div class="login-form">
        <h2>Templates Importieren</h2>
        <p>Importiere Templates aus einer offiziellen Portainer App Templates URL (JSON).</p>
        
        <?php if ($message): ?>
            <div style="padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid currentColor; background: <?php echo $msgType=='success' ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.1)'; ?>; color: <?php echo $msgType=='success' ? '#4ade80' : '#f87171'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">JSON URL</label>
                <input type="url" name="json_url" class="form-control" placeholder="https://raw.githubusercontent.com/..." required>
                <div style="margin-top:5px; font-size:11px; color:#6b7280;">
                    Tipp: Such auf GitHub nach "portainer templates json".
                </div>
            </div>
            <div class="form-actions">
                <a href="index.php" class="btn btn-ghost">Zurück zum Dashboard</a>
                <button type="submit" class="btn btn-primary">Import Starten</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
