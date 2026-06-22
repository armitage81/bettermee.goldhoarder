<?php

function get_db(): mysqli {
    static $conn = null;

    if ($conn !== null) {
        return $conn;
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $name = getenv('DB_NAME') ?: 'goldhoarder';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $conn = new mysqli($host, $user, $pass, $name);

    if ($conn->connect_error) {
        http_response_code(500);
        exit('Database connection failed.');
    }

    $conn->set_charset('utf8mb4');

    $result = $conn->query("SHOW TABLES LIKE 'gold_entries'");
    if ($result->num_rows === 0) {
        $schema = file_get_contents(__DIR__ . '/../schema.sql');
        $conn->multi_query($schema);
        while ($conn->next_result()) {}
    }

    return $conn;
}
