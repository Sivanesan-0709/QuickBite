<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Your Cart';

if (is_admin()) {
    redirect('admin/index.php');
}

const MAX_QTY = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);
    $isAjax = isset($_POST['ajax']);
    $msg    = '';
    $type   = 'success';
    $ok     = true;

    if ($action === 'add' && $id > 0) {
        $st = db()->prepare('SELECT name, available FROM menu_items WHERE id = ?');
        $st->execute([$id]);
        $item = $st->fetch();
        if (!$item || !$item['available']) {
            $ok   = false;
            $type = 'danger';
            $msg  = 'Sorry, that item is not available right now.';
        } elseif (($_SESSION['cart'][$id] ?? 0) >= MAX_QTY) {
            $ok   = false;
            $type = 'warning';
            $msg  = 'Maximum ' . MAX_QTY . ' per item.';
        } else {
            $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
            $msg = $item['name'] . ' added to cart';
        }
    } elseif ($action === 'dec' && $id > 0) {
        $q = ($_SESSION['cart'][$id] ?? 0) - 1;
        if ($q <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id] = $q;
        }
    } elseif ($action === 'update' && $id > 0) {
        $qty = (int) ($_POST['qty'] ?? 1);
        if ($qty <= 0) {
            unset($_SESSION['cart'][$id]);
        } elseif (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = min(MAX_QTY, $qty);
        }
    } elseif ($action === 'remove' && $id > 0) {
        unset($_SESSION['cart'][$id]);
        $msg  = 'Item removed from cart.';
        $type = 'info';
    } elseif ($action === 'clear') {
        unset($_SESSION['cart']);
        $msg  = 'Cart cleared.';
        $type = 'info';
    }

    if ($isAjax) {
        if (!$ok) {
            http_response_code(422);
        }
        header('Content-Type: application/json');
        echo json_encode(array_merge(
            ['ok' => $ok, 'message' => $msg, 'id' => $id, 'qty' => (int) ($_SESSION['cart'][$id] ?? 0)],
            cart_summary()
        ));
        exit;
    }

    if ($msg !== '') {
        flash($type, $msg);
    }
    // "add" comes from the menu page, everything else from the cart page
    redirect($action === 'add' ? 'index.php' : 'cart.php');
}

$cart = cart_details();

// Drop items that were deleted from the menu since they were added
$validIds = array_column($cart['rows'], 'id');
foreach (array_keys(cart_items()) as $cid) {
    if (!in_array($cid, $validIds)) {
        unset($_SESSION['cart'][$cid]);
    }
}

require __DIR__ . '/includes/header.php';
?>

<h2 class="fw-bold mb-3">🛒 Your cart</h2>

<?php if (!$cart['rows']): ?>
    <div class="text-center py-5">
        <div class="display-1">🍽️</div>
        <p class="lead text-muted">Your cart is empty.</p>
        <a href="<?= url('index.php') ?>" class="btn btn-warning fw-semibold">Browse the menu</a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Item</th><th class="text-end">Price</th><th class="text-center" style="width:140px">Quantity</th><th class="text-end">Subtotal</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cart['rows'] as $r): ?>
                            <tr class="<?= $r['available'] ? '' : 'table-danger' ?>">
                                <td>
                                    <span class="fs-4 me-1"><?= e($r['emoji']) ?></span>
                                    <strong><?= e($r['name']) ?></strong>
                                    <?php if (!$r['available']): ?><span class="badge text-bg-danger ms-1">Sold out</span><?php endif; ?>
                                </td>
                                <td class="text-end"><?= money($r['price']) ?></td>
                                <td class="text-center">
                                    <form method="post" class="d-inline-block">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <div class="qty-stepper">
                                            <button type="submit" name="qty" value="<?= (int) $r['qty'] - 1 ?>" aria-label="Decrease quantity">−</button>
                                            <span class="step-qty"><?= (int) $r['qty'] ?></span>
                                            <button type="submit" name="qty" value="<?= (int) $r['qty'] + 1 ?>" aria-label="Increase quantity" <?= $r['qty'] >= MAX_QTY ? 'disabled' : '' ?>>+</button>
                                        </div>
                                    </form>
                                </td>
                                <td class="text-end fw-semibold"><?= money($r['subtotal']) ?></td>
                                <td class="text-end">
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Remove">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <form method="post" class="mt-2" onsubmit="return confirm('Clear the whole cart?')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear">
                <button class="btn btn-link text-danger btn-sm">Clear cart</button>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <h5 class="fw-bold">Order summary</h5>
                    <div class="d-flex justify-content-between"><span>Items</span><span><?= cart_count() ?></span></div>
                    <hr>
                    <div class="d-flex justify-content-between fs-5 fw-bold"><span>Total</span><span><?= money($cart['total']) ?></span></div>
                    <a href="<?= url('checkout.php') ?>" class="btn btn-warning w-100 fw-semibold mt-3">Proceed to checkout →</a>
                    <a href="<?= url('index.php') ?>" class="btn btn-outline-secondary w-100 mt-2">Add more items</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
