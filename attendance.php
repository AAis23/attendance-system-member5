<?php
// attendance.php - time-in / time-out and the admin table (Member 5)
//
// Uses other members' files:
//   includes/attendance_data.php (Member 1): getTodayStatus(), recordTimeIn(),
//                                            recordTimeOut(), getAttendanceByDate()
//   includes/challenge.php       (Member 4): csrfToken(), csrfCheck()

session_start();
require_once __DIR__ . '/includes/attendance_data.php';
require_once __DIR__ . '/includes/challenge.php';

date_default_timezone_set('Asia/Manila');   // change if your timezone is different

// the user must be fully logged in (face + PIN)
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$name = $_SESSION['name'];

// user IDs that can see the admin table (edit this list)
$adminIds = ['admin'];
$isAdmin = in_array($userId, $adminIds, true);

$message = '';
$error = '';

// ---- Handle the Time In / Time Out buttons ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!csrfCheck($_POST['csrf'] ?? '')) {
        $error = 'Invalid form. Please try again.';
    } elseif ($action === 'time_in') {
        // Member 1's function blocks a second time-in on the same day
        $result = recordTimeIn($userId, $name);
        if ($result['success']) {
            $message = 'Time-in recorded at ' . $result['time'] . '.';
        } else {
            $error = $result['error'];
        }
    } elseif ($action === 'time_out') {
        // Member 1's function blocks time-out before time-in and double time-out
        $result = recordTimeOut($userId);
        if ($result['success']) {
            $message = 'Time-out recorded at ' . $result['time'] . '.';
        } else {
            $error = $result['error'];
        }
    }
}

// load today's records (after the actions, so the page shows the latest data)
$todayRecords = getAttendanceByDate();

// find this user's own row for today
$myRecord = null;
foreach ($todayRecords as $row) {
    if ($row['user_id'] === $userId) {
        $myRecord = $row;
        break;
    }
}

$pageTitle = 'Attendance';
require_once __DIR__ . '/includes/header.php';
?>

<h1>Attendance</h1>
<p>Welcome, <?php echo e($name); ?>. Today is <?php echo e(date('Y-m-d')); ?>.</p>

<?php if ($message !== ''): ?>
    <div class="message success"><?php echo e($message); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="message error"><?php echo e($error); ?></div>
<?php endif; ?>

<p>
    Time in: <?php echo e($myRecord['time_in'] ?? '-'); ?><br>
    Time out: <?php echo e(($myRecord['time_out'] ?? '') !== '' ? $myRecord['time_out'] : '-'); ?>
</p>

<!-- each button is its own small form so it can carry the CSRF token -->
<form method="post" action="attendance.php" style="display:inline;">
    <input type="hidden" name="csrf" value="<?php echo e(csrfToken()); ?>">
    <input type="hidden" name="action" value="time_in">
    <button class="btn" type="submit">Time In</button>
</form>

<form method="post" action="attendance.php" style="display:inline;">
    <input type="hidden" name="csrf" value="<?php echo e(csrfToken()); ?>">
    <input type="hidden" name="action" value="time_out">
    <button class="btn" type="submit">Time Out</button>
</form>

<!-- logout is handled by logout.php -->
<form method="post" action="logout.php" style="display:inline;">
    <input type="hidden" name="csrf" value="<?php echo e(csrfToken()); ?>">
    <button class="btn btn-secondary" type="submit">Logout</button>
</form>

<?php if ($isAdmin): ?>
    <h2>Today's Records (Admin)</h2>
    <table>
        <tr>
            <th>User ID</th>
            <th>Name</th>
            <th>Time In</th>
            <th>Time Out</th>
        </tr>
        <?php foreach ($todayRecords as $row): ?>
            <tr>
                <td><?php echo e($row['user_id']); ?></td>
                <td><?php echo e($row['name']); ?></td>
                <td><?php echo e($row['time_in']); ?></td>
                <td><?php echo e($row['time_out'] !== '' ? $row['time_out'] : '-'); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (count($todayRecords) === 0): ?>
            <tr><td colspan="4">No records yet today.</td></tr>
        <?php endif; ?>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>