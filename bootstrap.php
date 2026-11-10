<?php

use Dotenv\Dotenv;
require_once __DIR__ . '/vendor/autoload.php';

use App\Database\DatabaseConnection;



    $dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();


$database = new DatabaseConnection(
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT'],
    $_ENV['DB_DATABASE'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);