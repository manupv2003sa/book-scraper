
<?php

require_once __DIR__ . '/bootstrap.php';

use App\Http\HttpClient;
use App\Parsers\BookParser;
use App\Support\UrlResolver;
use App\Models\Book;
use App\Repositories\CategoryRepository;
use App\Database\DatabaseConnection;
use App\Scrapers\Scraper;

$db = new DatabaseConnection(
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT'],
    $_ENV['DB_DATABASE'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

$pdo = $db->getConnection();

$categoryRepository = new CategoryRepository($pdo);
// 1. Create objects
$client = new HttpClient();

$parser = new BookParser();

$resolver = new UrlResolver('https://books.toscrape.com/');
$categoryRepository = new CategoryRepository($pdo);


$scraper = new Scraper(
    $client,
    $parser,
    $resolver,
    $categoryRepository
);
// 2. Fetch catalogue HTML
$response = $client->get('https://books.toscrape.com');

$html = $response->getBody();


// 3. Parse catalogue
$books = $parser->parseListing($html);


// Test 1: First catalogue page → page 2
echo $resolver->resolve(
    'catalogue/page-2.html',
    'https://books.toscrape.com/'
) . PHP_EOL;

// Test 2: Page 2 → page 3
echo $resolver->resolve(
    'page-3.html',
    'https://books.toscrape.com/catalogue/page-2.html'
) . PHP_EOL;

// Test 3: Page 4 → page 5
echo $resolver->resolve(
    'page-5.html',
    'https://books.toscrape.com/catalogue/page-4.html'
) . PHP_EOL;

// 4. Fetch individual book details
// $books = $scraper->scrape(5);

// foreach ($books as $book) {

//     // Resolve product URL
//     $productUrl = $resolver->resolve($book->productUrl);

//     // Fetch product page
//     $detailResponse = $client->get($productUrl);

//     $detailHtml = $detailResponse->getBody();

//     // Parse product details
//     $bookDetails = $parser->parseDetails($detailHtml);
//     $categoryId = $categoryRepository->findOrCreate(
//     $bookDetails->category
// );

//     // Resolve image URL
//     $imageUrl = $resolver->resolve($book->imageUrl);

//     // Normalize monetary values
//     $price = preg_replace('/[^\d.]/', '', $book->price);
//     $priceExclTax = $bookDetails->priceExclTax !== null
//         ? preg_replace('/[^\d.]/', '', $bookDetails->priceExclTax)
//         : null;

//     $priceInclTax = $bookDetails->priceInclTax !== null
//         ? preg_replace('/[^\d.]/', '', $bookDetails->priceInclTax)
//         : null;

//     $tax = $bookDetails->tax !== null
//         ? preg_replace('/[^\d.]/', '', $bookDetails->tax)
//         : null;

//     // Create complete Book object
//     $completeBook = new Book(
//         upc: $bookDetails->upc,
//         title: $book->title,
//         price: $price,
//         productUrl: $productUrl,
//         availability: $bookDetails->availability,
//         rating: $book->rating,
//         imageUrl: $imageUrl,
//         productType: $bookDetails->productType,
//         priceExclTax: $priceExclTax,
//         priceInclTax: $priceInclTax,
//         tax: $tax,
//         numReviews: $bookDetails->numReviews,
//         description: $bookDetails->description,
//         categoryId: $categoryId
//     );

//     echo "\n-----------------------------\n";
//    print_r($completeBook);
// }
// $lastPageResponse = $client->get(
//     'https://books.toscrape.com/catalogue/page-50.html'
// );

// $lastPageHtml = $lastPageResponse->getBody();

// $nextPage = $parser->getNextPageUrl($lastPageHtml);

// var_dump($nextPage);


$books = $scraper->scrape(2);

echo "Total complete books: " . count($books) . PHP_EOL;

foreach ($books as $book) {
    print_r($book);
}

