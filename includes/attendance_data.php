<?php
/**
 * attendance_data.php - saving and reading attendance records.
 * Rule: one record per person per day; time-in once, time-out once.
 * Member 5 calls these from attendance.php after a successful login.
 */
require_once __DIR__ . '/csv_helper.php';

/** 'none' (not in yet), 'in' (timed in), or 'out' (finished for the day). */
function getTodayStatus(string $userId): string {
    $today = date('Y-m-d');
    foreach (readCSV(ATTENDANCE_CSV) as $r) {
        if ($r['user_id'] === $userId && $r['date'] === $today) {
            return $r['time_out'] === '' ? 'in' : 'out';
        }
    }
    return 'none';
}

/** @return array ['success' => bool, 'error' => string, 'time' => string] */
function recordTimeIn(string $userId, string $name): array {
    if (getTodayStatus($userId) !== 'none')
        return ['success' => false, 'error' => 'Already timed in today.', 'time' => ''];
    $time = date('H:i:s');
    $ok = appendCSV(ATTENDANCE_CSV, ATTENDANCE_HEADERS, [
        'user_id' => $userId, 'name' => $name,
        'date' => date('Y-m-d'), 'time_in' => $time, 'time_out' => '',
    ]);
    return ['success' => $ok, 'error' => $ok ? '' : 'Could not save record.', 'time' => $time];
}

/** @return array ['success' => bool, 'error' => string, 'time' => string] */
function recordTimeOut(string $userId): array {
    $status = getTodayStatus($userId);
    if ($status === 'none') return ['success' => false, 'error' => 'You have not timed in today.', 'time' => ''];
    if ($status === 'out')  return ['success' => false, 'error' => 'Already timed out today.', 'time' => ''];
    $time = date('H:i:s');
    $today = date('Y-m-d');
    $n = updateCSVWhere(ATTENDANCE_CSV,
        fn($r) => $r['user_id'] === $userId && $r['date'] === $today && $r['time_out'] === '',
        ['time_out' => $time]);
    return ['success' => $n > 0, 'error' => $n > 0 ? '' : 'Could not save record.', 'time' => $time];
}

/** All records for a date (default today), for the admin table. */
function getAttendanceByDate(?string $date = null): array {
    $date = $date ?? date('Y-m-d');
    return array_values(array_filter(readCSV(ATTENDANCE_CSV), fn($r) => $r['date'] === $date));
}

function getAttendanceByUser(string $userId): array {
    return array_values(array_filter(readCSV(ATTENDANCE_CSV), fn($r) => $r['user_id'] === $userId));
}

function getAllAttendance(): array {
    return readCSV(ATTENDANCE_CSV);
}
