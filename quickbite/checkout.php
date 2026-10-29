<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Checkout';

require_login();
if (is_admin()) {
    redirect('admin/index.php');
}

$cart = cart_details();
if (!$cart['rows']) {
    flash('info', 'Your cart is empty.');
    redirect('cart.php');
}

$errors = [];
$slot = PICKUP_SLOTS[0];
$note = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $slot = $_POST['slot'] ?? '';
    $note = trim($_POST['note'] ?? '');

    if (!in_array($slot, PICKUP_SLOTS, true)) {
        $errors[] = 'Please choose a valid pickup time.';
    }
    if (mb_strlen($note) > 200) {
        $errors[] = 'Note must be 200 characters or fewer.';
    }
    foreach ($cart['rows'] as $r) {
        if (!$r['available']) {
            $errors[] = $r['name'] . ' is sold out. Remove it from your cart to continue.';
        }
    }

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            // Unique, easy-to-read pickup token like QB-4821
            do {
                $token = 'QB-' . random_int(1000, 9999);
                $chk = $pdo->prepare('SELECT 1 FROM orders WHERE token = ?');
                $chk->execute([$token]);
            } while ($chk->fetch());

            // Prices come from the database (cart_details), never from the browser
            $ins = $pdo->prepare('INSERT INTO orders (user_id, token, total, pickup_time, note) VALUES (?, ?, ?, ?, ?)');
            $ins->execute([current_user()['id'], $token, $cart['total'], $slot, $note]);
            $orderId = (int) $pdo->lastInsertId();

            $line = $pdo->prepare('INSERT INTO order_items (order_id, item_id, name, price, qty) VALUES (?, ?, ?, ?, ?)');
            foreach ($cart['rows'] as $r) {
                $line->execute([$orderId, $r['id'], $r['name'], $r['price'], $r['qty']]);
            }

            $pdo->commit();
            unset($_SESSION['cart']);
            redirect('order_success.php?id=' . $orderId);
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Could not place the order. Please try again.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<h2 class="fw-bold mb-3">Checkout</h2>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Pickup details</h5>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Pickup time</label>
                        <select name="slot" class="form-select" required>
                            <?php foreach (PICKUP_SLOTS as $s): ?>
                                <option value="<?= e($s) ?>" <?= $s === $slot ? 'selected' : '' ?>><?= e($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note for the canteen <span class="text-muted">(optional)</span></label>
                        <textarea name="note" id="note" class="form-control" rows="3" maxlength="200" placeholder="Less spicy, no onion..."><?= e($note) ?></textarea>
                        <div class="form-text"><span id="note-count">0</span>/200</div>
                    </div>
                    <div class="alert alert-light border small">💵 Pay at the canteen counter when you collect your order. Show your token.</div>
                    <button class="btn btn-warning btn-lg w-100 fw-semibold">Place order · <?= money($cart['total']) ?></button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <h5 class="fw-bold">Your order</h5>
                <ul class="list-group list-group-flush">
                    <?php foreach ($cart['rows'] as $r): ?>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><?= e($r['emoji']) ?> <?= e($r['name']) ?> × <?= (int) $r['qty'] ?></span>
                            <span><?= money($r['subtotal']) ?></span>
                        </li>
                    <?php endforeach; ?>
                    <li class="list-group-item d-flex justify-content-between px-0 fw-bold fs-5">
                        <span>Total</span><span><?= money($cart['total']) ?></span>
                    </li>
                </ul>
                <a href="<?= url('cart.php') ?>" class="small">← Edit cart</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
