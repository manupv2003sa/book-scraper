<?php

require_once __DIR__ . '/bootstrap.php';

use App\Http\HttpClient;
use App\Parsers\BookParser;

// Fetch website HTML
$client = new HttpClient();

$html = $client->get('https://books.toscrape.com');

// Parse HTML
$parser = new BookParser();

$books = $parser->parseListing($html);

echo $books[0]->title;
// print_r($arr);
foreach ($books as $book) {
    $productUrl = 'https://books.toscrape.com/' . $book->productUrl;
    $detailHtml = $client->get($productUrl);

    $bookDetails = $parser->parseDetails($detailHtml);
    print_r($bookDetails);
}
