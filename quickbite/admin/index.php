<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Orders Dashboard';

require_admin();

// Allowed moves in the order workflow
const NEXT_STATUS = [
    'Pending'   => ['Preparing', 'Cancelled'],
    'Preparing' => ['Ready', 'Cancelled'],
    'Ready'     => ['Collected'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $oid    = (int) ($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $st = db()->prepare('SELECT status FROM orders WHERE id = ?');
    $st->execute([$oid]);
    $current = $st->fetchColumn();

    if ($current && in_array($status, NEXT_STATUS[$current] ?? [], true)) {
        db()->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $oid]);
        flash('success', "Order #$oid marked as $status.");
    } else {
        flash('warning', 'That status change is not allowed.');
    }
    $back = $_POST['filter'] ?? '';
    redirect('admin/index.php' . (in_array($back, STATUSES, true) ? '?status=' . urlencode($back) : ''));
}

$filter = $_GET['status'] ?? '';
if (!in_array($filter, STATUSES, true)) {
    $filter = '';
}

// Today's numbers
$stats = db()->query("
    SELECT
        COUNT(*)                                                        AS orders_today,
        COALESCE(SUM(status = 'Pending'), 0)                            AS pending,
        COALESCE(SUM(status IN ('Preparing')), 0)                       AS preparing,
        COALESCE(SUM(status = 'Ready'), 0)                              AS ready,
        COALESCE(SUM(CASE WHEN status <> 'Cancelled' THEN total END),0) AS revenue
    FROM orders WHERE DATE(created_at) = CURDATE()
")->fetch();

$sql = "SELECT o.*, u.name AS customer,
               GROUP_CONCAT(CONCAT(oi.name, ' × ', oi.qty) ORDER BY oi.id SEPARATOR ', ') AS items
        FROM orders o
        JOIN users u ON u.id = o.user_id
        LEFT JOIN order_items oi ON oi.order_id = o.id";
$params = [];
if ($filter !== '') {
    $sql .= ' WHERE o.status = ?';
    $params[] = $filter;
}
$sql .= ' GROUP BY o.id, u.name ORDER BY (o.status IN ("Pending","Preparing","Ready")) DESC, o.created_at DESC LIMIT 100';
$st = db()->prepare($sql);
$st->execute($params);
$orders = $st->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="fw-bold mb-0">📊 Orders dashboard</h2>
    <span class="small text-muted" id="refresh-note">Auto-refreshes every 30 seconds</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card qb-stat"><div class="card-body"><div class="small text-muted">Orders today</div><div class="fs-2 fw-bold"><?= (int) $stats['orders_today'] ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card qb-stat"><div class="card-body"><div class="small text-muted">Pending / Preparing</div><div class="fs-2 fw-bold"><?= (int) $stats['pending'] ?> / <?= (int) $stats['preparing'] ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card qb-stat"><div class="card-body"><div class="small text-muted">Ready for pickup</div><div class="fs-2 fw-bold"><?= (int) $stats['ready'] ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card qb-stat"><div class="card-body"><div class="small text-muted">Revenue today</div><div class="fs-2 fw-bold"><?= money($stats['revenue']) ?></div></div></div></div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?= url('admin/index.php') ?>" class="btn btn-sm qb-pill <?= $filter === '' ? 'active' : '' ?>">All</a>
    <?php foreach (STATUSES as $s): ?>
        <a href="<?= url('admin/index.php?status=' . urlencode($s)) ?>" class="btn btn-sm qb-pill <?= $filter === $s ? 'active' : '' ?>"><?= e($s) ?></a>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Token</th><th>Customer</th><th>Items</th><th>Total</th><th>Pickup</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php if (!$orders): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No orders found.</td></tr>
            <?php endif; ?>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td>
                        <strong><?= e($o['token']) ?></strong>
                        <div class="small text-muted"><?= e(date('d M, h:i A', strtotime($o['created_at']))) ?></div>
                    </td>
                    <td><?= e($o['customer']) ?></td>
                    <td class="small">
                        <?= e($o['items']) ?>
                        <?php if ($o['note']): ?><div class="text-muted fst-italic">“<?= e($o['note']) ?>”</div><?php endif; ?>
                    </td>
                    <td><?= money($o['total']) ?></td>
                    <td class="small"><?= e($o['pickup_time']) ?></td>
                    <td><span class="badge text-bg-<?= status_class($o['status']) ?>"><?= e($o['status']) ?></span></td>
                    <td>
                        <?php foreach (NEXT_STATUS[$o['status']] ?? [] as $next): ?>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                <input type="hidden" name="status" value="<?= e($next) ?>">
                                <input type="hidden" name="filter" value="<?= e($filter) ?>">
                                <button class="btn btn-sm <?= $next === 'Cancelled' ? 'btn-outline-danger' : 'btn-success' ?>"
                                    <?= $next === 'Cancelled' ? "onclick=\"return confirm('Cancel this order?')\"" : '' ?>>
                                    <?= $next === 'Cancelled' ? 'Cancel' : 'Mark ' . e($next) ?>
                                </button>
                            </form>
                        <?php endforeach; ?>
                        <?php if (!isset(NEXT_STATUS[$o['status']])): ?><span class="text-muted small">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>window.QB_ADMIN_REFRESH = true;</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
