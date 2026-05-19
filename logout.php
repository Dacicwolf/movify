<?php
/**
 * Movify – Logout
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

require_once __DIR__ . '/includes/functions.php';

logout_user();
header('Location: ' . url('login.php'));
exit;
