<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid entry ID.');
}

$stmt = $db->prepare("SELECT id, entry_date, amount, comment FROM goldhoarder_gold_entries WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$entry = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$entry) {
    http_response_code(404);
    exit('Entry not found.');
}

$errors = [];
$entry_date = $entry['entry_date'];
$amount = (string)$entry['amount'];
$comment = $entry['comment'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $entry_date = trim($_POST['entry_date'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if ($entry_date === '') {
        $errors[] = 'Date is required.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $entry_date) || !strtotime($entry_date)) {
        $errors[] = 'Date must be a valid date.';
    }

    if ($amount === '') {
        $errors[] = 'Gold amount is required.';
    } elseif (!preg_match('/^-?\d+$/', $amount)) {
        $errors[] = 'Gold amount must be an integer.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM goldhoarder_gold_entries WHERE user_id = ? AND entry_date = ? AND id != ?");
        $stmt->bind_param('isi', $user_id, $entry_date, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'An entry already exists for this date.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $comment_val = $comment !== '' ? $comment : null;
        $amount_int = (int)$amount;
        $stmt = $db->prepare("UPDATE goldhoarder_gold_entries SET entry_date = ?, amount = ?, comment = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param('sisii', $entry_date, $amount_int, $comment_val, $id, $user_id);
        $stmt->execute();
        $stmt->close();

        flash('success', 'Entry updated successfully.');
        redirect('index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Entry — Gold Hoarder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Edit Entry</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="card">
        <div class="card-body">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="mb-3">
                <label for="entry_date" class="form-label">Date</label>
                <input type="date" id="entry_date" name="entry_date" class="form-control" value="<?= e($entry_date) ?>" required>
            </div>
            <div class="mb-3">
                <label for="amount" class="form-label">Gold Amount</label>
                <input type="number" id="amount" name="amount" class="form-control" value="<?= e((string)$amount) ?>" required>
            </div>
            <div class="mb-3">
                <label for="comment" class="form-label">Comment <span class="text-muted">(optional)</span></label>
                <textarea id="comment" name="comment" class="form-control" rows="2"><?= e($comment) ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
