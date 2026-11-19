<?php

use App\Repositories\BookRepository;
use App\Database\DatabaseConnection;
use App\Logging\Logger;

use App\Http\HttpClient;
use App\Support\UrlResolver;
use App\Parsers\BookParser;
use App\Repositories\CategoryRepository;
use App\Scrapers\Scraper;

require_once __DIR__ . '/../bootstrap.php';
$logger = new Logger(__DIR__ . '/../logs/scraper.log');



$baseUrl = 'https://books.toscrape.com/';

$db = new DatabaseConnection(
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT'],
    $_ENV['DB_DATABASE'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);
$pdo =  $db->getConnection();

$httpClient = new HttpClient($logger);
$urlResolver = new UrlResolver($baseUrl);
$bookParser = new BookParser();

$categoryRepository = new CategoryRepository($pdo);
$bookrepository = new BookRepository($pdo);

$scraper = new Scraper(
    $logger,
    $httpClient,
    $bookParser,
    $urlResolver,
    $categoryRepository,
    $bookrepository,
);

$progressFile = __DIR__ . '/scrape-progress.json';

// GET: Return current scraping progress
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    header('Content-Type: application/json');

    if (!file_exists($progressFile)) {
        echo json_encode([
            'status' => 'idle',
            'totalBooksExpected' => 0,
            'totalProcessed' => 0,
            'successful' => 0,
            'failed' => 0,
            'pagesProcessed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'saveFailed' => 0,
            'errors' => [],
            'scrapeError' => null,
        ]);
        exit;
    }
    $content = file_get_contents($progressFile);

    if ($content === false) {
        http_response_code(500);

        echo json_encode([
            'status' => 'error',
            'message' => 'Unable to read scraping progress file'
        ]);

        exit;
    }

    echo $content;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');

    http_response_code(405);
    exit('Method Not Allowed');
}

$scrapeAll = isset($_POST['scrape_all']) && $_POST['scrape_all'] === '1';

if ($scrapeAll) {
    $pages = null;
} else {
    $pages = filter_input(INPUT_POST, 'pages', FILTER_VALIDATE_INT);


    if ($pages < 1 || !$pages) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid page count'
        ]);

        exit;
    }
}


$totalBooksExpected = $pages !== null ? $pages * 20 : 0;
$stopFile = dirname(__DIR__) . '/storage/stop-request.txt';

if (file_exists($stopFile)) {
    unlink($stopFile);
}

$scraper->setProgressCallback(function (array $stats) use ($progressFile, $totalBooksExpected) {



    $progress = array_merge([
        'status' => 'running',
        'totalBooksExpected' => $totalBooksExpected,
    ], $stats);

    file_put_contents(
        $progressFile,
        json_encode($progress, JSON_PRETTY_PRINT),
        LOCK_EX
    );
});




file_put_contents(
    $progressFile,
    json_encode([
        'status' => 'running',
        'totalBooksExpected' => $totalBooksExpected,
        'totalProcessed' => 0,
        'successful' => 0,
        'failed' => 0,
        'pagesProcessed' => 0,
        'inserted' => 0,
        'updated' => 0,
        'unchanged' => 0,
        'saveFailed' => 0,
        'errors' => []
    ]),
    LOCK_EX
);


// Execute scraping only once
$scraper->scrape($pages);

// Get final scraping statistics
$stats = $scraper->getStats();

// Prepare final report
$report = [
    'status' => $stats['scrapeError'] !== null
        ? 'error'
        : ($stats['stopped'] ? 'stopped' : 'completed'),

    'totalBooksExpected' => $totalBooksExpected,
    'totalProcessed' => $stats['totalProcessed'],
    'successful' => $stats['successful'],
    'failed' => $stats['failed'],
    'pagesProcessed' => $stats['pagesProcessed'],

    'inserted' => $stats['inserted'],
    'updated' => $stats['updated'],
    'unchanged' => $stats['unchanged'],
    'saveFailed' => $stats['saveFailed'],

    'errors' => $stats['errors'],
    'scrapeError' => $stats['scrapeError'],
];

// Save final progress
file_put_contents(
    $progressFile,
    json_encode($report, JSON_PRETTY_PRINT),
    LOCK_EX
);

// Remove stop request file
if (file_exists($stopFile)) {
    unlink($stopFile);
}

// Return final response
echo json_encode([
    'success' => $report['status'] !== 'error',
    'status' => $report['status'],
    'message' => match ($report['status']) {
        'error' => 'Scraping failed while processing a catalogue page',
        'stopped' => 'Scraping stopped successfully',
        default => 'Scraping completed'
    }
]);

exit;
