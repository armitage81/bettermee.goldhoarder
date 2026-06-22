<?php

$envFile = __DIR__ . '/env.php';
if (file_exists($envFile)) {
    require_once $envFile;
}

define('BASE_PATH', rtrim(getenv('BASE_PATH') ?: '', '/'));

function url(string $path): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}
