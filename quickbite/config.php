<?php
// QuickBite - Campus Canteen Pre-Order System
// Shared configuration + helper functions. Every page includes this first.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- EDIT THESE FOR YOUR HOSTING ----------
// XAMPP (local): leave as is.
// InfinityFree / any host: use the values from your hosting control panel.
const DB_HOST = 'localhost';
const DB_NAME = 'quickbite';
const DB_USER = 'root';
const DB_PASS = '';
// -------------------------------------------------

// Works whether the project sits in the web root or in a sub-folder (e.g. /quickbite)
$docRoot = rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$here    = str_replace('\\', '/', __DIR__);
define('BASE_URL', ($docRoot !== '' && stripos($here, $docRoot) === 0) ? substr($here, strlen($docRoot)) : '');

const STATUSES = ['Pending', 'Preparing', 'Ready', 'Collected', 'Cancelled'];

const PICKUP_SLOTS = [
    'ASAP (about 10 mins)',
    '10:30 AM', '11:00 AM', '11:30 AM',
    '12:30 PM', '12:45 PM', '1:00 PM', '1:15 PM',
    '3:30 PM', '4:00 PM',
];

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            die('Database connection failed. Check the DB_* settings at the top of config.php and make sure you imported database.sql.');
        }
    }
    return $pdo;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function money($amount): string
{
    return '₹' . number_format((float) $amount, 2);
}

// ---------- flash messages ----------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------- auth ----------
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(): void
{
    if (!current_user()) {
        flash('warning', 'Please log in to continue.');
        redirect('login.php?next=' . urlencode(basename($_SERVER['PHP_SELF'])));
    }
}

function require_admin(): void
{
    if (!current_user()) {
        flash('warning', 'Please log in as admin.');
        redirect('login.php');
    }
    if (!is_admin()) {
        http_response_code(403);
        die('403 - Admins only.');
    }
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function check_csrf(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        die('Invalid or expired form. Go back, refresh the page and try again.');
    }
}

// ---------- cart (stored in session as [menu_item_id => qty]) ----------
function cart_items(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(cart_items());
}

/** Returns cart rows joined with live menu data, plus the total. */
function cart_details(): array
{
    $cart = cart_items();
    if (!$cart) {
        return ['rows' => [], 'total' => 0.0];
    }
    $ids = array_map('intval', array_keys($cart));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $st  = db()->prepare("SELECT * FROM menu_items WHERE id IN ($in)");
    $st->execute($ids);
    $rows  = [];
    $total = 0.0;
    foreach ($st->fetchAll() as $item) {
        $qty = (int) $cart[$item['id']];
        $item['qty']      = $qty;
        $item['subtotal'] = $qty * (float) $item['price'];
        $total += $item['subtotal'];
        $rows[] = $item;
    }
    return ['rows' => $rows, 'total' => $total];
}

/** Compact cart snapshot used by the menu page (cart bar, mini cart, card steppers). */
function cart_summary(): array
{
    $d     = cart_details();
    $items = [];
    $count = 0;
    foreach ($d['rows'] as $r) {
        $count  += (int) $r['qty'];
        $items[] = [
            'id'       => (int) $r['id'],
            'name'     => $r['name'],
            'emoji'    => $r['emoji'],
            'qty'      => (int) $r['qty'],
            'price'    => money($r['price']),
            'subtotal' => money($r['subtotal']),
        ];
    }
    return ['count' => $count, 'total' => money($d['total']), 'items' => $items];
}

function status_class(string $status): string
{
    return match ($status) {
        'Pending'   => 'warning',
        'Preparing' => 'info',
        'Ready'     => 'success',
        'Collected' => 'secondary',
        'Cancelled' => 'danger',
        default     => 'light',
    };
}
