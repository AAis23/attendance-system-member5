<?php
// index.php - home page with the Register and Login buttons (Member 5)

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<h1>Welcome</h1>
<p>Please choose an option.</p>

<!-- register.php is Member 2's page -->
<a class="btn" href="register.php">Register</a>
<a class="btn" href="login.php">Login</a>

<?php require_once __DIR__ . '/includes/footer.php'; ?>