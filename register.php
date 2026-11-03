<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

View::header('Register');
$min = (int) Config::get('pin_min_length');
$max = (int) Config::get('pin_max_length');
?>
<section class="card">
    <h1>Register</h1>
    <form id="regForm" autocomplete="off">
        <label>Full name
            <input type="text" id="name" required maxlength="60">
        </label>
        <label>Employee ID
            <input type="text" id="employee_id" required pattern="[A-Za-z0-9][A-Za-z0-9\-]{2,19}">
        </label>
        <label>PIN (<?= $min ?>-<?= $max ?> digits)
            <input type="password" id="pin" required inputmode="numeric" pattern="\d{<?= $min ?>,<?= $max ?>}">
        </label>
        <label>Confirm PIN
            <input type="password" id="pin2" required inputmode="numeric">
        </label>

        <div class="video-wrap"><video id="video" autoplay muted playsinline></video></div>
        <p id="status" class="status">Loading face models…</p>
        <button type="submit" id="submitBtn" disabled>Scan face &amp; register</button>
    </form>
    <p class="small"><a href="index.php">Back</a></p>
</section>
<?php View::footer(['js/face.js', 'js/register.js'], true);
