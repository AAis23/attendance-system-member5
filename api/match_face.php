<?php
/**
 * api/match_face.php - the browser sends the scanned face descriptor,
 * the server compares it to saved faces and returns who it is (if anyone).
 * Matching on the server means saved face data never leaves the server.
 *
 * Request : POST JSON  {"descriptor": [128 numbers]}
 * Response: {"matched": true, "user_id": "...", "name": "..."}  or {"matched": false}
 *
 * TODO (Member 4/5): require the liveness token/session check before matching,
 * and store the matched user_id in $_SESSION for pin.php.
 */
require_once __DIR__ . '/../includes/users.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['matched' => false, 'error' => 'POST only']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$descriptor = $input['descriptor'] ?? null;
if (!is_array($descriptor) || count($descriptor) !== FACE_DESCRIPTOR_LENGTH) {
    http_response_code(400);
    echo json_encode(['matched' => false, 'error' => 'Invalid face data']);
    exit;
}

$match = findClosestUser($descriptor);
echo json_encode($match
    ? ['matched' => true, 'user_id' => $match['user_id'], 'name' => $match['name']]
    : ['matched' => false]);
