<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$allowed_sorts = ['entry_date', 'amount', 'comment'];
$sort = in_array($_GET['sort'] ?? '', $allowed_sorts) ? $_GET['sort'] : 'entry_date';
$dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = max(5, min(100, (int)($_GET['per_page'] ?? 20)));

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$amount_min = $_GET['amount_min'] ?? '';
$amount_max = $_GET['amount_max'] ?? '';
$comment_filter = $_GET['comment'] ?? '';

$where = 'WHERE user_id = ?';
$params = [$user_id];
$types = 'i';

if ($date_from !== '') {
    $where .= ' AND entry_date >= ?';
    $params[] = $date_from;
    $types .= 's';
}
if ($date_to !== '') {
    $where .= ' AND entry_date <= ?';
    $params[] = $date_to;
    $types .= 's';
}
if ($amount_min !== '') {
    $where .= ' AND amount >= ?';
    $params[] = (int)$amount_min;
    $types .= 'i';
}
if ($amount_max !== '') {
    $where .= ' AND amount <= ?';
    $params[] = (int)$amount_max;
    $types .= 'i';
}
if ($comment_filter !== '') {
    $where .= ' AND comment LIKE ?';
    $params[] = '%' . $comment_filter . '%';
    $types .= 's';
}

$count_stmt = $db->prepare("SELECT COUNT(*) FROM goldhoarder_gold_entries $where");
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_row()[0];
$count_stmt->close();

$pagination = paginate($total, $per_page, $page);

$query = "SELECT id, entry_date, amount, comment FROM goldhoarder_gold_entries $where ORDER BY $sort $dir LIMIT ? OFFSET ?";
$params[] = $pagination['per_page'];
$params[] = $pagination['offset'];
$types .= 'ii';

$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$filter_params = array_filter([
    'date_from' => $date_from,
    'date_to' => $date_to,
    'amount_min' => $amount_min,
    'amount_max' => $amount_max,
    'comment' => $comment_filter,
    'per_page' => $per_page !== 20 ? $per_page : null,
], fn($v) => $v !== '' && $v !== null);

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gold Hoarder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Gold Hoarder</h1>
        <a href="add.php" class="btn btn-primary">+ Add Entry</a>
    </div>

    <?php foreach ($flash as $type => $messages): ?>
        <?php foreach ($messages as $msg): ?>
            <div class="alert alert-<?= e($type) ?> alert-dismissible fade show">
                <?= e($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="card mb-3">
        <div class="card-header" role="button" data-bs-toggle="collapse" data-bs-target="#filterPanel">
            <strong>Filters</strong>
            <?php if (!empty($filter_params)): ?>
                <span class="badge bg-primary ms-2"><?= count($filter_params) ?> active</span>
            <?php endif; ?>
        </div>
        <div class="collapse <?= !empty($filter_params) ? 'show' : '' ?>" id="filterPanel">
            <div class="card-body">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Date from</label>
                        <input type="date" name="date_from" class="form-control" value="<?= e($date_from) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date to</label>
                        <input type="date" name="date_to" class="form-control" value="<?= e($date_to) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Amount min</label>
                        <input type="number" name="amount_min" class="form-control" value="<?= e($amount_min) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Amount max</label>
                        <input type="number" name="amount_max" class="form-control" value="<?= e($amount_max) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Comment</label>
                        <input type="text" name="comment" class="form-control" value="<?= e($comment_filter) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">Apply</button>
                    </div>
                    <?php if (!empty($filter_params)): ?>
                        <div class="col-12">
                            <a href="index.php" class="btn btn-sm btn-outline-secondary">Clear filters</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <?php if (empty($entries) && $total === 0): ?>
        <div class="alert alert-info">No entries yet. <a href="add.php">Add your first gold entry!</a></div>
    <?php elseif (empty($entries)): ?>
        <div class="alert alert-info">No entries match your filters.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>
                            <a href="<?= e(sort_url('entry_date', $sort, $dir, $filter_params)) ?>" class="text-decoration-none">
                                Date <?= sort_icon('entry_date', $sort, $dir) ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= e(sort_url('amount', $sort, $dir, $filter_params)) ?>" class="text-decoration-none">
                                Gold Amount <?= sort_icon('amount', $sort, $dir) ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= e(sort_url('comment', $sort, $dir, $filter_params)) ?>" class="text-decoration-none">
                                Comment <?= sort_icon('comment', $sort, $dir) ?>
                            </a>
                        </th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?= e($entry['entry_date']) ?></td>
                            <td class="<?= $entry['amount'] < 0 ? 'text-danger' : '' ?>"><?= e((string)$entry['amount']) ?></td>
                            <td><?= e($entry['comment'] ?? '') ?></td>
                            <td class="text-end">
                                <a href="edit.php?id=<?= (int)$entry['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <a href="delete.php?id=<?= (int)$entry['id'] ?>" class="btn btn-sm btn-outline-danger">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php
                    $page_params = array_merge($filter_params, ['sort' => $sort, 'dir' => $dir]);
                    ?>
                    <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($page_params, ['page' => $pagination['current_page'] - 1])) ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($page_params, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($page_params, ['page' => $pagination['current_page'] + 1])) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

        <div class="text-muted text-center mb-3">
            Showing <?= $pagination['offset'] + 1 ?>–<?= min($pagination['offset'] + $pagination['per_page'], $pagination['total']) ?>
            of <?= $pagination['total'] ?> entries
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
