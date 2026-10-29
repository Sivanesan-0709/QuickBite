<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Login';

if (current_user()) {
    redirect(is_admin() ? 'admin/index.php' : 'index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $st = db()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([$email]);
    $u = $st->fetch();

    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
        flash('success', 'Welcome back, ' . $u['name'] . '!');

        if ($u['role'] === 'admin') {
            redirect('admin/index.php');
        }
        // only allow redirecting to our own known pages
        $next = basename($_GET['next'] ?? '');
        $allowed = ['index.php', 'cart.php', 'checkout.php', 'orders.php'];
        redirect(in_array($next, $allowed, true) ? $next : 'index.php');
    }
    $error = 'Wrong email or password.';
}

require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <h3 class="fw-bold mb-3">Welcome back 👋</h3>
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-warning w-100 fw-semibold">Log in</button>
                </form>
                <p class="text-center small mt-3 mb-0">New here? <a href="<?= url('register.php') ?>">Create an account</a></p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
