<?php
// pin.php - Step 2 of login: ask for the PIN and check it (Member 5)
//
// Uses other members' files:
//   includes/users.php     (Member 1): getUserPublic(), verifyUserPin()
//   includes/challenge.php (Member 4): csrfToken(), csrfCheck(), isLocked(),
//                                      recordFailedPin(), clearFailedPin()
//
// $_SESSION['pending_user_id'] must be set by api/match_face.php
// after a successful face match.

session_start();
require_once __DIR__ . '/includes/users.php';
require_once __DIR__ . '/includes/challenge.php';

// the user must pass the face check first
if (empty($_SESSION['pending_user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['pending_user_id'];
$user = getUserPublic($userId);   // only user_id and name, no secrets

if (!$user) {
    unset($_SESSION['pending_user_id']);
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');
    $lockSeconds = isLocked($userId);   // seconds left, or 0 if not locked

    if (!csrfCheck($_POST['csrf'] ?? '')) {
        $error = 'Invalid form. Please try again.';
    } elseif ($lockSeconds > 0) {
        // account is locked after too many wrong PINs
        $error = 'Account locked. Try again in ' . $lockSeconds . ' seconds.';
    } elseif (!preg_match('/^[0-9]{4,6}$/', $pin)) {
        $error = 'PIN must be 4 to 6 digits.';
    } elseif (verifyUserPin($userId, $pin)) {
        // correct PIN: log the user in
        clearFailedPin($userId);
        session_regenerate_id(true);   // new session id for safety
        unset($_SESSION['pending_user_id']);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        header('Location: attendance.php');
        exit;
    } else {
        // wrong PIN: Member 4's function counts it and returns attempts left
        $left = recordFailedPin($userId);
        if ($left <= 0) {
            $error = 'Too many wrong attempts. Account locked for a short time.';
        } else {
            $error = 'Wrong PIN. Attempts left: ' . $left;
        }
    }
}

$pageTitle = 'Enter PIN';
require_once __DIR__ . '/includes/header.php';
?>

<h1>Enter your PIN</h1>
<p>Hello, <?php echo e($user['name']); ?>.</p>

<?php if ($error !== ''): ?>
    <div class="message error"><?php echo e($error); ?></div>
<?php endif; ?>

<form method="post" action="pin.php">
    <input type="hidden" name="csrf" value="<?php echo e(csrfToken()); ?>">

    <label for="pin">PIN (4 to 6 digits)</label>
    <input type="password" id="pin" name="pin" maxlength="6" inputmode="numeric" autocomplete="off" required>

    <button class="btn" type="submit">Submit</button>
    <a class="btn btn-secondary" href="login.php">Cancel</a>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>