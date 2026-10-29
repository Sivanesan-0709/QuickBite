<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'My Orders';

require_login();
if (is_admin()) {
    redirect('admin/index.php');
}
$uid = current_user()['id'];

// Student can cancel their own order only while it is still Pending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $oid = (int) ($_POST['order_id'] ?? 0);
    $st = db()->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status = 'Pending'");
    $st->execute([$oid, $uid]);
    flash($st->rowCount() ? 'info' : 'warning', $st->rowCount() ? 'Order cancelled.' : 'This order can no longer be cancelled.');
    redirect('orders.php');
}

$st = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC');
$st->execute([$uid]);
$orders = $st->fetchAll();

$itemsByOrder = [];
if ($orders) {
    $ids = array_column($orders, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $st  = db()->prepare("SELECT * FROM order_items WHERE order_id IN ($in)");
    $st->execute($ids);
    foreach ($st->fetchAll() as $row) {
        $itemsByOrder[$row['order_id']][] = $row;
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="fw-bold mb-0">📋 My orders</h2>
    <span class="small text-muted" id="live-note">● Live status updates</span>
</div>

<?php if (!$orders): ?>
    <div class="text-center py-5">
        <p class="lead text-muted">You haven't placed any orders yet.</p>
        <a href="<?= url('index.php') ?>" class="btn btn-warning fw-semibold">Order something tasty</a>
    </div>
<?php endif; ?>

<div class="row g-3" id="orders-list">
    <?php foreach ($orders as $o): ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="qb-token-sm"><?= e($o['token']) ?></div>
                            <div class="small text-muted"><?= e(date('d M Y, h:i A', strtotime($o['created_at']))) ?></div>
                        </div>
                        <span class="badge fs-6 text-bg-<?= status_class($o['status']) ?>" data-order-status="<?= (int) $o['id'] ?>"><?= e($o['status']) ?></span>
                    </div>
                    <hr>
                    <ul class="list-unstyled mb-2">
                        <?php foreach ($itemsByOrder[$o['id']] ?? [] as $l): ?>
                            <li class="d-flex justify-content-between"><span><?= e($l['name']) ?> × <?= (int) $l['qty'] ?></span><span><?= money($l['price'] * $l['qty']) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="d-flex justify-content-between fw-bold"><span>Total</span><span><?= money($o['total']) ?></span></div>
                    <div class="small mt-1">Pickup: <?= e($o['pickup_time']) ?></div>
                    <?php if ($o['note']): ?><div class="small text-muted">Note: <?= e($o['note']) ?></div><?php endif; ?>

                    <?php if ($o['status'] === 'Pending'): ?>
                        <form method="post" class="mt-3" onsubmit="return confirm('Cancel this order?')" data-cancel-form="<?= (int) $o['id'] ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Cancel order</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($orders): ?>
<script>window.QB_POLL_ORDERS = true;</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
