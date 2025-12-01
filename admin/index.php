<?php
require_once 'functions.php';
requireAuth();

// Aktionen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                createTemplate($_POST);
                header('Location: index.php?created=1');
                exit;
                
            case 'update':
                updateTemplate($_POST['id'], $_POST);
                header('Location: index.php?updated=1&template=' . $_POST['id']);
                exit;
                
            case 'delete':
                deleteTemplate($_POST['id']);
                header('Location: index.php?deleted=1');
                exit;
        }
    }
}

// Template zum Bearbeiten laden
$editTemplate = null;
if (isset($_GET['edit'])) {
    $editTemplate = getTemplateById($_GET['edit']);
}

// Aktuelles Template für die Anzeige laden
$currentTemplate = null;
if (isset($_GET['template'])) {
    $currentTemplate = getTemplateById($_GET['template']);
} elseif (isset($_GET['updated']) && isset($_GET['template'])) {
    $currentTemplate = getTemplateById($_GET['template']);
}

// Suche und Filter
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'Alle';
$templates = getTemplates($search, $category);
$categories = getAllCategories();
$categories = array_merge(['Alle'], $categories);

// Für die Anzeige aufbereiten
$currentTemplate = $currentTemplate ? prepareTemplateForDisplay($currentTemplate) : null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portainer Template CMS – Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <div class="brand">
            <div class="logo-orb"><div class="logo-orb-inner"></div></div>
            <div class="brand-title">
                <span>PORTAINER TEMPLATE HUB</span>
                <span>Template CMS Admin</span>
            </div>
        </div>
        <div class="topbar-actions">
            <div class="pill">
                <div class="pill-dot"></div>
                <span>App-Templates URL bereit</span>
            </div>
            <button class="btn btn-ghost" onclick="copyJsonUrl()">
                <span>JSON ansehen</span>
            </button>
            <button class="btn btn-primary" onclick="openModal('create')">
                <span>Neues Template</span>
            </button>
            <a href="logout.php" class="btn btn-ghost" style="text-transform: none;">
                <span>Abmelden</span>
            </a>
        </div>
    </header>

    <main class="main-grid">
        <!-- Linke Seite: Liste -->
        <section class="card">
            <div class="card-head">
                <div class="card-title">
                    <span>Deine Templates</span>
                    <span class="sub">Verwalte deine persönliche Portainer Template Sammlung</span>
                </div>
                <button class="btn btn-pill-soft" onclick="copyTemplatesUrl()">
                    <span class="dot-small"></span>
                    <span>App-Templates URL kopieren</span>
                </button>
            </div>

            <div class="chip-row">
                <?php foreach ($categories as $cat): ?>
                <div class="chip <?php echo $category === $cat ? 'active' : ''; ?>" onclick="filterByCategory('<?php echo htmlspecialchars($cat); ?>')">
                    <?php if ($cat === 'Alle'): ?>
                    <div class="chip-dot"></div>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($cat); ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="search-row">
                <div class="input-shell">
                    <span class="input-icon">🔍</span>
                    <input class="input" placeholder="Nach Name, Kategorie oder Port suchen…" value="<?php echo htmlspecialchars($search); ?>" id="search-input" />
                    <span class="input-badge"><?php echo count($templates); ?> Templates</span>
                </div>
                <button class="btn btn-ghost" style="border-radius:999px;white-space:nowrap;">
                    Sortieren
                </button>
            </div>

            <div class="template-list">
                <?php if (empty($templates)): ?>
                    <div style="padding: 20px; text-align: center; color: var(--muted);">
                        Keine Templates gefunden.
                    </div>
                <?php else: ?>
                    <?php foreach ($templates as $template): ?>
                    <article class="template-item <?php echo $currentTemplate && $currentTemplate['id'] == $template['id'] ? 'active' : ''; ?>" onclick="loadTemplate(<?php echo $template['id']; ?>)">
                        <div class="template-logo"><?php echo strtoupper(substr($template['title'], 0, 1)); ?></div>
                        <div class="template-main">
                            <div class="template-main-top">
                                <div class="template-title"><?php echo htmlspecialchars($template['title']); ?></div>
                                <div class="template-type-pill">
                                    <?php echo $template['type'] == 1 ? 'Container' : ($template['type'] == 2 ? 'Swarm' : 'Stack'); ?>
                                </div>
                            </div>
                            <div class="template-desc"><?php echo htmlspecialchars(substr($template['note'] ?? '', 0, 80)) . (strlen($template['note'] ?? '') > 80 ? '...' : ''); ?></div>
                            <div class="template-meta-row">
                                <div class="template-tags">
                                    <?php 
                                    $cats = !empty($template['categories']) ? explode(',', $template['categories']) : [];
                                    foreach (array_slice($cats, 0, 3) as $cat): ?>
                                    <span class="tag"><?php echo htmlspecialchars(trim($cat)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="template-ports">
                                    <?php 
                                    $ports = !empty($template['ports']) ? explode(',', $template['ports']) : [];
                                    foreach (array_slice($ports, 0, 2) as $port): ?>
                                    <span><?php echo htmlspecialchars(trim($port)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Rechte Seite: Detail & JSON Vorschau -->
        <section class="detail-shell">
            <div class="detail-inner">
                <?php if ($currentTemplate): ?>
                    <div class="detail-header">
                        <div class="detail-header-left">
                            <div class="detail-logo"><?php echo strtoupper(substr($currentTemplate['title'], 0, 1)); ?></div>
                            <div class="detail-title-block">
                                <div class="detail-title"><?php echo htmlspecialchars($currentTemplate['title']); ?></div>
                                <div class="detail-subtitle"><?php echo htmlspecialchars($currentTemplate['note'] ?? 'Keine Beschreibung'); ?></div>
                                <div class="detail-label-row">
                                    <span class="pill-soft"><span class="dot"></span><span>template id: <?php echo $currentTemplate['id']; ?></span></span>
                                    <span class="pill-soft">image: <span class="field-value mono"><?php echo htmlspecialchars($currentTemplate['image']); ?></span></span>
                                </div>
                            </div>
                        </div>
                        <div class="detail-label-row">
                            <span class="pill-soft">Kategorie: <?php echo implode(', ', array_map('htmlspecialchars', $currentTemplate['categories_array'])); ?></span>
                            <span class="pill-soft">Typ: <?php echo htmlspecialchars($currentTemplate['type_string']); ?></span>
                        </div>
                    </div>

                    <div class="detail-grid">
                        <!-- Linker Bereich: Metadaten -->
                        <div class="section">
                            <div class="section-title">
                                <span>Template & Image</span>
                                <span class="badge-mini">Basisdaten</span>
                            </div>
                            <div class="field-grid">
                                <div>
                                    <div class="field-label"><span>Titel</span></div>
                                    <div class="field-control">
                                        <div class="field-value"><?php echo htmlspecialchars($currentTemplate['title']); ?></div>
                                        <button class="field-action-btn" onclick="openModal('edit', <?php echo $currentTemplate['id']; ?>)">Bearbeiten</button>
                                    </div>
                                </div>
                                <div>
                                    <div class="field-label"><span>Interner Name</span></div>
                                    <div class="field-control">
                                        <div class="field-value mono"><?php echo htmlspecialchars($currentTemplate['name']); ?></div>
                                        <button class="field-action-btn" onclick="openModal('edit', <?php echo $currentTemplate['id']; ?>)">Bearbeiten</button>
                                    </div>
                                </div>
                                <div>
                                    <div class="field-label"><span>Docker Image</span></div>
                                    <div class="field-control">
                                        <div class="field-value mono"><?php echo htmlspecialchars($currentTemplate['image']); ?></div>
                                        <button class="field-action-btn" onclick="openModal('edit', <?php echo $currentTemplate['id']; ?>)">Bearbeiten</button>
                                    </div>
                                </div>
                                <div>
                                    <div class="field-label"><span>Logo URL</span></div>
                                    <div class="field-control">
                                        <div class="field-value mono"><?php echo htmlspecialchars($currentTemplate['logo'] ?: 'Nicht gesetzt'); ?></div>
                                        <button class="field-action-btn" onclick="openModal('edit', <?php echo $currentTemplate['id']; ?>)">Bearbeiten</button>
                                    </div>
                                </div>
                            </div>

                            <div class="helper-row">
                                <span class="helper-pill">Pflichtfelder: <code>title</code>, <code>name</code>, <code>image</code></span>
                                <span class="helper-pill">Optional: <code>logo</code>, <code>note</code>, <code>maintainer</code></span>
                            </div>
                        </div>

                        <!-- Rechter Bereich: Ports / Volumes / Env -->
                        <div class="section">
                            <div class="section-title">
                                <span>Netzwerk, Volumes & Env</span>
                                <span class="badge-mini">Runtime</span>
                            </div>

                            <div class="field-label"><span>Ports</span><span style="font-size:10px;color:#6b7280;">comma-separated</span></div>
                            <div class="field-control">
                                <div class="field-chip-row">
                                    <?php foreach ($currentTemplate['ports_array'] as $port): ?>
                                    <span class="field-chip"><?php echo htmlspecialchars(trim($port)); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (empty($currentTemplate['ports_array'])): ?>
                                    <span class="field-chip">Keine Ports konfiguriert</span>
                                    <?php endif; ?>
                                </div>
                                <button class="field-action-btn" onclick="openModal('edit', <?php echo $currentTemplate['id']; ?>)">Bearbeiten</button>
                            </div>

                            <div class="field-label"><span>Volumes</span><span style="font-size:10px;color:#6b7280;">container|bind</span></div>
                            <div class="field-control">
                                <div class="textarea-like"><?php echo htmlspecialchars($currentTemplate['volumes_formatted']); ?></div>
                                <button class="field-action-btn" onclick="openModal('edit', <?php echo $currentTemplate['id']; ?>)">Bearbeiten</button>
                            </div>

                           <div class="field-label"><span>Environment Variablen</span><span style="font-size:10px;color:#6b7280;">(Name | Label | Default | Preset)</span></div>
<div class="field-control">
    <div class="textarea-like">
        <?php if (!empty($currentTemplate['env_display'])): ?>
            <?php foreach ($currentTemplate['env_display'] as $env): ?>
                <?php echo htmlspecialchars($env['name']) . 
                     ' | ' . htmlspecialchars($env['label']) . 
                     ' | ' . htmlspecialchars($env['default'] ?? '') . 
                     ' | ' . ($env['preset'] ? '1' : '0'); ?>
                <?php if (!$loop->last): ?><br><?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            Keine Umgebungsvariablen definiert.
        <?php endif; ?>
    </div>
    <button class="field-action-btn" onclick="openModal('edit', <?php echo $currentTemplate['id']; ?>)">Bearbeiten</button>
</div>

                    <div class="section" style="margin-top:10px;">
                        <div class="section-title">
                            <span>Portainer JSON Vorschau</span>
                            <span class="badge-mini">version: 3</span>
                        </div>
                        <div class="json-preview" id="json-preview">
                            <?php 
                            $json = json_decode(generatePortainerJson(), true);
                            $currentJson = null;
                            foreach ($json['templates'] as $t) {
                                if ($t['id'] == $currentTemplate['id']) {
                                    $currentJson = $t;
                                    break;
                                }
                            }
                            if ($currentJson) {
                                echo htmlspecialchars(json_encode($currentJson, JSON_PRETTY_PRINT));
                            } else {
                                echo "JSON konnte nicht generiert werden";
                            }
                            ?>
                        </div>

                        <div class="pill-row-footer">
                            <div class="helper-row">
                                <span class="helper-pill">Portainer: <code>App Templates → URL</code> auf diese Instanz</span>
                            </div>
                            <div class="pill-row-footer-right">
                                <button class="btn-pill-soft" onclick="copyJsonUrl()">
                                    <span class="dot-small"></span>
                                    <span>JSON URL kopieren</span>
                                </button>
                                <button class="btn-pill-soft ghost-danger" onclick="deleteTemplate(<?php echo $currentTemplate['id']; ?>)">
                                    <span>Template löschen</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: var(--muted);">
                        <p>Wählen Sie ein Template aus der Liste aus, um Details anzuzeigen.</p>
                        <p>Erstellen Sie ein neues Template mit dem Button oben rechts.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<!-- Modals -->
<div id="create-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">Neues Template erstellen</div>
            <button class="modal-close" onclick="closeModal('create')">&times;</button>
        </div>
        <form method="POST" id="create-form">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label class="form-label">Titel *</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Interner Name *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Docker Image *</label>
                <input type="text" name="image" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Logo URL</label>
                <input type="text" name="logo" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <textarea name="note" class="form-control form-textarea"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Kategorien (durch Komma getrennt)</label>
                <input type="text" name="categories" class="form-control" placeholder="z.B. security, dns, media">
            </div>
            <div class="form-group">
                <label class="form-label">Typ</label>
                <select name="type" class="form-control">
                    <option value="1">Container</option>
                    <option value="2">Swarm</option>
                    <option value="3">Stack</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Plattformen (durch Komma getrennt)</label>
                <input type="text" name="platforms" class="form-control" placeholder="z.B. linux, windows">
            </div>
            <div class="form-group">
                <label class="form-label">Labels (key1=value1&key2=value2)</label>
                <input type="text" name="labels" class="form-control" placeholder="z.B. maintainer=admin&version=1.0">
            </div>
            <div class="form-group">
                <label class="form-label">Ports (durch Komma getrennt)</label>
                <input type="text" name="ports" class="form-control" placeholder="z.B. 80/tcp, 443/tcp">
            </div>
            <div class="form-group">
                <label class="form-label">Volumes (container|bind pro Zeile)</label>
                <textarea name="volumes" class="form-control form-textarea" placeholder="/container/path | /host/path&#10;/etc/config | /srv/config"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Environment Variablen (NAME|Label|Default|Preset pro Zeile)</label>
                <textarea name="env" class="form-control form-textarea" placeholder="TZ|Timezone|Europe/Berlin|1&#10;PUID|User ID|1000|0"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-ghost btn-small" onclick="closeModal('create')">Abbrechen</button>
                <button type="submit" class="btn btn-primary btn-small">Erstellen</button>
            </div>
        </form>
    </div>
</div>

<div id="edit-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">Template bearbeiten</div>
            <button class="modal-close" onclick="closeModal('edit')">&times;</button>
        </div>
        <form method="POST" id="edit-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-group">
                <label class="form-label">Titel *</label>
                <input type="text" name="title" id="edit-title" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Interner Name *</label>
                <input type="text" name="name" id="edit-name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Docker Image *</label>
                <input type="text" name="image" id="edit-image" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Logo URL</label>
                <input type="text" name="logo" id="edit-logo" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <textarea name="note" id="edit-note" class="form-control form-textarea"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Kategorien (durch Komma getrennt)</label>
                <input type="text" name="categories" id="edit-categories" class="form-control" placeholder="z.B. security, dns, media">
            </div>
            <div class="form-group">
                <label class="form-label">Typ</label>
                <select name="type" id="edit-type" class="form-control">
                    <option value="1">Container</option>
                    <option value="2">Swarm</option>
                    <option value="3">Stack</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Plattformen (durch Komma getrennt)</label>
                <input type="text" name="platforms" id="edit-platforms" class="form-control" placeholder="z.B. linux, windows">
            </div>
            <div class="form-group">
                <label class="form-label">Labels (key1=value1&key2=value2)</label>
                <input type="text" name="labels" id="edit-labels" class="form-control" placeholder="z.B. maintainer=admin&version=1.0">
            </div>
            <div class="form-group">
                <label class="form-label">Ports (durch Komma getrennt)</label>
                <input type="text" name="ports" id="edit-ports" class="form-control" placeholder="z.B. 80/tcp, 443/tcp">
            </div>
            <div class="form-group">
                <label class="form-label">Volumes (container|bind pro Zeile)</label>
                <textarea name="volumes" id="edit-volumes" class="form-control form-textarea" placeholder="/container/path | /host/path&#10;/etc/config | /srv/config"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Environment Variablen (NAME|Label|Default|Preset pro Zeile)</label>
                <textarea name="env" id="edit-env" class="form-control form-textarea" placeholder="TZ|Timezone|Europe/Berlin|1&#10;PUID|User ID|1000|0"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-ghost btn-small" onclick="closeModal('edit')">Abbrechen</button>
                <button type="submit" class="btn btn-primary btn-small">Speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal-Funktionen
function openModal(type, id = null) {
    if (type === 'create') {
        document.getElementById('create-modal').classList.add('active');
    } else if (type === 'edit' && id) {
        // Template-Daten laden
        fetch(`templates.php?id=${id}`)
            .then(response => response.json())
            .then(template => {
                document.getElementById('edit-id').value = template.id;
                document.getElementById('edit-title').value = template.title;
                document.getElementById('edit-name').value = template.name;
                document.getElementById('edit-image').value = template.image;
                document.getElementById('edit-logo').value = template.logo || '';
                document.getElementById('edit-note').value = template.note || '';
                document.getElementById('edit-categories').value = template.categories || '';
                document.getElementById('edit-type').value = template.type || 1;
                document.getElementById('edit-platforms').value = template.platforms || '';
                document.getElementById('edit-labels').value = template.labels || '';
                document.getElementById('edit-ports').value = template.ports || '';
                document.getElementById('edit-volumes').value = template.volumes || '';
                document.getElementById('edit-env').value = template.env || '';
                document.getElementById('edit-modal').classList.add('active');
            })
            .catch(error => {
                alert('Fehler beim Laden des Templates: ' + error);
            });
    }
}

function closeModal(type) {
    if (type === 'create') {
        document.getElementById('create-modal').classList.remove('active');
        document.getElementById('create-form').reset();
    } else if (type === 'edit') {
        document.getElementById('edit-modal').classList.remove('active');
    }
}

// Vorlagen laden
function loadTemplate(id) {
    window.location.href = `index.php?template=${id}`;
}

// Template löschen
function deleteTemplate(id) {
    if (confirm('Möchten Sie dieses Template wirklich löschen?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// URLs kopieren
function copyTemplatesUrl() {
    const url = window.location.origin + '/public/templates.php';
    navigator.clipboard.writeText(url).then(() => {
        alert('URL in die Zwischenablage kopiert!');
    });
}

function copyJsonUrl() {
    const url = window.location.origin + '/public/templates.php?id=' + (<?php echo $currentTemplate ? $currentTemplate['id'] : '0'; ?>);
    if (<?php echo $currentTemplate ? 'true' : 'false'; ?>) {
        navigator.clipboard.writeText(url).then(() => {
            alert('JSON-URL in die Zwischenablage kopiert!');
        });
    } else {
        const baseUrl = window.location.origin + '/public/templates.php';
        navigator.clipboard.writeText(baseUrl).then(() => {
            alert('Basis-URL in die Zwischenablage kopiert!');
        });
    }
}

// Kategorie-Filter
function filterByCategory(category) {
    const url = new URL(window.location);
    url.searchParams.set('category', category);
    url.searchParams.delete('template');
    window.location.href = url.toString();
}

// Suchfunktion
document.getElementById('search-input').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        const url = new URL(window.location);
        url.searchParams.set('search', this.value);
        url.searchParams.delete('template');
        window.location.href = url.toString();
    }
});
</script>
</body>
</html>