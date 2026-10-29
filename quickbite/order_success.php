<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Order placed';

require_login();

$id = (int) ($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$st->execute([$id, current_user()['id']]);
$order = $st->fetch();
if (!$order) {
    flash('danger', 'Order not found.');
    redirect('orders.php');
}

$st = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$st->execute([$id]);
$lines = $st->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 text-center">
            <div class="card-body p-4 p-md-5">
                <div class="display-3">🎉</div>
                <h3 class="fw-bold">Order placed!</h3>
                <p class="text-muted">Show this token at the counter</p>

                <div class="qb-token my-3"><?= e($order['token']) ?></div>

                <p class="mb-1">Pickup: <strong><?= e($order['pickup_time']) ?></strong></p>
                <p class="mb-3">Status: <span class="badge text-bg-<?= status_class($order['status']) ?>"><?= e($order['status']) ?></span></p>

                <ul class="list-group list-group-flush text-start mb-3">
                    <?php foreach ($lines as $l): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= e($l['name']) ?> × <?= (int) $l['qty'] ?></span>
                            <span><?= money($l['price'] * $l['qty']) ?></span>
                        </li>
                    <?php endforeach; ?>
                    <li class="list-group-item d-flex justify-content-between fw-bold">
                        <span>Total (pay at counter)</span><span><?= money($order['total']) ?></span>
                    </li>
                </ul>

                <a href="<?= url('orders.php') ?>" class="btn btn-warning fw-semibold">Track my orders</a>
                <a href="<?= url('index.php') ?>" class="btn btn-outline-secondary">Order more</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
