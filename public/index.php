<?php

use App\Database\DatabaseConnection;
use App\Repositories\BookRepository;

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

// Page title
$pageTitle = 'Dashboard';

ob_start();

?>

<div class="container py-5">

    <div class="row g-4 align-items-start">

        <!-- Left Column: Dashboard and Database Overview -->
        <div class="col-lg-4">

            <!-- Dashboard Heading -->
            <div class="mb-4">
                <h2 class="fw-bold">Dashboard</h2>
                <p class="text-muted">
                    Manage your book scraping activities and database.
                </p>
            </div>

            <!-- Total Books Card -->
            <div class="card shadow-sm border-0" style="min-height: 235px;">
                <div class="card-body p-4">

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


        <!-- Right Column: Scraping Section -->
        <div class="col-lg-8">

            <div class="card shadow-sm border-0">

                <div class="card-body p-4">

                    <h4 class="mb-3">Start Scraping</h4>

                    <p class="text-muted">
                        Select the number of catalogue pages you want to scrape.
                    </p>

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
                                required>

                            <div class="form-text">
                                Expected books:
                                <strong id="expectedBooks"></strong>
                            </div>

                        </div>


                        <!-- Scrape All Checkbox -->
                        <div class="form-check mb-4">

                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="scrape_all"
                                name="scrape_all"
                                value="1">

                            <label
                                class="form-check-label"
                                for="scrape_all">
                                Scrape all available pages
                            </label>

                        </div>



                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary flex-grow-1"
                                id="startButton">
                                <i class="bi bi-play-circle"></i>
                                Start Scraping
                            </button>

                            <button
                                type="button"
                                class="btn btn-danger flex-grow-1"
                                id="stopButton"
                                disabled>
                                <i class="bi bi-stop-circle"></i>
                                Stop Scraping
                            </button>

                        </div>

                    </form>


                    <!-- Scraping Progress -->
                    <div id="progressSection" class="mt-4 d-none">

                        <h6 id="progressStatus">
                            Scraping in progress...
                        </h6>

                        <div class="progress mb-2" role="progressbar">

                            <div
                                id="progressBar"
                                class="progress-bar progress-bar-striped progress-bar-animated"
                                style="width: 0%">
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

    </div>

</div>



<!-- Scraping Report -->
<div class="row">

    <div class="col-12">

        <div id="scrapingReport" class="card shadow-sm border-0 mt-4 d-none">

            <div class="card-body p-4">

                <h5 class="mb-4">Scraping Report</h5>


                <!-- Scraping Statistics -->
                <div class="row g-3">

                    <div class="col-md-3">

                        <h6 class="text-muted">Expected Books</h6>

                        <h4 id="reportExpected">0</h4>

                    </div>


                    <div class="col-md-3">

                        <h6 class="text-muted">Pages Processed</h6>

                        <h4 id="reportPages">0</h4>

                    </div>


                    <div class="col-md-3">

                        <h6 class="text-muted">Successfully Scraped</h6>

                        <h4 class="text-success" id="reportSuccessful">
                            0
                        </h4>

                    </div>


                    <div class="col-md-3">

                        <h6 class="text-muted">Failed</h6>

                        <h4 class="text-danger" id="reportFailed">
                            0
                        </h4>

                    </div>

                </div>


                <hr class="my-4">


                <!-- Database Saving Report -->
                <h6 class="mb-3">Database Saving Report</h6>

                <div class="row g-3">

                    <div class="col-md-3">
                        <p class="mb-1 text-muted">Inserted</p>
                        <h5 id="reportInserted">0</h5>
                    </div>

                    <div class="col-md-3">
                        <p class="mb-1 text-muted">Updated</p>
                        <h5 id="reportUpdated">0</h5>
                    </div>

                    <div class="col-md-3">
                        <p class="mb-1 text-muted">Unchanged</p>
                        <h5 id="reportUnchanged">0</h5>
                    </div>

                    <div class="col-md-3">
                        <p class="mb-1 text-muted">Save Failed</p>
                        <h5 class="text-danger" id="reportSaveFailed">0</h5>
                    </div>

                </div>


                <hr class="my-4">


                <!-- Scraping Errors -->
                <h6 class="mb-3">Scraping Errors</h6>

                <div id="reportErrors">
                    No errors.
                </div>

            </div>

        </div>

    </div>

</div>

</div>


<!-- Page JavaScript -->
<script>
    const pagesInput = document.getElementById('pages');
    const scrapeAllCheckbox = document.getElementById('scrape_all');
    const expectedBooks = document.getElementById('expectedBooks');

    const scrapeForm = document.getElementById('scrapeForm');
    const startButton = document.getElementById('startButton');

    const progressSection = document.getElementById('progressSection');
    const progressText = document.getElementById('progressText');
    const progressBar = document.getElementById('progressBar');
    const progressStatus = document.getElementById('progressStatus');

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

    const stopButton = document.getElementById('stopButton');

    let pollingInterval = null;


    // Start scraping
    scrapeForm.addEventListener('submit', function(event) {

        event.preventDefault();

        const formData = new FormData(scrapeForm);

        startButton.disabled = true;
        startButton.textContent = 'Scraping...';

         stopButton.disabled = false;
         stopButton.innerHTML = '<i class="bi bi-stop-circle"></i> Stop Scraping';


        progressSection.classList.remove('d-none');
        scrapingReport.classList.add('d-none');

        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        progressBar.classList.add('progress-bar-animated');

        progressText.textContent = 'Starting scraper...';
        progressStatus.textContent = 'Scraping in progress...';

        // Start polling
        startPolling();

        // Start scraping
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

                stopButton.disabled = true;
                stopButton.textContent = 'Stop Scraping';

            });

    });

    stopButton.addEventListener('click', async function () {
        const confirmed = confirm('Are you sure you want to stop scraping?');

        if(!confirmed) return;

        stopButton.disabled=true;
        stopButton.innerHTML= 'Stopping...';

        try{
            const response =await fetch ('stop-scraping.php',{
                method : 'POST'
            });

            const result =await response.json();

            if(!response.ok || !result.success){
                throw new Error (result.message || 'Unable to stop scraping');
            }

            progressStatus.textContent = ' Stop requested';
            progressText.textContent = 'Waiting for scraper to stop...';
        }catch (error){
            alert(error.message);

            stopButton.disabled=false;
            stopButton.innerHTML = '<i class="bi bi-stop-circle"></i> Stop Scraping';

        }
    })


    // Start progress polling
    function startPolling() {

        if (pollingInterval !== null) {
            clearInterval(pollingInterval);
        }

        pollingInterval = setInterval(getProgress, 1000);

    }


    // Get scraping progress
    async function getProgress() {

        try {

            const response = await fetch('scrape.php', {
                method: 'GET',
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('Unable to fetch scraping progress');
            }

            const data = await response.json();


            if (data.status === 'error') {

                clearInterval(pollingInterval);

                progressStatus.textContent = 'Scraping failed';

                startButton.disabled = false;
                startButton.textContent = 'Start Scraping';

                stopButton.disabled = true;
                stopButton.textContent = 'Stop Scraping';

                progressBar.classList.remove('progress-bar-animated');

                return;
            }


            const total = data.totalBooksExpected || 0;
            const processed = data.totalProcessed || 0;

            const percentage = total > 0 ?
                Math.min(Math.round((processed / total) * 100), 100) :
                0;


            progressBar.style.width = percentage + '%';
            progressBar.textContent = percentage + '%';


            progressText.textContent =
                `${processed} books processed | Successful: ${data.successful || 0} | Failed: ${data.failed || 0}`;


            // Scraping stopped by user
            if (data.status === 'stopped') {

                clearInterval(pollingInterval);

                progressStatus.textContent = 'Scraping stopped';

                progressText.textContent =
                   'Scraping stopped by user.';

                displayReport(data);

                progressBar.classList.remove('progress-bar-animated');

                startButton.disabled = false;
                startButton.textContent = 'Start Scraping';

                stopButton.disabled = true;
                stopButton.textContent = 'Stop Scraping';

                return;
            }

            // Scraping completed
            if (data.status === 'completed') {

                clearInterval(pollingInterval);

                progressStatus.textContent = 'Scraping completed';

                progressText.textContent = 'Scraping completed successfully.';

                displayReport(data);

                progressBar.classList.remove('progress-bar-animated');

                startButton.disabled = false;
                startButton.textContent = 'Start Scraping';

            stopButton.disabled = true;
            stopButton.textContent = 'Stop Scraping';

            }

        } catch (error) {

            console.error('Progress error:', error);

        }

    }


    // Display scraping report
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

    function updateExpected(){
        const pages = parseInt(pagesInput.value) || 0;

        expectedBooks.textContent = pages * 20;    
    }


    // Calculate expected books
    pagesInput.addEventListener('input', updateExpected); 
    updateExpected();


    // Disable page input when scraping all pages
    scrapeAllCheckbox.addEventListener('change', function() {

        pagesInput.disabled = this.checked;

        if (this.checked) {
            pagesInput.removeAttribute('required');
        } else {
            pagesInput.setAttribute('required', '');
        }

    });
</script>


<?php

$content = ob_get_clean();

require_once __DIR__ . '/templates/baseLayout.php';
?>