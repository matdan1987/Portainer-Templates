<?php
require_once 'functions.php';
requireAuth();

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $template = getTemplateById($_GET['id']);
    if ($template) {
        echo json_encode($template);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Template nicht gefunden']);
    }
} else {
    echo json_encode(['error' => 'Keine Template-ID angegeben']);
}
?>