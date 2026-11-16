
<?php

use App\Database\DatabaseConnection;
use App\Repositories\BookRepository;
use App\Scrapers\Scraper;

require_once __DIR__ . '/../bootstrap.php';

// Database connection
$database = new DatabaseConnection(
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT'],
    $_ENV['DB_DATABASE'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

$pdo = $database->getConnection();

// Repository
$bookRepository = new BookRepository($pdo);

// Get existing books
$books = $bookRepository->findAll();

$totalBooks = count($books);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Book Scraper Dashboard</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

    <!-- Navigation -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                Book Scraper
            </a>

            <a href="books.php" class="btn btn-outline-light btn-sm">
                View All Books
            </a>
        </div>
    </nav>


    <div class="container mt-5">

        <!-- Dashboard Heading -->
        <div class="mb-4">
            <h2>Dashboard</h2>
            <p class="text-muted">
                Manage your book scraping activities.
            </p>
        </div>


        <!-- Database Overview -->
        <div class="row mb-4">

            <div class="col-md-4">

                <div class="card shadow-sm">
                    <div class="card-body">

                        <h6 class="text-muted">Total Books Stored</h6>

                        <h2 class="fw-bold">
                            <?= $totalBooks ?>
                        </h2>

                        <p class="text-muted mb-0">
                            Books available in database
                        </p>

                    </div>
                </div>

            </div>

        </div>


        <!-- Scraping Form -->
        <div class="row">

            <div class="col-md-7">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <h4 class="mb-3">Start Scraping</h4>

                        <form action="scrape.php" method="POST" id="scrapeForm">

                            <!-- Number of Pages -->
                            <div class="mb-3">

                                <label for="pages" class="form-label">
                                    Number of pages (20 books each)
                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    id="pages"
                                    name="pages"
                                    min="1"
                                    value="5"
                                    required
                                >

                                <div class="form-text">
                                    Expected books:
                                    <span id="expectedBooks">100</span>
                                </div>

                            </div>


                            <!-- Scrape All Checkbox -->
                            <div class="form-check mb-4">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="scrape_all"
                                    name="scrape_all"
                                    value="1"
                                >

                                <label
                                    class="form-check-label"
                                    for="scrape_all"
                                >
                                    Scrape all available pages
                                </label>

                            </div>


                            <!-- Submit Button -->
                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                                id="startButton"
                            >
                                Start Scraping
                            </button>

                        </form>

                                            <!-- Scraping Progress -->
                        <div id="progressSection" class="mt-4 d-none">

                            <h6 id="progressStatus">Scraping in progress...</h6>

                            <div class="progress mb-2" role="progressbar">

                                <div
                                    id="progressBar"
                                    class="progress-bar progress-bar-striped progress-bar-animated"
                                    style="width: 0%"
                                >
                                    0%
                                </div>

                            </div>

                            <p id="progressText" class="text-muted mb-0">
                                Waiting to start...
                            </p>

                        </div>


                    </div>

                </div>

            </div>


            <!-- Scraping Report -->
<div id="scrapingReport" class="card shadow-sm mt-4 d-none">

    <div class="card-body">

        <h5 class="mb-3">Scraping Report</h5>

        <div class="row g-3">

            <div class="col-md-3">
                <h6>Expected Books</h6>
                <h4 id="reportExpected">0</h4>
            </div>

            <div class="col-md-3">
                <h6>Pages Processed</h6>
                <h4 id="reportPages">0</h4>
            </div>

            <div class="col-md-3">
                <h6>Successfully Scraped</h6>
                <h4 class="text-success" id="reportSuccessful">0</h4>
            </div>

            <div class="col-md-3">
                <h6>Failed</h6>
                <h4 class="text-danger" id="reportFailed">0</h4>
            </div>

        </div>

        <hr>

        <h6>Database Saving Report</h6>

        <p>Inserted: <span id="reportInserted">0</span></p>
        <p>Updated: <span id="reportUpdated">0</span></p>
        <p>Unchanged: <span id="reportUnchanged">0</span></p>
        <p>Save Failed: <span id="reportSaveFailed">0</span></p>

        <hr>

        <h6>Scraping Errors</h6>

        <div id="reportErrors">
            No errors.
        </div>

    </div>

</div>

        <!-- View Books -->
        <div class="card shadow-sm mt-4">

            <div class="card-body d-flex justify-content-between align-items-center">

                <div>
                    <h5>Book Management</h5>

                    <p class="text-muted mb-0">
                        Browse and manage all books stored in your database.
                    </p>
                </div>

                <a href="books.php" class="btn btn-outline-primary">
                    View Books
                </a>

            </div>

        </div>

    </div>


    <!-- Bootstrap JavaScript -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>


    <!-- Page Count Calculation -->
    <script>

        const pagesInput = document.getElementById('pages');
        const scrapeAllCheckbox = document.getElementById('scrape_all');
        const expectedBooks = document.getElementById('expectedBooks');


        const scrapeForm=document.getElementById('scrapeForm');
        const startButton=document.getElementById('startButton');
        const progressSection= document.getElementById('progressSection');
        const progressText= document.getElementById('progressText');
        const progressBar= document.getElementById('progressBar');
        const progressStatus= document.getElementById('progressStatus');


        const scrapingReport = document.getElementById('scrapingReport');

        const reportExpected = document.getElementById('reportExpected');
        const reportPages = document.getElementById('reportPages');
        const reportSuccessful = document.getElementById('reportSuccessful');
        const reportFailed = document.getElementById('reportFailed');

        const reportInserted = document.getElementById('reportInserted');
        const reportUpdated = document.getElementById('reportUpdated');
        const reportUnchanged = document.getElementById('reportUnchanged');
        const reportSaveFailed = document.getElementById('reportSaveFailed');

        const reportErrors = document.getElementById('reportErrors');

        let pollingInterval= null;

       scrapeForm.addEventListener('submit', function(event) {

            event.preventDefault();

            const formData = new FormData(scrapeForm);

            startButton.disabled = true;
            startButton.textContent = 'Scraping...';

            progressSection.classList.remove('d-none');

            progressBar.style.width = '0%';
            progressBar.textContent = '0%';

            progressText.textContent = 'Starting scraper...';
            progressStatus.textContent = 'Scraping in progress...';

            // Start polling immediately
            startPolling();

            // Start scraping independently
            fetch('scrape.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {

                if (!response.ok) {
                    throw new Error('Failed to start scraping');
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Scraping failed');
                }

            })
            .catch(error => {

                clearInterval(pollingInterval);

                progressStatus.textContent = 'Error';
                progressText.textContent = error.message;

                startButton.disabled = false;
                startButton.textContent = 'Start Scraping';

            });

        });

    function startPolling(){
        if(pollingInterval!==null){
            clearInterval(pollingInterval);
        }

        pollingInterval= setInterval(getProgress, 1000);

    }

    async function getProgress() {
        try{
            const response = await fetch('scrape.php',{
                method :'GET',
                cache :'no-store'
            });
            
            const data = await response.json();

            if (data.status === 'error') {
                clearInterval(pollingInterval);

                progressStatus.textContent = 'Scraping failed';
                startButton.disabled = false;
                startButton.textContent = 'Start Scraping';

                return;
            }


            const total =data.totalBooksExpected ||0 ;
            const processed= data.totalProcessed || 0;

            let percentage =total>0
                ? Math.min(Math.round((processed/total)*100),100)
                : 0;

            progressBar.style.width = percentage + '%';
            progressBar.textContent = percentage + '%';


            progressText.textContent =  `${processed} books  processed |Successful : ${data.successful || 0 } | failed:${data.failed || 0}`;
            
            if (data.status === 'completed') {

                clearInterval(pollingInterval);

                progressStatus.textContent = 'Scraping completed';

                progressText.textContent =
                    `Processed: ${processed} | Inserted: ${data.inserted || 0} | Updated: ${data.updated || 0} | Unchanged: ${data.unchanged || 0}`;

                displayReport(data);
                
                progressBar.classList.remove('progress-bar-animated');

                startButton.disabled = false;
                startButton.textContent = 'Start Scraping';

            }
        }catch (error){
            console.error("Progress error :", error);
        }

    }

    function displayReport(data) {

    scrapingReport.classList.remove('d-none');

    reportExpected.textContent = data.totalBooksExpected ?? 0;
    reportPages.textContent = data.pagesProcessed ?? 0;
    reportSuccessful.textContent = data.successful ?? 0;
    reportFailed.textContent = data.failed ?? 0;

    reportInserted.textContent = data.inserted ?? 0;
    reportUpdated.textContent = data.updated ?? 0;
    reportUnchanged.textContent = data.unchanged ?? 0;
    reportSaveFailed.textContent = data.saveFailed ?? 0;

    reportErrors.innerHTML = '';

    if (data.errors && data.errors.length > 0) {

        data.errors.forEach(error => {

            const errorItem = document.createElement('p');

            errorItem.textContent =
                `${error.title}: ${error.message}`;

            reportErrors.appendChild(errorItem);

        });

    } else {

        reportErrors.textContent = 'No errors.';

    }

}
        






        // Calculate expected books
        pagesInput.addEventListener('input', function () {

            const pages = parseInt(this.value) || 0;

            expectedBooks.textContent = pages * 20;

        });


        // Disable page input when scraping all pages
        scrapeAllCheckbox.addEventListener('change', function () {

            pagesInput.disabled = this.checked;

            if (this.checked) {
                expectedBooks.textContent = 'All available books';
            } else {
                expectedBooks.textContent =
                    (parseInt(pagesInput.value) || 0) * 20;
            }

        });


        

    </script>


</body>
</html>
