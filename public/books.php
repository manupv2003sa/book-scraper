
<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Repositories\BookRepository;
use App\Database\DatabaseConnection;

$db = new DatabaseConnection(
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT'],
    $_ENV['DB_DATABASE'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

$pdo = $db->getConnection();

$repository = new BookRepository($pdo);

// Pagination configuration
$limit = 20;

// Get and validate requested page
$reqPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);

if ($reqPage === false || $reqPage === null || $reqPage < 1) {
    $reqPage = 1;
}

// Get total number of books
$countAll = $repository->countAll();

// Calculate total pages
$totalPages = (int) ceil($countAll / $limit);

// Handle empty database
if ($totalPages === 0) {
    $paginatedContent = [];
} else {

    // Redirect if requested page exceeds available pages
    if ($reqPage > $totalPages) {
        header("Location: books.php?page=" . $totalPages);
        exit;
    }

    // Calculate offset
    $offset = ($reqPage - 1) * $limit;

    // Fetch current page books
    $paginatedContent = $repository->findPaginated($limit, $offset);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Book Management</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

    <div class="container mt-5">

        <h2>Book Management</h2>
        <p>Manage all scraped books here.</p>

        <div class="card shadow-sm">
            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Book Title</th>
                                <th>Price</th>
                                <th>Rating</th>
                                <th>Availability</th>
                                <th>Category</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (empty($paginatedContent)): ?>

                                <tr>
                                    <td colspan="7" class="text-center">
                                        No books found.
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($paginatedContent as $index => $book): ?>

                                    <tr>
                                        <td>
                                            <?= $offset + $index + 1 ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($book['title']) ?>
                                        </td>

                                        <td>
                                            £<?= htmlspecialchars($book['price']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars((string) ($book['rating'] ?? 'N/A')) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($book['availability'] ?? 'N/A') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($book['category_name'] ?? 'Uncategorized') ?>
                                        </td>

                                        <td>
                                            <a
                                                href="book-details.php?id=<?= (int) $book['id'] ?>"
                                                class="btn btn-primary btn-sm"
                                            >
                                                View
                                            </a>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>

                    <nav aria-label="Book pagination">

                        <ul class="pagination justify-content-center">

                            <li class="page-item <?= $reqPage <= 1 ? 'disabled' : '' ?>">
                                <a
                                    class="page-link"
                                    href="?page=<?= $reqPage - 1 ?>"
                                >
                                    Previous
                                </a>
                            </li>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                                <li class="page-item <?= $reqPage === $i ? 'active' : '' ?>">
                                    <a
                                        class="page-link"
                                        href="?page=<?= $i ?>"
                                    >
                                        <?= $i ?>
                                    </a>
                                </li>

                            <?php endfor; ?>

                            <li class="page-item <?= $reqPage >= $totalPages ? 'disabled' : '' ?>">
                                <a
                                    class="page-link"
                                    href="?page=<?= $reqPage + 1 ?>"
                                >
                                    Next
                                </a>
                            </li>

                        </ul>

                    </nav>

                <?php endif; ?>

            </div>
        </div>

    </div>

</body>
</html>
