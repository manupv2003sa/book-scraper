<?php
require_once __DIR__ . '../../bootstrap.php';
use App\Repositories\BookRepository;
use App\Database\DatabaseConnection;

$db= new DatabaseConnection(
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT'],
    $_ENV['DB_DATABASE'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

$pdo = $db->getConnection();


$repository = new BookRepository($pdo);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    die('Invalid book ID');
}

$book = $repository->findById($id);

if (!$book) {
    http_response_code(404);
    die('Book not found');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Book Details</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container mt-5">

    <!-- Page heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Book Details</h2>

        <a href="books.php" class="btn btn-secondary">
            Back to Books
        </a>
    </div>

    <!-- Main book information -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">

                <!-- Book image -->
                <div class="col-md-3 text-center mb-3">
                    <img
                        src="<?= htmlspecialchars($book['image_url'] ?? '') ?>"
                        alt="Book cover"
                        class="img-fluid"
                        style="max-height: 250px;"
                    >
                </div>

                <!-- Book title and basic details -->
                <div class="col-md-9">
                    <h3><?= htmlspecialchars($book['title']) ?></h3>

                    <h4 class="text-success mt-3">
                        £<?= htmlspecialchars($book['price']) ?>
                    </h4>

                    <p>
                        <strong>Rating:</strong>
                        <?= htmlspecialchars((string) $book['rating']) ?>/5
                    </p>

                    <p>
                        <strong>Availability:</strong>
                        <?= htmlspecialchars($book['availability'] ?? 'N/A') ?>
                    </p>

                    <p>
                        <strong>Category:</strong>
                        <?= htmlspecialchars($book['category_name'] ?? 'Uncategorized') ?>
                    </p>
                </div>

            </div>
        </div>
    </div>

    <!-- Additional information -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h4 class="mb-3">Product Information</h4>

            <table class="table table-bordered">
                <tr>
                    <th>UPC</th>
                    <td><?= htmlspecialchars($book['upc']) ?></td>
                </tr>

                <tr>
                    <th>Product Type</th>
                    <td><?= htmlspecialchars($book['product_type'] ?? 'N/A') ?></td>
                </tr>

                <tr>
                    <th>Price Excluding Tax</th>
                    <td>£<?= htmlspecialchars($book['price_excl_tax'] ?? 'N/A') ?></td>
                </tr>

                <tr>
                    <th>Price Including Tax</th>
                    <td>£<?= htmlspecialchars($book['price_incl_tax'] ?? 'N/A') ?></td>
                </tr>

                <tr>
                    <th>Tax</th>
                    <td>£<?= htmlspecialchars($book['tax'] ?? 'N/A') ?></td>
                </tr>

                <tr>
                    <th>Number of Reviews</th>
                    <td><?= htmlspecialchars((string) ($book['num_reviews'] ?? 0)) ?></td>
                </tr>
            </table>

        </div>
    </div>

    <!-- Description -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h4 class="mb-3">Description</h4>

            <p>
                <?= nl2br(htmlspecialchars($book['description'] ?? 'No description available.')) ?>
            </p>
        </div>
    </div>

</div>

</body>
</html>
