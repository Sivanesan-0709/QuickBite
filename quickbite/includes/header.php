<?php
// Included at the top of every page (after config.php).
$pageTitle = $pageTitle ?? 'QuickBite';
$user      = current_user();
$current   = basename($_SERVER['PHP_SELF']);
$inAdmin   = strpos(str_replace('\\', '/', $_SERVER['PHP_SELF']), '/admin/') !== false;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <script>
        // Apply the saved theme before the page paints (dark is the default)
        (function () {
            var t = 'dark';
            try { t = localStorage.getItem('qb-theme') || 'dark'; } catch (e) {}
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | QuickBite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= url('assets/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg qb-nav sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= url('index.php') ?>">🍔 Quick<span>Bite</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <?php if (!$inAdmin || !is_admin()): ?>
                    <li class="nav-item"><a class="nav-link <?= $current === 'index.php' && !$inAdmin ? 'active' : '' ?>" href="<?= url('index.php') ?>">Menu</a></li>
                    <?php if ($user && !is_admin()): ?>
                        <li class="nav-item"><a class="nav-link <?= $current === 'orders.php' ? 'active' : '' ?>" href="<?= url('orders.php') ?>">My Orders</a></li>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (is_admin()): ?>
                    <li class="nav-item"><a class="nav-link <?= $inAdmin && $current === 'index.php' ? 'active' : '' ?>" href="<?= url('admin/index.php') ?>">Orders Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link <?= $current === 'menu.php' ? 'active' : '' ?>" href="<?= url('admin/menu.php') ?>">Manage Menu</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <button id="theme-toggle" type="button" class="btn btn-sm btn-outline-light" aria-label="Switch between dark and light theme">☀️</button>
                </li>
                <?php if (!is_admin()): ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= url('cart.php') ?>">
                            🛒 Cart
                            <span id="cart-badge" class="badge rounded-pill bg-warning text-dark <?= cart_count() ? '' : 'd-none' ?>"><?= cart_count() ?></span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($user): ?>
                    <li class="nav-item"><span class="navbar-text small">Hi, <?= e(explode(' ', $user['name'])[0]) ?><?= is_admin() ? ' (admin)' : '' ?></span></li>
                    <li class="nav-item"><a class="btn btn-sm btn-outline-light" href="<?= url('logout.php') ?>">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('login.php') ?>">Login</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-warning" href="<?= url('register.php') ?>">Sign up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
<?php foreach (get_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($f['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endforeach; ?>
