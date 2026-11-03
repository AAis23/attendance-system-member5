<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$employeeId = Auth::requirePending();
$user = (new UserRepository())->find($employeeId);
if ($user === null) {
    Auth::clearPending();
    Http::redirect('login.php');
}

View::header('Enter PIN');
?>
<section class="card">
    <h1>Hello, <?= Http::e($user['name']) ?></h1>
    <p class="muted">Enter your PIN to continue.</p>
    <form id="pinForm" autocomplete="off">
        <input type="password" id="pin" inputmode="numeric" required autofocus placeholder="PIN">
        <p id="status" class="status"></p>
        <button type="submit">Confirm</button>
    </form>
    <p class="small"><a href="login.php">Not you?</a></p>
</section>
</main>
<script>
document.getElementById('pinForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const status = document.getElementById('status');
    const pin = document.getElementById('pin');
    status.className = 'status'; status.textContent = 'Checking…';
    try {
        const res = await fetch('api/verify_pin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ pin: pin.value })
        });
        const data = await res.json();
        if (data.ok) { location.href = data.redirect; return; }
        status.className = 'status error';
        status.textContent = data.error || 'Error';
        pin.value = '';
        if (data.redirect) setTimeout(() => location.href = data.redirect, 2500);
    } catch (err) {
        status.className = 'status error'; status.textContent = 'Network error.';
    }
});
</script>
</body>
</html>
