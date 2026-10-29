<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Sign up';

if (current_user()) {
    redirect('index.php');
}

$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
        $errors[] = 'Please enter your name (2-80 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $st = db()->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $errors[] = 'That email is already registered. Try logging in.';
        }
    }

    if (!$errors) {
        $st = db()->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "student")');
        $st->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        flash('success', 'Account created! Please log in.');
        redirect('login.php');
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <h3 class="fw-bold mb-3">Create your account</h3>
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2"><?= e($err) ?></div>
                <?php endforeach; ?>
                <form method="post" id="register-form" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Full name</label>
                        <input type="text" name="name" class="form-control" value="<?= e($name) ?>" required minlength="2" maxlength="80">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">College email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required minlength="6">
                        <div class="form-text">At least 6 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm password</label>
                        <input type="password" name="confirm" id="confirm" class="form-control" required>
                        <div class="invalid-feedback">Passwords do not match.</div>
                    </div>
                    <button class="btn btn-warning w-100 fw-semibold">Sign up</button>
                </form>
                <p class="text-center small mt-3 mb-0">Already have an account? <a href="<?= url('login.php') ?>">Log in</a></p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
