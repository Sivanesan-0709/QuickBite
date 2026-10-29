<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Manage Menu';

require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $name  = trim($_POST['name'] ?? '');
        $cat   = trim($_POST['category'] ?? '');
        $price = $_POST['price'] ?? '';
        $emoji = trim($_POST['emoji'] ?? '') ?: '🍽️';
        $desc  = trim($_POST['description'] ?? '');
        $avail = isset($_POST['available']) ? 1 : 0;

        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Item name is required (max 100 characters).';
        }
        if ($cat === '' || mb_strlen($cat) > 40) {
            $errors[] = 'Category is required (max 40 characters).';
        }
        if (!is_numeric($price) || (float) $price <= 0 || (float) $price > 9999) {
            $errors[] = 'Enter a valid price greater than 0.';
        }
        if (mb_strlen($desc) > 200) {
            $errors[] = 'Description must be 200 characters or fewer.';
        }

        if (!$errors) {
            if ($id > 0) {
                $st = db()->prepare('UPDATE menu_items SET name=?, category=?, price=?, emoji=?, description=?, available=? WHERE id=?');
                $st->execute([$name, $cat, $price, $emoji, $desc, $avail, $id]);
                flash('success', "“$name” updated.");
            } else {
                $st = db()->prepare('INSERT INTO menu_items (name, category, price, emoji, description, available) VALUES (?,?,?,?,?,?)');
                $st->execute([$name, $cat, $price, $emoji, $desc, $avail]);
                flash('success', "“$name” added to the menu.");
            }
            redirect('admin/menu.php');
        }
        // fall through: show the form again with the errors and the entered values
        $editing = ['id' => $id, 'name' => $name, 'category' => $cat, 'price' => $price, 'emoji' => $emoji, 'description' => $desc, 'available' => $avail];
    } elseif ($action === 'toggle' && $id > 0) {
        db()->prepare('UPDATE menu_items SET available = 1 - available WHERE id = ?')->execute([$id]);
        redirect('admin/menu.php');
    } elseif ($action === 'delete' && $id > 0) {
        db()->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$id]);
        flash('info', 'Item deleted. Past orders keep their history.');
        redirect('admin/menu.php');
    }
}

if (!isset($editing)) {
    $editing = ['id' => 0, 'name' => '', 'category' => '', 'price' => '', 'emoji' => '', 'description' => '', 'available' => 1];
    $editId = (int) ($_GET['edit'] ?? 0);
    if ($editId > 0) {
        $st = db()->prepare('SELECT * FROM menu_items WHERE id = ?');
        $st->execute([$editId]);
        $editing = $st->fetch() ?: $editing;
    }
}

$items = db()->query('SELECT * FROM menu_items ORDER BY category, name')->fetchAll();
$cats  = array_values(array_unique(array_column($items, 'category')));

require __DIR__ . '/../includes/header.php';
?>

<h2 class="fw-bold mb-3">🍴 Manage menu</h2>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><?= $editing['id'] ? 'Edit item' : 'Add new item' ?></h5>
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2"><?= e($err) ?></div>
                <?php endforeach; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">

                    <div class="mb-2">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?= e($editing['name']) ?>" required maxlength="100">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" list="cat-list" class="form-control" value="<?= e($editing['category']) ?>" required maxlength="40">
                            <datalist id="cat-list">
                                <?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-5">
                            <label class="form-label">Price (₹)</label>
                            <input type="number" name="price" step="0.50" min="1" class="form-control" value="<?= e($editing['price']) ?>" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Emoji</label>
                        <input type="text" name="emoji" class="form-control" value="<?= e($editing['emoji']) ?>" maxlength="8" placeholder="🍛">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" maxlength="200"><?= e($editing['description']) ?></textarea>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="available" id="avail" <?= $editing['available'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="avail">Available today</label>
                    </div>
                    <button class="btn btn-warning fw-semibold"><?= $editing['id'] ? 'Save changes' : 'Add item' ?></button>
                    <?php if ($editing['id']): ?>
                        <a href="<?= url('admin/menu.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Item</th><th>Category</th><th class="text-end">Price</th><th>Available</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><span class="fs-5 me-1"><?= e($it['emoji']) ?></span><strong><?= e($it['name']) ?></strong></td>
                            <td><span class="badge qb-cat"><?= e($it['category']) ?></span></td>
                            <td class="text-end"><?= money($it['price']) ?></td>
                            <td>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
                                    <button class="btn btn-sm <?= $it['available'] ? 'btn-success' : 'btn-outline-secondary' ?>"><?= $it['available'] ? 'In stock' : 'Sold out' ?></button>
                                </form>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= url('admin/menu.php?edit=' . (int) $it['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this item?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
