<?php
require_once 'config.php';

$page_title = 'Trainer Database - ' . ucfirst($_SESSION['role']);
$content_file = 'views/' . $_SESSION['role'] . '.php';

// Set up the main content area
ob_start();
?>

<!-- MAIN -->
<div class="main">
    <div class="rl" id="rl">Showing Training Providers</div>
    <div class="pg" id="provGrid"></div>
    <div class="tg hidden" id="trainGrid"></div>
</div>

<?php
$main_content = ob_get_clean();

// Now include the layout with the main content
$content_file = 'views/' . $_SESSION['role'] . '.php';
include 'includes/layout.php';
?>
