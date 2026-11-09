<?php

// helper to safely print text inside HTML (stops script injection)
if (!function_exists('e')) {
    function e($text)
    {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($pageTitle)) {
    $pageTitle = 'Face Attendance';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="site-header">
    <a href="index.php">Face Attendance</a>
</div>
<div class="container">