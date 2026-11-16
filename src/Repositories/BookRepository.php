<?php

namespace App\Repositories;

use App\Models\Book;

use InvalidArgumentException;
use PDO;

class BookRepository
{

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUpc(string $upc): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM books WHERE upc=:upc"
        );
        $stmt->execute([
            ":upc" => $upc,
        ]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        return $book ?: null;
    }


    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                books.*,
                categories.name AS category_name
            FROM books
            LEFT JOIN categories
            ON books.category_id = categories.id
             WHERE books.id = :id;"
        );

        $stmt->execute([
            "id"=>$id,
        ]);

         $book = $stmt->fetch(PDO::FETCH_ASSOC);
        return $book ?: null;

    }


    public function update(Book $book): bool
    {
        $stmt = $this->pdo->prepare(
            " UPDATE books
            SET
                title = :title,
                price = :price,
                availability = :availability,
                rating = :rating,
                image_url = :image_url,
                product_url = :product_url,
                product_type = :product_type,
                price_excl_tax = :price_excl_tax,
                price_incl_tax = :price_incl_tax,
                tax = :tax,
                num_reviews = :num_reviews,
                description = :description,
                category_id = :category_id
            WHERE upc = :upc"
        );

        return $stmt->execute([
            'title' => $book->title,
            'price' => $book->price,
            'availability' => $book->availability,
            'rating' => $book->rating,
            'image_url' => $book->imageUrl,
            'product_url' => $book->productUrl,
            'product_type' => $book->productType,
            'price_excl_tax' => $book->priceExclTax,
            'price_incl_tax' => $book->priceInclTax,
            'tax' => $book->tax,
            'num_reviews' => $book->numReviews,
            'description' => $book->description,
            'category_id' => $book->categoryId,
            'upc' => $book->upc
        ]);
    }


    public function insert(Book $book): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO books (
            upc,
            title,
            price,
            availability,
            rating,
            image_url,
            product_url,
            product_type,
            price_excl_tax,
            price_incl_tax,
            tax,
            num_reviews,
            description,
            category_id
        ) VALUES (
            :upc,
            :title,
            :price,
            :availability,
            :rating,
            :image_url,
            :product_url,
            :product_type,
            :price_excl_tax,
            :price_incl_tax,
            :tax,
            :num_reviews,
            :description,
            :category_id
        )"
        );

        $stmt->execute([
            'upc' => $book->upc,
            'title' => $book->title,
            'price' => $book->price,
            'availability' => $book->availability,
            'rating' => $book->rating,
            'image_url' => $book->imageUrl,
            'product_url' => $book->productUrl,
            'product_type' => $book->productType,
            'price_excl_tax' => $book->priceExclTax,
            'price_incl_tax' => $book->priceInclTax,
            'tax' => $book->tax,
            'num_reviews' => $book->numReviews,
            'description' => $book->description,
            'category_id' => $book->categoryId
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function hasChanges(array $existing, Book $book): bool
    {
        $fields = [
            'title' => $book->title,
            'price' => $book->price,
            'availability' => $book->availability,
            'rating' => $book->rating,
            'image_url' => $book->imageUrl,
            'product_url' => $book->productUrl,
            'product_type' => $book->productType,
            'price_excl_tax' => $book->priceExclTax,
            'price_incl_tax' => $book->priceInclTax,
            'tax' => $book->tax,
            'num_reviews' => $book->numReviews,
            'description' => $book->description,
            'category_id' => $book->categoryId,
        ];

        foreach ($fields as $column => $newValue) {
            if ((string) ($existing[$column] ?? '') !== (string) ($newValue ?? '')) {
                return true;
            }
        }

        return false;
    }


    public function save(Book $book): string
    {
        $existing = $this->findByUpc($book->upc);

        if ($existing === null) {
            $this->insert($book);
            return 'inserted';
        }

        if (!$this->hasChanges($existing, $book)) {
            return 'unchanged';
        }

        $this->update($book);
        return 'updated';
    }


    public function findAll(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
            books.*,
            categories.name AS category_name
        FROM books
        LEFT JOIN categories
            ON books.category_id = categories.id
        ORDER BY books.id DESC"
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findPaginated(int $limit, int $offset): array{

       if ($limit <= 0 || $offset < 0) {
            throw new InvalidArgumentException("Invalid pagination values.");
        }

        $stmt=$this->pdo->prepare(
              "SELECT
                    books.*,
                    categories.name AS category_name
                FROM books
                LEFT JOIN categories
                    ON books.category_id = categories.id
                ORDER BY books.id DESC
                LIMIT :limit OFFSET :offset"
        );

        $stmt->bindValue(':limit',$limit,PDO::PARAM_INT);
        $stmt->bindValue(':offset',$offset, PDO :: PARAM_INT);

     
        $stmt->execute();


        return $stmt->fetchAll(PDO::FETCH_ASSOC);    }

    public function countAll():int{
        $stmt=$this->pdo->prepare(
            "SELECT COUNT(*) FROM books");

        $stmt->execute();
        return (int) $stmt->fetchColumn();

        
    }
}
