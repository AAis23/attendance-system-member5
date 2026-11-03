<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

View::header('Log in');
?>
<section class="card">
    <h1>Face login</h1>
    <div class="video-wrap"><video id="video" autoplay muted playsinline></video></div>
    <p id="status" class="status">Loading face models…</p>
    <button id="startBtn" disabled>Open camera &amp; start</button>
    <p class="small"><a href="index.php">Back</a></p>
</section>
<?php View::footer(['js/face.js', 'js/liveness.js', 'js/login.js'], true);
