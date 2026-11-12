<?php

namespace App\Repositories;
use App\Http\HttpResponse;
use App\Models\Book;

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


    public function update(Book $book): bool
{
    $stmt = $this->pdo->prepare(
    "UPDATE books
    SET
        title = COALESCE(:title, title),
        price = COALESCE(:price, price),
        availability = COALESCE(:availability, availability),
        rating = COALESCE(:rating, rating),
        image_url = COALESCE(:image_url, image_url),
        product_url = COALESCE(:product_url, product_url),
        product_type = COALESCE(:product_type, product_type),
        price_excl_tax = COALESCE(:price_excl_tax, price_excl_tax),
        price_incl_tax = COALESCE(:price_incl_tax, price_incl_tax),
        tax = COALESCE(:tax, tax),
        num_reviews = COALESCE(:num_reviews, num_reviews),
        description = COALESCE(:description, description),
        category_id = COALESCE(:category_id, category_id)
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


    public function save(Book $book): string
{
    $existing = $this->findByUpc($book->upc);

    if ($existing) {
        $this->update($book);
        return 'updated';
    }
    return "Working";

    $this->insert($book);
    return 'inserted';
}
}



