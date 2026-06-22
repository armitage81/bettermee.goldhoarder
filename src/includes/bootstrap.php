<?php

session_name('BETTERMEE_SESSID');
session_set_cookie_params(['path' => '/']);
session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Access denied. Please log in through the main application.');
}

$user_id = (int)$_SESSION['user_id'];

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$db = get_db();
