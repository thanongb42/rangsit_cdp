<?php
/**
 * Logout Handler - Rangsit CDP
 */
require_once __DIR__ . '/config/auth.php';

logout();

header('Location: ' . BASE_URL . '/login.php');
exit;
