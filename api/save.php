<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$siteId = preg_replace('/[^a-zA-Z0-9_-]/', '', $data['site_id'] ?? 'default');
if (empty($siteId)) {
    $siteId = 'default';
}

$username = get_logged_in_user();

if (!can_edit_site($username, $siteId)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$sitesDir = __DIR__ . '/../sites';
if (!is_dir($sitesDir)) {
    mkdir($sitesDir, 0755, true);
}

$filePath = $sitesDir . '/' . $siteId . '.json';


$owner = 'admin';
if (file_exists($filePath)) {
    $existing = json_decode(file_get_contents($filePath), true);
    if ($existing && isset($existing['owner'])) {
        $owner = $existing['owner'];
    }
} else {
    $owner = $username;
}

$payload = [
    'site_id' => $siteId,
    'settings' => $data['settings'] ?? [],
    'blocks' => $data['blocks'] ?? [],
    'owner' => $owner,
    'updated_at' => date('c')
];

if (file_put_contents($filePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'site_id' => $siteId]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save configuration']);
}
