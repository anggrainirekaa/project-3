<?php
// Bootstrap: dipanggil di awal setiap halaman.
define('BASE_URL', rtrim(getenv('BASE_URL') ?: '', '/'));
define('PER_PAGE', 8);

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/auth.php';
