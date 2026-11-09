<?php
// login.php - Step 1 of login: camera -> liveness -> face match -> show name (Member 5)
//
// Uses other members' files (see the API contract):
//   includes/csv_helper.php  (Member 1): readCSV()
//   includes/challenge.php   (Member 4): generateChallenge(), verifyChallenge(), csrfToken(), csrfCheck()
//   js/face.js               (Member 3): loadModels(), startCamera(), getDescriptor(), matchFace(), drawStatus()
//   js/liveness.js           (Member 4): runLiveness()

session_start();
require_once __DIR__ . '/includes/csv_helper.php';
require_once __DIR__ . '/includes/challenge.php';

$error = '';

// ---- The browser sends the matched user here after the face check ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = trim($_POST['user_id'] ?? '');

    if (!csrfCheck($_POST['csrf'] ?? '')) {
        $error = 'Invalid form. Please try again.';
    } elseif (!verifyChallenge($_POST['challenge_token'] ?? '')) {
        // the one-time token is wrong or expired, so liveness is not accepted
        $error = 'Liveness check expired or invalid. Please try again.';
    } else {
        // make sure this user really exists in users.csv
        $found = false;
        foreach (readCSV(__DIR__ . '/data/users.csv') as $row) {
            if ($row['user_id'] === $userId) {
                $found = true;
                break;
            }
        }

        if ($found) {
            // face is OK, now go to the PIN step
            $_SESSION['pending_user_id'] = $userId;
            header('Location: pin.php');
            exit;
        }
        $error = 'User not found.';
    }
}

// ---- Prepare data for the page ----
$challenge = generateChallenge();   // example: ['type' => 'blink', 'token' => 'abc123...']

// Only send what the browser needs for matching: id, name, descriptor
$savedUsers = [];
foreach (readCSV(__DIR__ . '/data/users.csv') as $row) {
    $savedUsers[] = [
        'user_id'    => $row['user_id'],
        'name'       => $row['name'],
        'descriptor' => json_decode($row['face_descriptor'], true),
    ];
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<h1>Login</h1>

<?php if ($error !== ''): ?>
    <div class="message error"><?php echo e($error); ?></div>
<?php endif; ?>

<!-- camera area: the canvas is drawn on top of the video (box and text) -->
<div class="camera-box">
    <video id="video" autoplay muted playsinline></video>
    <canvas id="overlay"></canvas>
</div>
<p id="status">Click Start to begin.</p>
<button class="btn" id="startBtn" type="button">Start</button>
<a class="btn btn-secondary" href="index.php">Back</a>

<!-- hidden form: filled in by JavaScript after a successful face match -->
<form id="matchForm" method="post" action="login.php">
    <input type="hidden" name="csrf" value="<?php echo e(csrfToken()); ?>">
    <input type="hidden" name="challenge_token" value="<?php echo e($challenge['token']); ?>">
    <input type="hidden" name="user_id" id="matchedUserId" value="">
</form>

<script src="js/face.js"></script>
<script src="js/liveness.js"></script>
<script>
// data from PHP
var savedUsers = <?php echo json_encode($savedUsers); ?>;
var challengeType = <?php echo json_encode($challenge['type']); ?>;

var video = document.getElementById('video');
var overlay = document.getElementById('overlay');
var statusText = document.getElementById('status');
var startBtn = document.getElementById('startBtn');

// show a message under the camera
function setStatus(text) {
    statusText.textContent = text;
}

async function startLogin() {
    startBtn.disabled = true;

    try {
        // 1. load the models and start the camera (Member 3)
        setStatus('Loading face models...');
        await loadModels();
        await startCamera(video);

        // 2. liveness check: blink or head turn (Member 4)
        setStatus('Liveness check: please ' + (challengeType === 'blink' ? 'blink' : 'turn your head'));
        var isLive = await runLiveness(video, challengeType);
        if (!isLive) {
            setStatus('Liveness check failed. Please try again.');
            startBtn.disabled = false;
            return;
        }

        // 3. get the face descriptor (Member 3)
        // expected result: { ok: true, descriptor: [...] } or { ok: false, error: '...' }
        setStatus('Looking at your face...');
        var result = await getDescriptor(video, overlay);
        if (!result.ok) {
            // no face, many faces, too small, too dark, etc.
            setStatus(result.error);
            startBtn.disabled = false;
            return;
        }

        // 4. compare with the saved users (Member 3)
        // expected result: { user_id: '...', name: '...' } or null
        var match = matchFace(result.descriptor, savedUsers);
        if (!match) {
            setStatus('Face not recognized. Please try again.');
            startBtn.disabled = false;
            return;
        }

        // 5. show the name, then go on to the PIN page
        setStatus('Hello, ' + match.name + '! Going to PIN...');
        document.getElementById('matchedUserId').value = match.user_id;
        setTimeout(function () {
            document.getElementById('matchForm').submit();
        }, 1500);

    } catch (err) {
        setStatus('Something went wrong: ' + err.message);
        startBtn.disabled = false;
    }
}

startBtn.addEventListener('click', startLogin);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>