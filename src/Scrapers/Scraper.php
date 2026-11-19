<?php

namespace App\Scrapers;

use App\Logging\LoggerInterface;
use App\Http\HttpClient;
use App\Parsers\BookParser;
use App\Support\UrlResolver;
use App\Repositories\CategoryRepository;
use App\Repositories\BookRepository;

use App\Models\Book;
use Closure;

class Scraper
{
    private LoggerInterface $logger;
    private HttpClient $httpClient;
    private BookParser $bookParser;
    private UrlResolver $urlResolver;
    private CategoryRepository $categoryRepository;
    private BookRepository $bookRepository;

    private int $totalProcessed = 0;
    private int $successful = 0;
    private int $failed = 0;
    private array $errors = [];
    private int $pagesProcessed = 0;
    private int $inserted = 0;
    private int $updated = 0;
    private int $unchanged = 0;
    private int $saveFailed = 0;

    private string $stopFile;
    private bool $stopped = false;
    private ?string $scrapeError = null;

    private ?Closure $progressCallback = null;

    public function __construct(
        LoggerInterface $logger,
        HttpClient $httpClient,
        BookParser $bookParser,
        UrlResolver $urlResolver,
        CategoryRepository $categoryRepository,
        BookRepository $bookRepository,
    ) {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->bookParser = $bookParser;
        $this->urlResolver = $urlResolver;
        $this->categoryRepository = $categoryRepository;
        $this->bookRepository = $bookRepository;
        $this->stopFile = dirname(__DIR__, 2) . '/storage/stop-request.txt';

    }

    private function isStopRequested(): bool
    {
        return file_exists($this->stopFile);
    }

    public function scrape(?int $maxPages = 1): void
    {
        if ($maxPages !== null && $maxPages < 1) {
            throw new \InvalidArgumentException(
                'Maximum pages must be at least 1.'
            );
        }

        $this->totalProcessed = 0;
        $this->successful = 0;
        $this->failed = 0;
        $this->errors = [];
        $this->pagesProcessed = 0;
        $this->stopped = false;


        $this->inserted = 0;
        $this->updated = 0;
        $this->unchanged = 0;
        $this->saveFailed = 0;
        $this->scrapeError = null;
        $currentUrl = 'https://books.toscrape.com/';

        $this->logger->info('Scraping started', [
            'maxPages' => $maxPages
        ]);

        // Catalogue pagination
        while (
            $currentUrl !== null &&
            ($maxPages === null || $this->pagesProcessed < $maxPages)
        ) {
            if ($this->isStopRequested()) {
                $this->stopped = true;
                break;
            }
        try {
            $this->delayBetweenRequests();

            // 1. Fetch catalogue page
            $response = $this->httpClient->get($currentUrl);

            if ($this->isStopRequested()) {
                $this->stopped = true;
                break;
            }

            // 2. Extract HTML
            $html = $response->getBody();

            // 3. Parse catalogue books
            $books = $this->bookParser->parseListing($html);

            // 4. Extract and resolve the next page URL
            $nextPageUrl = $this->bookParser->getNextPageUrl($html);

            $resolvedNextPageUrl = $nextPageUrl !== null
                ? $this->urlResolver->resolve($nextPageUrl, $currentUrl)
                : null;

            $this->pagesProcessed++;
            $this->logger->info('Catalogue page processed', [
                'url' => $currentUrl,
                'page' => $this->pagesProcessed,
                'booksFound' => count($books)
            ]);

        } catch (\Throwable $e) {

            $this->scrapeError = $e->getMessage();
            $this->logger->error('Failed to process catalogue page', [
                'url' => $currentUrl,
                'error' => $e->getMessage()
            ]);

            $this->publishProgress();

            break;
        }

            // 4. Process each individual book
            foreach ($books as $book) {

                if ($this->isStopRequested()) {
                    $this->stopped = true;
                    break;
                }


                try {

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



                    $this->delayBetweenRequests();



                    // Fetch individual product page
                    $detailResponse = $this->httpClient->get($productUrl);

                    if ($this->isStopRequested()) {
                        $this->stopped = true;
                        break;
                    }

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
                        !is_numeric($price) || (float) $price <= 0 ||
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

                    try {
                        $result = $this->bookRepository->save($completeBook);

                        if ($result === 'inserted') {
                            $this->inserted++;
                        } elseif ($result === 'updated') {
                            $this->updated++;
                        } elseif ($result === 'unchanged') {
                            $this->unchanged++;
                        } else {
                            throw new \RuntimeException(
                                'Unexpected repository save result: ' . $result
                            );
                        }

                        $this->successful++;
                        $this->totalProcessed++;
                    } catch (\Throwable $e) {
                        $this->saveFailed++;
                        throw $e;
                    }
                } catch (\Throwable $e) {
                    $this->failed++;
                    $this->totalProcessed++;

                    $this->logger->error('Failed to process book', [
                        'title' => $book->title ?? 'Unknown book',
                        'error' => $e->getMessage()
                    ]);

                    $this->errors[] = [
                        'title' => $book->title ?? 'Unknown book',
                        'message' => $e->getMessage(),
                    ];

                    $this->publishProgress();

                    continue;
                }
                $this->publishProgress();
            }

            if ($this->stopped) {
                break;
            }

            $currentUrl = $resolvedNextPageUrl;
        }
        $this->logger->info('Scraping finished', [
            'pagesProcessed' => $this->pagesProcessed,
            'totalProcessed' => $this->totalProcessed,
            'successful' => $this->successful,
            'failed' => $this->failed,
            'inserted' => $this->inserted,
            'updated' => $this->updated,
            'unchanged' => $this->unchanged,
            'stopped' => $this->stopped
        ]);
    }

    public  function setProgressCallback(Closure $callback): void
    {
        $this->progressCallback = $callback;
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
            'pagesProcessed' => $this->pagesProcessed,
            'stopped' => $this->stopped,

            'inserted' => $this->inserted,
            'updated' => $this->updated,
            'unchanged' => $this->unchanged,
            'saveFailed' => $this->saveFailed,
            'scrapeError' => $this->scrapeError,
        ];
    }


    private function delayBetweenRequests(): void
    {
        usleep(500000); // 500 milliseconds
    }
}
