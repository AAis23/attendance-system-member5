<?php
/**
 * users.php - everything about employee accounts (data side only).
 * Used by: Member 2 (createUser), Member 3/5 (findClosestUser, findUser),
 * Member 5 (verifyUserPin).
 */
require_once __DIR__ . '/csv_helper.php';

/** Returns an error message, or null if the data is valid. */
function validateUserData(string $userId, string $name, string $pin, array $descriptor): ?string {
    if (!preg_match('/^[A-Za-z0-9_-]{3,20}$/', $userId))
        return 'Employee ID must be 3-20 letters, numbers, - or _.';
    if (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,49}$/", $name))
        return 'Name must be 2-50 letters.';
    if (!preg_match('/^\d{4,6}$/', $pin))
        return 'PIN must be 4-6 digits.';
    if (count($descriptor) !== FACE_DESCRIPTOR_LENGTH)
        return 'Face data is invalid. Please scan your face again.';
    foreach ($descriptor as $n) {
        if (!is_numeric($n)) return 'Face data is invalid. Please scan your face again.';
    }
    return null;
}

/**
 * Registers a new user. PIN is hashed, descriptor is stored as JSON.
 * @return array ['success' => bool, 'error' => string]
 */
function createUser(string $userId, string $name, string $pin, array $descriptor): array {
    $err = validateUserData($userId, $name, $pin, $descriptor);
    if ($err) return ['success' => false, 'error' => $err];

    $row = [
        'user_id'         => $userId,
        'name'            => $name,
        'pin_hash'        => password_hash($pin, PASSWORD_DEFAULT),
        'face_descriptor' => json_encode(array_map('floatval', $descriptor)),
        'created_at'      => date('Y-m-d H:i:s'),
    ];
    if (!appendCSVUnique(USERS_CSV, USERS_HEADERS, $row, 'user_id'))
        return ['success' => false, 'error' => 'Employee ID already registered.'];
    return ['success' => true, 'error' => ''];
}

function userExists(string $userId): bool {
    return findInCSV(USERS_CSV, 'user_id', $userId) !== null;
}

/** Full row including pin_hash. Keep it server-side, never send to the browser. */
function findUser(string $userId): ?array {
    return findInCSV(USERS_CSV, 'user_id', $userId);
}

/** Safe version for display: no pin_hash, no face data. */
function getUserPublic(string $userId): ?array {
    $u = findUser($userId);
    return $u ? ['user_id' => $u['user_id'], 'name' => $u['name']] : null;
}

/** All users for an admin list, without secrets. */
function getAllUsersPublic(): array {
    return array_map(fn($u) => [
        'user_id' => $u['user_id'], 'name' => $u['name'], 'created_at' => $u['created_at'],
    ], readCSV(USERS_CSV));
}

/** True if the PIN is correct for that user. */
function verifyUserPin(string $userId, string $pin): bool {
    $u = findUser($userId);
    return $u !== null && password_verify($pin, $u['pin_hash']);
}

function deleteUser(string $userId): bool {
    return deleteCSV(USERS_CSV, 'user_id', $userId) > 0;
}

/** Distance between two face descriptors (smaller = more alike). */
function euclideanDistance(array $a, array $b): float {
    $sum = 0.0;
    $n = min(count($a), count($b));
    for ($i = 0; $i < $n; $i++) {
        $d = (float)$a[$i] - (float)$b[$i];
        $sum += $d * $d;
    }
    return sqrt($sum);
}

/**
 * Compares a scanned face with every saved face ON THE SERVER.
 * Returns ['user_id','name','distance'] for the best match under the
 * threshold, or null if nobody matches.
 */
function findClosestUser(array $descriptor, float $threshold = FACE_MATCH_THRESHOLD): ?array {
    if (count($descriptor) !== FACE_DESCRIPTOR_LENGTH) return null;
    $best = null;
    foreach (readCSV(USERS_CSV) as $u) {
        $saved = json_decode($u['face_descriptor'], true);
        if (!is_array($saved)) continue;
        $dist = euclideanDistance($descriptor, $saved);
        if ($best === null || $dist < $best['distance']) {
            $best = ['user_id' => $u['user_id'], 'name' => $u['name'], 'distance' => $dist];
        }
    }
    return ($best !== null && $best['distance'] <= $threshold) ? $best : null;
}
