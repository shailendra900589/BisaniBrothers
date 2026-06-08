<?php
require_once __DIR__ . '/../includes/security.php';
security_bootstrap();
security_logout();
header('Location: login.php');
exit;
