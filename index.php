<?php
require_once 'includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
} else {
    redirect('dashboard.php');
}
?>
