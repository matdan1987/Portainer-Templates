<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($detailTemplate['title']); ?> – Portainer Template</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .detail-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .detail-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(55,65,81,0.7);
        }
        .detail-logo {
            width: 70px;
            height: 70px;
            border-radius: 18px;
            background: radial-gradient(circle at 30% 10%, #f9fafb, #6b7280 38%, #020617 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 15px;
            box-shadow: 0 12px 30px rgba(15,23,42,0.9);
        }
        .detail-title {
            font-size: 28px;
            margin: 0 0 8px;
            color: #e5e7eb;
        }
        .detail-subtitle {
            font-size: 16px;
            color: #9ca3af;
            margin-bottom: 15px;
        }
        .detail-id {
            background: rgba(34,197,94,0.15);
            color: #bbf7d0;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 13px;
            display: inline-block;
            margin-top: 10px;
        }
        .field-row {
            background: rgba(15,23,42,0.9);
            border: 1px solid rgba(31,41,55,0.9);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .field-label {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .field-value {
            color: #e5e7eb;
            font-size: 14px;
            word-break: break-all;
            white-space: pre-wrap;
        }
        .field-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }
        .field-tag {
            background: rgba(59,130,246,0.15);
            color: #bfdbfe;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 12px;
        }
        .json-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(55,65,81,0.7);
        }
        .json-box {
            background: #030712;
            border: 1px solid rgba(31,41,55,0.9);
            border-radius: 10px;
            padding: 15px;
            font-family: monospace;
            font-size: 13px;
            color: #e5e7eb;
            overflow-x: auto;
            max-height: 300px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #22c55e;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="app-shell">
    <div class="detail-container">
        <a href="/" class="back-link">← Zurück zur Übersicht</a>
        
        <div class="detail-header">
            <div class="detail-logo"><?php echo strtoupper(substr($detailTemplate['title'], 0, 1)); ?></div>
            <h1 class="detail-title"><?php echo htmlspecialchars($detailTemplate['title']); ?></h1>
            <p class="detail-subtitle"><?php echo htmlspecialchars($detailTemplate['note'] ?? 'Keine Beschreibung verfügbar.'); ?></p>
            <div class="detail-id">Template-ID: <?php echo $detailTemplate['id']; ?></div>
        </div>
        
        <div class="field-row">
            <div class="field-label">Docker Image</div>
            <div class="field-value"><?php echo htmlspecialchars($detailTemplate['image']); ?></div>
        </div>
        
        <?php if (!empty($detailTemplate['logo'])): ?>
        <div class="field-row">
            <div class="field-label">Logo URL</div>
            <div class="field-value"><?php echo htmlspecialchars($detailTemplate['logo']); ?></div>
        </div>
        <?php endif; ?>
        
        <div class="field-row">
            <div class="field-label">Typ</div>
            <div class="field-value"><?php echo htmlspecialchars($detailTemplate['type_string']); ?></div>
        </div>
        
        <?php if (!empty($detailTemplate['categories_array'])): ?>
        <div class="field-row">
            <div class="field-label">Kategorien</div>
            <div class="field-tags">
                <?php foreach ($detailTemplate['categories_array'] as $cat): ?>
                <span class="field-tag"><?php echo htmlspecialchars($cat); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($detailTemplate['ports_array'])): ?>
        <div class="field-row">
            <div class="field-label">Ports</div>
            <div class="field-value"><?php echo implode(', ', array_map('htmlspecialchars', $detailTemplate['ports_array'])); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($detailTemplate['volumes_formatted'])): ?>
        <div class="field-row">
            <div class="field-label">Volumes</div>
            <div class="field-value"><?php echo htmlspecialchars($detailTemplate['volumes_formatted']); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($detailTemplate['env_display'])): ?>
<div class="field-row">
    <div class="field-label">Environment Variablen</div>
    <div class="field-value">
        <?php foreach ($detailTemplate['env_display'] as $env): ?>
            <strong><?php echo htmlspecialchars($env['name']); ?></strong>: 
            <?php echo htmlspecialchars($env['label']); ?>
            <?php if (!empty($env['default'])): ?> (Standard: <?php echo htmlspecialchars($env['default']); ?>)<?php endif; ?>
            <?php if ($env['preset']): ?> <span style="color:#22c55e;">(Voreinstellung aktiv)</span><?php endif; ?>
            <br>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
        
        <div class="json-section">
            <h3>Portainer JSON URL</h3>
            <p>Verwenden Sie diese URL in Portainer, um nur dieses Template zu laden:</p>
            <div class="json-box">
                <?php echo htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . '/public/templates.php?id=' . $detailTemplate['id']); ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
