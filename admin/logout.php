<?php
require_once __DIR__ . '/../config/db.php';
unset($_SESSION['admin_id'], $_SESSION['admin_username']);
header('Location: login.php');
exit;
