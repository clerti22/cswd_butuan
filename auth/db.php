<?php

declare(strict_types=1);

function get_database_connection(): mysqli
{
    $connection = new mysqli('localhost', 'root', '', 'cswd_db');

    if ($connection->connect_error) {
        throw new RuntimeException('Database connection failed.');
    }

    $connection->set_charset('utf8mb4');

    return $connection;
}
