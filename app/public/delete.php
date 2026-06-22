<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid entry ID.');
}

$stmt = $db->prepare("SELECT id, entry_date, amount, comment FROM gold_entries WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$entry = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$entry) {
    http_response_code(404);
    exit('Entry not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $stmt = $db->prepare("DELETE FROM gold_entries WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $stmt->close();

    flash('success', 'Entry deleted.');
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Entry — Gold Hoarder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Delete Entry</h1>

    <div class="card">
        <div class="card-body">
            <p>Are you sure you want to delete this entry?</p>
            <table class="table table-bordered mb-3">
                <tr>
                    <th>Date</th>
                    <td><?= e($entry['entry_date']) ?></td>
                </tr>
                <tr>
                    <th>Gold Amount</th>
                    <td><?= e((string)$entry['amount']) ?></td>
                </tr>
                <tr>
                    <th>Comment</th>
                    <td><?= e($entry['comment'] ?? '—') ?></td>
                </tr>
            </table>

            <form method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
