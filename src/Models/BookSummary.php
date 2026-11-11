<?php
namespace App\Models;

class BookSummary{
    public function __construct(
        public string $title,
        public ?string $price,
        public ?string $availability,
        public ?string $rating,
        public ?string $imageUrl,
        public ?string $productUrl,

    )
    {
    }
}