<?php
// Öffentliche Hauptansicht – Portainer Template CMS
require_once __DIR__ . '/admin/functions.php';

// Detailansicht?
$detailId = $_GET['id'] ?? null;
if ($detailId) {
    $detailTemplate = getTemplateById($detailId);
    if (!$detailTemplate) {
        http_response_code(404);
        die('Template nicht gefunden.');
    }
    $detailTemplate = prepareTemplateForDisplay($detailTemplate);
    // Zeige Detailansicht
    include __DIR__ . '/public/detail_view.php';
    exit;
}

// Normale Listenansicht
$templates = getTemplates();
$allCategories = getAllCategories();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portainer Templates – Öffentliche Ansicht</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* ... (gleiche Styles wie zuvor für Header, Suche, Grid) ... */
        .public-header { text-align: center; margin-bottom: 30px; padding: 20px 0; }
        .public-header h1 { font-size: 28px; margin-bottom: 8px; }
        .search-container { max-width: 600px; margin: 0 auto 20px; position: relative; }
        .search-input { width: 100%; padding: 12px 20px 12px 40px; border-radius: 50px; border: 1px solid rgba(55,65,81,0.9); background: rgba(15,23,42,0.95); color: #e5e7eb; font-size: 14px; outline: none; }
        .search-input:focus { border-color: #22c55e; box-shadow: 0 0 0 2px rgba(34,197,94,0.3); }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #6b7280; }
        .filter-chips { display: flex; justify-content: center; flex-wrap: wrap; gap: 8px; margin-bottom: 25px; }
        .filter-chip { padding: 6px 16px; border-radius: 50px; background: rgba(15,23,42,0.85); border: 1px solid rgba(55,65,81,0.9); color: #9ca3af; font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .filter-chip.active, .filter-chip:hover { background: rgba(34,197,94,0.15); color: #bbf7d0; border-color: rgba(34,197,94,0.7); }
        .templates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 10px; }
        .template-card { background: rgba(15,23,42,0.92); border-radius: 14px; padding: 20px; border: 1px solid rgba(31,41,55,0.9); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
        .template-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.4); border-color: rgba(34,197,94,0.5); }
        .template-logo { width: 48px; height: 48px; border-radius: 12px; background: radial-gradient(circle at 30% 10%, #f9fafb, #6b7280 38%, #020617 100%); display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 15px; box-shadow: 0 8px 20px rgba(15,23,42,0.8); }
        .template-title { font-size: 18px; font-weight: 650; margin: 0 0 5px; color: #e5e7eb; }
        .template-subtitle { font-size: 12px; color: #9ca3af; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
        .template-id { font-size: 10px; background: rgba(34,197,94,0.15); color: #bbf7d0; padding: 2px 8px; border-radius: 999px; }
        .template-desc { font-size: 14px; color: #d1d5db; margin-bottom: 15px; line-height: 1.5; }
        .meta-tags { display: flex; flex-wrap: wrap; gap: 6px; margin: 10px 0; }
        .meta-tag { background: rgba(34,197,94,0.12); color: #bbf7d0; padding: 3px 10px; border-radius: 50px; font-size: 12px; }
        .no-results { grid-column: 1 / -1; text-align: center; padding: 40px; color: #9ca3af; }
        .integration-hint { margin-top: 40px; padding: 20px; background: rgba(15,23,42,0.8); border-radius: 14px; border: 1px solid rgba(31,41,55,0.8); text-align: center; }
        .integration-hint code { background: rgba(0,0,0,0.3); padding: 3px 8px; border-radius: 6px; color: #facc15; font-family: monospace; }
    </style>
</head>
<body>
<div class="app-shell">
    <div class="public-header">
        <h1>Portainer Templates – Öffentliche Ansicht</h1>
        <p>Durchsuche verfügbare Vorlagen und klicke auf ein Template, um Details zu sehen.</p>
        
        <div class="search-container">
            <span class="search-icon">🔍</span>
            <input type="text" class="search-input" id="search-input" placeholder="Nach Titel, Beschreibung oder Kategorie suchen...">
        </div>
        
        <div class="filter-chips">
            <div class="filter-chip active" data-category="all">Alle Kategorien</div>
            <?php foreach ($allCategories as $cat): ?>
            <div class="filter-chip" data-category="<?php echo htmlspecialchars($cat); ?>">
                <?php echo htmlspecialchars($cat); ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="templates-grid" id="templates-grid">
        <?php if (empty($templates)): ?>
            <div class="no-results">Keine Templates verfügbar.</div>
        <?php else: ?>
            <?php foreach ($templates as $template): 
                $categories = !empty($template['categories']) ? explode(',', $template['categories']) : [];
                $type = $template['type'] == 1 ? 'Container' : ($template['type'] == 2 ? 'Swarm' : 'Stack');
            ?>
                <div class="template-card" 
                     data-title="<?php echo htmlspecialchars(strtolower($template['title'])); ?>" 
                     data-desc="<?php echo htmlspecialchars(strtolower($template['note'] ?? '')); ?>"
                     data-categories="<?php echo htmlspecialchars(strtolower(implode(' ', $categories))); ?>"
                     onclick="window.location.href='?id=<?php echo $template['id']; ?>'">
                    <div class="template-logo"><?php echo strtoupper(substr($template['title'], 0, 1)); ?></div>
                    <h3 class="template-title"><?php echo htmlspecialchars($template['title']); ?></h3>
                    <div class="template-subtitle">
                        <span><?php echo htmlspecialchars($type); ?></span>
                        <span class="template-id">ID: <?php echo $template['id']; ?></span>
                    </div>
                    <p class="template-desc"><?php echo htmlspecialchars($template['note'] ?? 'Keine Beschreibung verfügbar.'); ?></p>
                    
                    <?php if (!empty($categories)): ?>
                    <div class="meta-tags">
                        <?php foreach ($categories as $cat): ?>
                        <span class="meta-tag"><?php echo htmlspecialchars(trim($cat)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="integration-hint">
        <h3>Portainer Integration</h3>
        <p>Um <strong>alle Templates</strong> zu nutzen, fügen Sie diese URL in Portainer ein:</p>
        <p><code><?php echo htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . '/public/templates.php'); ?></code></p>
        <p style="margin-top: 15px;">
            Für ein <strong>einzelnes Template</strong> nutzen Sie die ID aus der Detailansicht:<br>
            <code><?php echo htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . '/public/templates.php?id='); ?></code><strong style="color:#facc15;">[ID]</strong>
        </p>
    </div>
</div>

<script>
// Live-Suche und Filter (gleich wie vorher)
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const filterChips = document.querySelectorAll('.filter-chip');
    const templateCards = document.querySelectorAll('.template-card');
    let activeCategory = 'all';
    
    searchInput.addEventListener('input', filterTemplates);
    filterChips.forEach(chip => {
        chip.addEventListener('click', function() {
            filterChips.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            activeCategory = this.dataset.category;
            filterTemplates();
        });
    });
    
    function filterTemplates() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        templateCards.forEach(card => {
            const title = card.dataset.title || '';
            const desc = card.dataset.desc || '';
            const categories = card.dataset.categories || '';
            const matchesCategory = activeCategory === 'all' || categories.includes(activeCategory.toLowerCase());
            const matchesSearch = !searchTerm || title.includes(searchTerm) || desc.includes(searchTerm) || categories.includes(searchTerm);
            
            card.style.display = (matchesCategory && matchesSearch) ? 'block' : 'none';
            if (matchesCategory && matchesSearch) visibleCount++;
        });
        document.querySelector('.no-results').style.display = visibleCount === 0 ? 'block' : 'none';
    }
    
    templateCards.forEach(card => card.style.display = 'block');
});
</script>
</body>
</html>