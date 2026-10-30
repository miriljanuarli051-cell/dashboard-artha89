<?php
declare(strict_types=1);

define('APP_NAME', 'Artha Delapan Sembilan');
define('BASE_URL', '/monitoring-pembelian');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'monitoring_pembelian');
define('DB_USER', 'root');
define('DB_PASS', ''); // Sesuaikan jika MySQL Anda memakai password.

/*
 * Untuk XAMPP lokal, lokasi ini berada di dalam proyek agar mudah dicoba.
 * Untuk penggunaan kantor, pindahkan folder receipts ke luar htdocs,
 * lalu ubah path ini ke lokasi tersebut.
 */
define('RECEIPT_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'receipts');
define('MAX_RECEIPT_BYTES', 5 * 1024 * 1024);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secureCookie,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Periksa config.php dan pastikan MySQL berjalan.');
}

if (!is_dir(RECEIPT_DIR)) {
    @mkdir(RECEIPT_DIR, 0750, true);
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(float|string|null $amount): string {
    return 'Rp' . number_format((float)$amount, 0, ',', '.');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Sesi formulir tidak valid. Muat ulang halaman dan coba kembali.');
    }
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!current_user()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if ((current_user()['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Anda tidak memiliki izin untuk membuka halaman ini.');
    }
}

function audit(PDO $pdo, string $action, ?int $transactionId = null, string $details = ''): void {
    $user = current_user();
    $stmt = $pdo->prepare('INSERT INTO audit_logs (actor_id, transaction_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $user['id'] ?? null,
        $transactionId,
        $action,
        $details,
        substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45)
    ]);
}

function redirect(string $path): never {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function layout_start(string $title): void {
    $user = current_user();
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . ' - ' . e(APP_NAME) . '</title>';
    $stylePath = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'style.css';
    $styleVersion = is_file($stylePath) ? (string)filemtime($stylePath) : '1';
    echo '<link rel="stylesheet" href="' . BASE_URL . '/assets/style.css?v=' . e($styleVersion) . '"></head><body>';
    if ($user) {
        $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $logoPath = __DIR__ . DIRECTORY_SEPARATOR . 'img_logo_artha89.png';
        $logoVersion = is_file($logoPath) ? (string)filemtime($logoPath) : '1';
        echo '<div class="app-shell"><aside class="sidebar">';
        echo '<a class="brand" href="' . BASE_URL . '/index.php"><img class="brand-logo" src="' . BASE_URL . '/img_logo_artha89.png?v=' . e($logoVersion) . '" alt="Logo Artha 89"><span>' . e(APP_NAME) . '</span></a>';
        echo '<div class="sidebar-caption">MENU UTAMA</div>';
        echo '<nav class="sidebar-nav" aria-label="Navigasi utama">';
        echo '<a class="sidebar-link' . ($currentPage === 'index.php' ? ' active' : '') . '" href="' . BASE_URL . '/index.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1z"/></svg><span>Dashboard</span></a>';
        echo '<a class="sidebar-link' . (in_array($currentPage, ['transactions.php', 'transaction_create.php', 'transaction_view.php'], true) ? ' active' : '') . '" href="' . BASE_URL . '/transactions.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4h12v17H4V4h4"/><path d="M8 2h8v4H8zM8 11h8M8 15h8"/></svg><span>Transaksi</span></a>';
        if ($user['role'] === 'admin') {
            echo '<a class="sidebar-link' . ($currentPage === 'users.php' ? ' active' : '') . '" href="' . BASE_URL . '/users.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2zM17 4a4 4 0 0 1 0 8M18 14a6 6 0 0 1 4 5v2h-4"/></svg><span>Pengguna</span></a>';
            echo '<a class="sidebar-link' . ($currentPage === 'audit.php' ? ' active' : '') . '" href="' . BASE_URL . '/audit.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span>Audit Log</span></a>';
        }
        echo '</nav><a class="sidebar-link sidebar-logout" href="' . BASE_URL . '/logout.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/></svg><span>Keluar</span></a>';
        echo '<div class="sidebar-user"><span class="sidebar-avatar" aria-hidden="true">' . e(strtoupper(substr($user['name'], 0, 1))) . '</span><span class="sidebar-user-details">' . e($user['name']) . '<span>' . e($user['role']) . '</span></span></div>';
        echo '</aside><div class="app-main"><main class="container">';
    } else {
        echo '<main class="container">';
    }
}

function layout_end(): void {
    if (current_user()) {
        echo '</main><footer class="footer">Internal kantor · ' . date('Y') . '</footer></div></div></body></html>';
    } else {
        echo '</main><footer class="footer">Internal kantor · ' . date('Y') . '</footer></body></html>';
    }
}

function flash(string $message, string $type = 'info'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function show_flash(): void {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        echo '<div class="alert ' . e($f['type']) . '">' . e($f['message']) . '</div>';
        unset($_SESSION['flash']);
    }
}
