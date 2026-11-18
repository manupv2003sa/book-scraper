<?php 

use App\Repositories\BookRepository;
use App\Database\DatabaseConnection;

use App\Http\HttpClient;
use App\Support\UrlResolver;
use App\Parsers\BookParser;
use App\Repositories\CategoryRepository;
use App\Scrapers\Scraper;

require_once __DIR__ . '/../bootstrap.php';


$baseUrl = 'https://books.toscrape.com/';

 $db = new DatabaseConnection(
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'],
        $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
$pdo=  $db->getConnection();

$httpClient= new HttpClient();
$urlResolver = new UrlResolver($baseUrl);
$bookParser = new BookParser();

$categoryRepository= new CategoryRepository($pdo);
$bookrepository = new BookRepository($pdo);

$scraper = new Scraper(
    $httpClient,
    $bookParser,
    $urlResolver,
    $categoryRepository,
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
            'errors' => []
        ]);
        exit;
    }

    echo file_get_contents($progressFile);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$pages= filter_input(INPUT_POST, 'pages', FILTER_VALIDATE_INT);

if($pages<1 || !$pages){
    exit('Invalid page count');
}

  $stopFile = dirname(__DIR__) . '/storage/stop-request.txt';

    if (file_exists($stopFile)) {
        unlink($stopFile);
    }

$scraper->setProgressCallback(function (array $stats) use ($progressFile, $pages) {

  

    $progress = array_merge([
        'status' => 'running',
        'totalBooksExpected' => $pages * 20,
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
        'totalBooksExpected' => $pages * 20,
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

$scrapedBooks = $scraper->scrape($pages);

$stats= $scraper->getStats();
$totalProcessed = $stats['totalProcessed'];
$successful = $stats['successful'];
$failed = $stats['failed'];
$pagesProcessed = $stats['pagesProcessed'];
$errors = $stats['errors'];


$inserted = 0;
$updated = 0;
$unchanged = 0;
$saveFailed = 0;
foreach ($scrapedBooks as $book) {
    try {
        $result = $bookrepository->save($book);


        if ($result === 'inserted') {
            $inserted++;
        } elseif ($result === 'updated') {
            $updated++;
        } elseif ($result === 'unchanged') {
            $unchanged++;
        }

    } catch (Throwable $e) {
        $saveFailed++;
    }
}

$report = [
    'status' => $stats['stopped'] ? 'stopped' : 'completed',    
    'totalBooksExpected' => $pages * 20,
    'totalProcessed' => $totalProcessed,
    'successful' => $successful,
    'failed' => $failed,
    'pagesProcessed' => $pagesProcessed,
    'inserted' => $inserted,
    'updated' => $updated,
    'unchanged' => $unchanged,
    'saveFailed' => $saveFailed,
    'errors' => $errors
];


file_put_contents(
    $progressFile,
    json_encode($report, JSON_PRETTY_PRINT),
    LOCK_EX
);

if (file_exists($stopFile)) {
    unlink($stopFile);
}


header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'status' => $report['status'],
    'message' => $report['status'] === 'stopped'
        ? 'Scraping stopped successfully'
        : 'Scraping completed'
]);

exit;
?>
