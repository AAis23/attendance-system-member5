<?php
/** Run from the project folder:  php tests/test_data_layer.php
 *  Uses a temporary folder, so your real CSV files are never touched. */
$tmp = sys_get_temp_dir() . '/att_test_' . uniqid();
putenv("ATT_DATA_DIR=$tmp");
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../includes/attendance_data.php';

$pass = 0; $fail = 0;
function check(string $label, bool $cond) {
    global $pass, $fail;
    echo ($cond ? "PASS" : "FAIL") . "  $label\n";
    $cond ? $pass++ : $fail++;
}

$face = array_fill(0, 128, 0.1);
$other = array_fill(0, 128, 0.9);

// Users
check('create user', createUser('EMP001', 'Juan Dela Cruz', '1234', $face)['success']);
check('duplicate ID rejected', !createUser('EMP001', 'Someone Else', '1111', $face)['success']);
check('bad PIN rejected', !createUser('EMP002', 'Maria Clara', '12', $face)['success']);
check('bad descriptor rejected', !createUser('EMP002', 'Maria Clara', '1234', [1, 2])['success']);
check('user exists', userExists('EMP001'));
check('correct PIN', verifyUserPin('EMP001', '1234'));
check('wrong PIN', !verifyUserPin('EMP001', '9999'));
check('PIN not stored as plain text', strpos(file_get_contents(USERS_CSV), '1234') === false);
check('public info has no hash', !isset(getUserPublic('EMP001')['pin_hash']));

// Face matching
$m = findClosestUser($face);
check('same face matches', $m !== null && $m['user_id'] === 'EMP001');
check('different face rejected', findClosestUser($other) === null);

// Attendance
check('status none', getTodayStatus('EMP001') === 'none');
check('time out before in fails', !recordTimeOut('EMP001')['success']);
check('time in', recordTimeIn('EMP001', 'Juan Dela Cruz')['success']);
check('double time in blocked', !recordTimeIn('EMP001', 'Juan Dela Cruz')['success']);
check('status in', getTodayStatus('EMP001') === 'in');
check('time out', recordTimeOut('EMP001')['success']);
check('double time out blocked', !recordTimeOut('EMP001')['success']);
check('status out', getTodayStatus('EMP001') === 'out');
check('today list has 1 row', count(getAttendanceByDate()) === 1);

// Generic helpers
check('formula injection neutralised', csvCell('=1+1') === "'=1+1");
check('delete user', deleteUser('EMP001') && !userExists('EMP001'));

echo "\n$pass passed, $fail failed\n";
array_map('unlink', glob("$tmp/*"));
@rmdir($tmp);
exit($fail ? 1 : 0);
