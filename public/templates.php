<?php
// Öffentliche Templates-Seite für Portainer
require_once __DIR__ . '/../admin/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Wenn eine Template-ID angegeben ist, nur dieses Template zurückgeben
if (isset($_GET['id'])) {
    $template = getTemplateById($_GET['id']);
    if ($template) {
        $result = [
            'version' => '3',
            'templates' => [formatTemplateForPortainer($template)]
        ];
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Template nicht gefunden']);
    }
} else {
    // Alle Templates zurückgeben
    echo generatePortainerJson();
}
?>