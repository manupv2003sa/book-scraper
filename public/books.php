<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Repositories\BookRepository;
use App\Database\DatabaseConnection;
use App\Repositories\CategoryRepository;

$db = new DatabaseConnection(
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT'],
    $_ENV['DB_DATABASE'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

$pdo = $db->getConnection();


$categoryRepository = new CategoryRepository($pdo);
$categories = $categoryRepository->findAll();

$repository = new BookRepository($pdo);

$search = trim($_GET['search'] ?? '');

$categoryIds = [];

if (isset($_GET['category']) && is_array($_GET['category'])) {
    foreach ($_GET['category'] as $id) {
        $validId = filter_var($id, FILTER_VALIDATE_INT);

        if ($validId !== false && $validId > 0) {
            $categoryIds[] = $validId;
        }
    }
}

if (!empty($categoryIds)) {
    $filterType = 'category';
    $filterValue = $categoryIds;
    $search = '';
} elseif ($search !== '') {
    $filterType = 'search';
    $filterValue = $search;
} else {
    $filterType = 'all';
    $filterValue = null;
}

$limit = 20;

$reqPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);

if ($reqPage === false || $reqPage === null || $reqPage < 1) {
    $reqPage = 1;
}

$countAll = $repository->countBooks($filterType, $filterValue);

$totalPages = (int) ceil($countAll / $limit);

if ($totalPages === 0) {
    $paginatedContent = [];
} else {

    if ($reqPage > $totalPages) {
        header("Location: books.php?page=" . $totalPages);
        exit;
    }


    $offset = ($reqPage - 1) * $limit;

    $paginatedContent = $repository->findBooks(
        $filterType,
        $filterValue,
        $limit,
        $offset
    );
}


$pageTitle = 'All Books';

ob_start();
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
        rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        /* Dropdown appearance */
        .dropdown-menu {
            min-width: 180px;
            max-height: 600px;
            overflow-y: auto;
            color: #212529;
        }

        /* Category names should not be bold */
        .dropdown-menu .form-check-label {
            font-weight: normal;
            color: #212529;
            cursor: pointer;
        }

        /* Make checkboxes more visible */
        .dropdown-menu .form-check-input {
            width: 16px;
            height: 16px;
            border: 1px solid #6c757d;
            cursor: pointer;
        }

        /* Keep table width consistent */
        .card-body {
            height: 650px;
            overflow-y: auto;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container mt-5">

        <h2>Book Management</h2>
        <p>Manage all scraped books here.</p>

        <div class="card shadow-sm">
            <div class="card-body">

                <form method="GET" action="books.php" class="mb-3">
                    <div class="input-group">
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search books by title..."
                            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

                        <button type="submit" class="btn btn-primary">
                            Search
                        </button>
                    </div>
                </form>

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Book Title</th>
                                <th>Price</th>
                                <th>Rating</th>
                                <th>Availability</th>
                                <th>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-dark fw-bold"
                                        data-bs-toggle="modal"
                                        data-bs-target="#categoryModal">
                                        Category  <svg xmlns="http://www.w3.org/2000/svg"
             width="18"
             height="18"
             viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="2"
             stroke-linecap="round"
             stroke-linejoin="round"
             style="vertical-align: middle; margin-left: 4px;">

            <path d="m13 13.5 2-2.5-2-2.5" />
            <path d="m21 21-4.3-4.3" />
            <path d="M9 8.5 7 11l2 2.5" />
            <circle cx="11" cy="11" r="8" />

        </svg> <i class="bi bi-funnel"></i>
                                    </button>
                                </th>
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
                                                class="btn btn-primary btn-sm">
                                                View
                                            </a>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>


                    <!-- Category Filter Modal -->
                    <div class="modal fade" id="categoryModal" tabindex="-1"
                        aria-labelledby="categoryModalLabel" aria-hidden="true">

                        <div class="modal-dialog modal-dialog-centered modal-lg">

                            <div class="modal-content">

                                <!-- Modal Header -->
                                <div class="modal-header">
                                    <h5 class="modal-title" id="categoryModalLabel">
                                        Filter by Category
                                    </h5>

                                    <button type="button" class="btn-close"
                                        data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>

                                <!-- Modal Body -->
                                <div class="modal-body">

                                    <form method="GET" action="books.php" id="categoryFilterForm">

                                        <p class="text-muted">
                                            Select one or more categories.
                                        </p>

                                        <div class="row">

                                            <?php foreach ($categories as $category): ?>

                                                <div class="col-md-6 mb-2">
                                                    <div class="form-check">

                                                        <input
                                                            class="form-check-input"
                                                            type="checkbox"
                                                            name="category[]"
                                                            value="<?= (int) $category['id'] ?>"
                                                            id="category_<?= (int) $category['id'] ?>"
                                                            <?= in_array((int) $category['id'], $categoryIds ?? [], true) ? 'checked' : '' ?>>

                                                        <label
                                                            class="form-check-label"
                                                            for="category_<?= (int) $category['id'] ?>">

                                                            <?= htmlspecialchars($category['name']) ?>

                                                        </label>

                                                    </div>
                                                </div>

                                            <?php endforeach; ?>

                                        </div>

                                    </form>

                                </div>

                                <!-- Modal Footer -->
                                <div class="modal-footer">

                                    <button type="button"
                                        class="btn btn-outline-secondary"
                                        id="clearCategories">
                                        Clear All
                                    </button>

                                    <button type="button"
                                        class="btn btn-secondary"
                                        data-bs-dismiss="modal">
                                        Close
                                    </button>

                                    <button type="submit"
                                        form="categoryFilterForm"
                                        class="btn btn-primary">
                                        Apply Filter
                                    </button>

                                </div>

                            </div>
                        </div>
                    </div>


                </div>

                <script>
                    const clearButton = document.getElementById('clearCategories');

                    clearButton.addEventListener('click', function () {

                        const checkboxes = document.querySelectorAll(
                            '#categoryFilterForm input[name="category[]"]'
                        );

                        checkboxes.forEach(function (checkbox) {
                            checkbox.checked = false;
                        });

                    });
                </script>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>

                    <nav aria-label="Book pagination">

                        <ul class="pagination justify-content-center">

                            <li class="page-item <?= $reqPage <= 1 ? 'disabled' : '' ?>">
                                <a
                                    class="page-link"
                                    href="?page=<?= $reqPage - 1 ?>">
                                    Previous
                                </a>
                            </li>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                                <li class="page-item <?= $reqPage === $i ? 'active' : '' ?>">
                                    <a
                                        class="page-link"
                                        href="?page=<?= $i ?>">
                                        <?= $i ?>
                                    </a>
                                </li>

                            <?php endfor; ?>

                            <li class="page-item <?= $reqPage >= $totalPages ? 'disabled' : '' ?>">
                                <a
                                    class="page-link"
                                    href="?page=<?= $reqPage + 1 ?>">
                                    Next
                                </a>
                            </li>

                        </ul>

                    </nav>

                <?php endif; ?>

            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php

$content = ob_get_clean();

require_once __DIR__ . '/templates/baseLayout.php';

?>