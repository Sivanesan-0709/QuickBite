</main>

<footer class="qb-footer text-center py-3 mt-4">
    <small>QuickBite &copy; <?= date('Y') ?> &middot; Campus Canteen Pre-Order System &middot; Web Programming Mini Project</small>
</footer>

<div class="toast-container position-fixed bottom-0 end-0 p-3 mb-5">
    <div id="qb-toast" class="toast align-items-center text-bg-dark border-0" role="status">
        <div class="d-flex">
            <div class="toast-body" id="qb-toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.QB = { base: <?= json_encode(BASE_URL) ?>, csrf: <?= json_encode(csrf_token()) ?> };</script>
<script src="<?= url('assets/app.js') ?>"></script>
</body>
</html>
