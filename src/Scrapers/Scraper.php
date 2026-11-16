<?php

namespace App\Scrapers;

use App\Http\HttpClient;
use App\Parsers\BookParser;
use App\Support\UrlResolver;
use App\Repositories\CategoryRepository;
use App\Models\Book;
use Closure;

class Scraper
{
    private HttpClient $httpClient;
    private BookParser $bookParser;
    private UrlResolver $urlResolver;
    private CategoryRepository $categoryRepository;


    private int $totalProcessed = 0;
    private int $successful = 0;
    private int $failed = 0;
    private array $errors = [];
    private int $pagesProcessed = 0;

    private ?Closure $progressCallback = null;

    public function __construct(
        HttpClient $httpClient,
        BookParser $bookParser,
        UrlResolver $urlResolver,
        CategoryRepository $categoryRepository
    ) {
        $this->httpClient = $httpClient;
        $this->bookParser = $bookParser;
        $this->urlResolver = $urlResolver;
        $this->categoryRepository = $categoryRepository;
    }

    public function scrape(int $maxPages = 1): array
    {
        if ($maxPages < 1) {
            throw new \InvalidArgumentException(
                'Maximum pages must be at least 1.'
            );
        }

        $this->totalProcessed = 0;
        $this->successful = 0;
        $this->failed = 0;
        $this->errors = [];
        $this->pagesProcessed = 0;

        $allBooks = [];
        $currentUrl = 'https://books.toscrape.com/';

        // Catalogue pagination
        while (
            $currentUrl !== null &&
            $this->pagesProcessed < $maxPages
        ) {

            // 1. Fetch catalogue page
            $response = $this->httpClient->get($currentUrl);

            // 2. Extract HTML
            $html = $response->getBody();

            // 3. Parse catalogue books
            $books = $this->bookParser->parseListing($html);

            $this->pagesProcessed++;

            // 4. Process each individual book
            foreach ($books as $book) {
                
                $this->totalProcessed++;

                try{

                    // Resolve product URL
                    $productUrl = $book->productUrl !== null
                        ? $this->urlResolver->resolve(
                            $book->productUrl,
                            $currentUrl
                        )
                        : null;

                    if ($productUrl === null || $productUrl === '') {
                        throw new \RuntimeException("Product URL is missing.");
                    }



                    // Fetch individual product page
                    $detailResponse = $this->httpClient->get($productUrl);

                    $detailHtml = $detailResponse->getBody();

                    // Parse individual product details
                    $bookDetails = $this->bookParser->parseDetails($detailHtml);

              

                    // Resolve image URL
                    $imageUrl = $book->imageUrl !== null
                        ? $this->urlResolver->resolve(
                            $book->imageUrl,
                            $currentUrl
                        )
                        : null;

                    // Normalize monetary values
                    $price = $book->price !== null
                        ? preg_replace('/[^\d.]/', '', $book->price)
                        : null;

                    $priceExclTax = $bookDetails->priceExclTax !== null
                        ? preg_replace('/[^\d.]/', '', $bookDetails->priceExclTax)
                        : null;

                    $priceInclTax = $bookDetails->priceInclTax !== null
                        ? preg_replace('/[^\d.]/', '', $bookDetails->priceInclTax)
                        : null;

                    $tax = $bookDetails->tax !== null
                        ? preg_replace('/[^\d.]/', '', $bookDetails->tax)
                        : null;

                    // Validate required fields
                   if (
                        $bookDetails->upc === null ||
                        trim($bookDetails->upc) === '' ||
                        trim($book->title) === '' ||
                        $price === null ||
                        $price === '' ||
                        !is_numeric($price) ||(float) $price <= 0 || 
                        $productUrl === ''
                    ) {
                        throw new \RuntimeException(
                            "Required book information is missing or invalid."
                        );
                    }

                    $categoryId = null;

                    if ($bookDetails->category !== null) {
                        $categoryId = $this->categoryRepository->findOrCreate(
                            $bookDetails->category
                        );
                    }
                    // Create complete Book object
                    $completeBook = new Book(
                        upc: $bookDetails->upc,
                        title: $book->title,
                        price: $price,
                        productUrl: $productUrl,
                        availability: $bookDetails->availability,
                        rating: $book->rating,
                        imageUrl: $imageUrl,
                        productType: $bookDetails->productType,
                        priceExclTax: $priceExclTax,
                        priceInclTax: $priceInclTax,
                        tax: $tax,
                        numReviews: $bookDetails->numReviews,
                        description: $bookDetails->description,
                        categoryId: $categoryId
                    );

                    // Collect complete Book object
                    $allBooks[] = $completeBook;
                    $this->successful++;
                }catch(\RuntimeException $e)
                {
                    $this->failed++;
                    $this->errors[] = [
                        'title' => $book->title,
                        'message' => $e->getMessage(),
                    ];
                    continue;

                }
                $this->publishProgress();
            }

   

            // 6. Extract next-page relative URL
            $nextPageUrl = $this->bookParser->getNextPageUrl($html);

            // 7. Resolve next-page URL
            $currentUrl = $nextPageUrl !== null
                ? $this->urlResolver->resolve(
                    $nextPageUrl,
                    $currentUrl
                )
                : null;
        }

        return $allBooks;
    }

    public  function setProgressCallback(Closure $callback): void{
        $this->progressCallback=$callback;
    }

    private function publishProgress(): void
    {
        if ($this->progressCallback !== null) {
            ($this->progressCallback)($this->getStats());
        }
    }


    public function getStats(): array
    {
        return [
            'totalProcessed' => $this->totalProcessed,
            'successful' => $this->successful,
            'failed' => $this->failed,
            'errors' => $this->errors,
            'pagesProcessed' =>$this->pagesProcessed ,
        ];
    }
}
