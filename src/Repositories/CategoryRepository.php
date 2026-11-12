<?php

namespace App\Repositories;

use PDO;

class CategoryRepository
{


    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }


    public function findByName(string $name): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM categories WHERE name = :name"
        );

        $stmt->execute([
            'name' => $name
        ]);

        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        return $category ?: null;
    }


    public function create(string $name): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO categories (name) VALUES (:name)"
        );

        $stmt->execute([
            'name' => $name
        ]);

        $id = (int) $this->pdo->lastInsertId();

        return $id;
    }
    public function findOrCreate(string $name): int
    {
        $exist = $this->findByName($name);

        if ($exist) {
            return (int) $exist['id'];
        }

        return $this->create($name);
    }
}