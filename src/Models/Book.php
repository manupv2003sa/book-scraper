<?php
namespace App\Models;

class Book
{
   public function __construct(
    public string $upc,
    public string $title,
    public string $price,
    public string $productUrl,


    public ?int $id = null,
    public ?string $availability = null,
    public ?int $rating = null,
    public ?string $imageUrl = null,
    public ?string $productType = null,
    public ?string $priceExclTax = null,
    public ?string $priceInclTax = null,
    public ?string $tax = null,
    public ?int $numReviews = null,
    public ?string $description = null,
    public ?int $categoryId = null
) {}
}