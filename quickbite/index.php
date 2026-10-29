<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Menu';

$items = db()->query('SELECT * FROM menu_items ORDER BY category, name')->fetchAll();
$categories = array_values(array_unique(array_column($items, 'category')));

$cartQty = cart_items();      // [item id => quantity]
$summary = cart_summary();    // count, total and item list for the cart bar / mini cart
$showCart = !is_admin();

require __DIR__ . '/includes/header.php';
?>

<section class="qb-hero rounded-4 p-4 p-md-5 mb-4">
    <h1 class="fw-bold mb-2">Skip the queue. Eat on time. 🍽️</h1>
    <p class="lead mb-0">Pre-order from the campus canteen, pick a slot and collect with your token.</p>
</section>

<div class="row g-2 mb-3 align-items-center">
    <div class="col-md-5">
        <input type="search" id="menu-search" class="form-control" placeholder="🔍 Search dishes (dosa, biryani, coffee...)">
    </div>
    <div class="col-md-7">
        <div id="category-pills" class="d-flex flex-wrap gap-2">
            <button class="btn btn-sm qb-pill active" data-category="all">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="btn btn-sm qb-pill" data-category="<?= e($cat) ?>"><?= e($cat) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row g-3" id="menu-grid">
    <?php foreach ($items as $it): ?>
        <?php $q = (int) ($cartQty[$it['id']] ?? 0); ?>
        <div class="col-6 col-md-4 col-lg-3 menu-card-wrap"
             data-category="<?= e($it['category']) ?>"
             data-name="<?= e(strtolower($it['name'] . ' ' . $it['description'])) ?>">
            <div class="card qb-card h-100 <?= $it['available'] ? '' : 'sold-out' ?> <?= $q > 0 ? 'in-cart' : '' ?>">
                <div class="card-body d-flex flex-column">
                    <div class="qb-emoji"><?= e($it['emoji']) ?></div>
                    <span class="badge qb-cat align-self-start mb-1"><?= e($it['category']) ?></span>
                    <h6 class="card-title fw-bold mb-1"><?= e($it['name']) ?></h6>
                    <p class="small text-muted flex-grow-1"><?= e($it['description']) ?></p>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="fw-bold fs-5"><?= money($it['price']) ?></span>
                        <?php if (!$it['available']): ?>
                            <span class="badge text-bg-secondary">Sold out</span>
                        <?php elseif ($showCart): ?>
                            <div class="qb-ctrl" data-id="<?= (int) $it['id'] ?>">
                                <button type="button" class="btn btn-sm btn-warning fw-semibold add-btn <?= $q > 0 ? 'd-none' : '' ?>">+ Add</button>
                                <div class="qty-stepper <?= $q > 0 ? '' : 'd-none' ?>">
                                    <button type="button" class="step-dec" aria-label="Decrease quantity">−</button>
                                    <span class="step-qty"><?= $q ?></span>
                                    <button type="button" class="step-inc" aria-label="Increase quantity" <?= $q >= 10 ? 'disabled' : '' ?>>+</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<p id="no-results" class="text-center text-muted py-5 d-none">No dishes match your search.</p>

<?php if ($showCart): ?>
    <!-- Sticky cart bar: always shows how many items are in the cart and the running total -->
    <div id="cart-bar" class="cart-bar <?= $summary['count'] > 0 ? '' : 'd-none' ?>">
        <div class="container d-flex justify-content-between align-items-center gap-2">
            <div>🛒 <strong id="bar-count"><?= (int) $summary['count'] ?></strong> <span id="bar-label"><?= $summary['count'] === 1 ? 'item' : 'items' ?></span>
                &middot; <strong class="bar-total" id="bar-total"><?= e($summary['total']) ?></strong></div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="offcanvas" data-bs-target="#miniCart">View items</button>
                <a href="<?= url('checkout.php') ?>" class="btn btn-sm btn-warning fw-semibold">Checkout →</a>
            </div>
        </div>
    </div>

    <!-- Slide-out mini cart with quantities (can be changed here too) -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="miniCart" aria-labelledby="miniCartLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title fw-bold" id="miniCartLabel">🛒 Your cart</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <ul id="mini-items" class="list-group list-group-flush flex-grow-1"></ul>
            <div class="d-flex justify-content-between fw-bold fs-5 my-3"><span>Total</span><span id="mini-total"><?= e($summary['total']) ?></span></div>
            <a href="<?= url('checkout.php') ?>" class="btn btn-warning fw-semibold w-100">Checkout →</a>
            <a href="<?= url('cart.php') ?>" class="btn btn-outline-secondary w-100 mt-2">Open full cart</a>
        </div>
    </div>

    <script>window.QB_CART = <?= json_encode($summary, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
