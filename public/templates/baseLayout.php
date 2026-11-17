
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle ?? 'Book Scraper') ?></title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

    <!-- Navigation Bar -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">

            <!-- Application Title -->
            <a class="navbar-brand fw-bold" href="index.php">
                Book Scraper
            </a>

            <!-- View Books Button -->
            <a href="books.php" class="btn btn-outline-light btn-sm">
                View Books
            </a>

        </div>
    </nav>

    <!-- Page Content -->
    <main>
        <?= $content ?? '' ?>
    </main>

    <!-- Footer -->
    <footer class="text-center text-muted py-3 mt-5">
        <small>&copy; <?= date('Y') ?> Book Scraper. All rights reserved.</small>
    </footer>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
