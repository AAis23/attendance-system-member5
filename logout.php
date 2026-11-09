<?php
// logout.php - ends the session and goes back to the home page

session_start();
require_once __DIR__ . '/includes/challenge.php';

// only accept a POST request with a valid CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfCheck($_POST['csrf'] ?? '')) {
    session_unset();    // remove all session data
    session_destroy();  // end the session
}

header('Location: index.php');
exit;