<?php

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): void {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void {
    $_SESSION['flash'][$type][] = $message;
}

function get_flash(): array {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function paginate(int $total, int $per_page, int $current_page): array {
    $per_page = max(1, $per_page);
    $total_pages = max(1, (int)ceil($total / $per_page));
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;

    return [
        'total' => $total,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => $offset,
    ];
}

function sort_url(string $column, string $current_sort, string $current_dir, array $params = []): string {
    $dir = ($current_sort === $column && $current_dir === 'asc') ? 'desc' : 'asc';
    $params['sort'] = $column;
    $params['dir'] = $dir;
    return '?' . http_build_query($params);
}

function sort_icon(string $column, string $current_sort, string $current_dir): string {
    if ($current_sort !== $column) {
        return '<span class="text-muted">&#8693;</span>';
    }
    return $current_dir === 'asc' ? '&#9650;' : '&#9660;';
}
