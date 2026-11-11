<?php
namespace App\Models;

class BookDetails{
    public function __construct(
        public string $upc,
        public ?string $productType,
        public ?string $priceExclTax,
        public ?string $priceInclTax,
        public ?string $tax,
        public ?string $numReviews,
        public ?string $availability,
        public ?string $description,
        public ?string $category,


    )
    {
    }
}