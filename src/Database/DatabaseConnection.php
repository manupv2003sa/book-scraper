<?php

namespace App\Database;

use PDO;
use PDOException;

class DatabaseConnection
{
    private PDO $connection;

    public function __construct(
        string $host,
        string $port,
        string $database,
        string $username,
        string $password
    ) {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        $this->connection = new PDO($dsn, $username, $password,[PDO::ATTR_ERRMODE => PDO:: ERRMODE_EXCEPTION]);
    }
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
