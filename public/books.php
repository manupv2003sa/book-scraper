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
$books=$repository->findAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Book Management</title>

    <!-- Bootstrap CSS -->
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

                <h5 class="mb-3">
                    Scraped Books (<?= count($books) ?>)
                </h5>

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

                            <?php if (empty($books)): ?>

                                <tr>
                                    <td colspan="6" class="text-center">
                                        No books found.
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($books as $index => $book): ?>

                                    <tr>
                                        <td>
                                            <?= $index+1; ?>
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
            </div>
        </div>

    </div>

</body>
</html>


