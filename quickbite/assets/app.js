// QuickBite - client-side JavaScript
// Covers: theme toggle, menu search + category filter, AJAX cart (steppers, cart bar, mini cart),
// form validation, live order status polling, admin auto-refresh.

document.addEventListener('DOMContentLoaded', function () {

    var MAX_QTY = 10;

    // ---------- toast helper ----------
    function showToast(message, isError) {
        var el = document.getElementById('qb-toast');
        if (!el || typeof bootstrap === 'undefined') { return; }
        document.getElementById('qb-toast-body').textContent = message;
        el.classList.toggle('text-bg-danger', !!isError);
        el.classList.toggle('text-bg-dark', !isError);
        bootstrap.Toast.getOrCreateInstance(el, { delay: 2200 }).show();
    }

    // ---------- dark / light theme toggle ----------
    var themeBtn = document.getElementById('theme-toggle');
    function syncThemeButton() {
        if (!themeBtn) { return; }
        var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        themeBtn.textContent = dark ? '☀️' : '🌙';
        themeBtn.title = dark ? 'Switch to light theme' : 'Switch to dark theme';
    }
    if (themeBtn) {
        syncThemeButton();
        themeBtn.addEventListener('click', function () {
            var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', next);
            try { localStorage.setItem('qb-theme', next); } catch (e) {}
            syncThemeButton();
        });
    }

    // ---------- navbar cart badge ----------
    function updateCartBadge(count, pulse) {
        var badge = document.getElementById('cart-badge');
        if (!badge) { return; }
        badge.textContent = count;
        badge.classList.toggle('d-none', count <= 0);
        if (pulse) {
            badge.classList.remove('badge-pulse');
            void badge.offsetWidth; // restart the animation
            badge.classList.add('badge-pulse');
        }
    }

    // ---------- cart on the menu page: cards, cart bar and mini cart ----------
    var grid = document.getElementById('menu-grid');
    var busy = false;

    // Redraws every cart-related part of the page from one summary object
    // summary = { count, total, items: [{id, name, emoji, qty, price, subtotal}] }
    function renderCart(summary, pulse) {
        if (!summary) { return; }
        updateCartBadge(summary.count, pulse);

        var qtyById = {};
        summary.items.forEach(function (it) { qtyById[it.id] = it.qty; });

        // 1) quantity controls on each menu card
        document.querySelectorAll('.qb-ctrl').forEach(function (ctrl) {
            var q = qtyById[ctrl.dataset.id] || 0;
            ctrl.querySelector('.add-btn').classList.toggle('d-none', q > 0);
            ctrl.querySelector('.qty-stepper').classList.toggle('d-none', q === 0);
            ctrl.querySelector('.step-qty').textContent = q;
            ctrl.querySelector('.step-inc').disabled = q >= MAX_QTY;
            var card = ctrl.closest('.qb-card');
            if (card) { card.classList.toggle('in-cart', q > 0); }
        });

        // 2) sticky cart bar
        var bar = document.getElementById('cart-bar');
        if (bar) {
            bar.classList.toggle('d-none', summary.count <= 0);
            document.body.classList.toggle('has-bar', summary.count > 0);
            document.getElementById('bar-count').textContent = summary.count;
            document.getElementById('bar-label').textContent = summary.count === 1 ? 'item' : 'items';
            document.getElementById('bar-total').textContent = summary.total;
        }

        // 3) slide-out mini cart
        var list = document.getElementById('mini-items');
        if (list) {
            list.textContent = '';
            if (!summary.items.length) {
                var empty = document.createElement('li');
                empty.className = 'list-group-item text-muted text-center py-4';
                empty.textContent = 'Your cart is empty.';
                list.appendChild(empty);
            }
            summary.items.forEach(function (it) {
                var li = document.createElement('li');
                li.className = 'list-group-item px-0 mini-row';
                li.dataset.id = it.id;

                var top = document.createElement('div');
                top.className = 'd-flex justify-content-between';
                var name = document.createElement('span');
                name.className = 'mini-name';
                name.textContent = it.emoji + ' ' + it.name;
                var sub = document.createElement('span');
                sub.className = 'fw-semibold';
                sub.textContent = it.subtotal;
                top.appendChild(name);
                top.appendChild(sub);

                var bottom = document.createElement('div');
                bottom.className = 'd-flex justify-content-between align-items-center mt-1';
                var each = document.createElement('small');
                each.className = 'text-muted';
                each.textContent = it.price + ' each';

                var stepper = document.createElement('div');
                stepper.className = 'qty-stepper';
                var dec = document.createElement('button');
                dec.type = 'button'; dec.className = 'step-dec'; dec.textContent = '−';
                dec.setAttribute('aria-label', 'Decrease quantity');
                var qty = document.createElement('span');
                qty.className = 'step-qty'; qty.textContent = it.qty;
                var inc = document.createElement('button');
                inc.type = 'button'; inc.className = 'step-inc'; inc.textContent = '+';
                inc.setAttribute('aria-label', 'Increase quantity');
                inc.disabled = it.qty >= MAX_QTY;
                stepper.appendChild(dec);
                stepper.appendChild(qty);
                stepper.appendChild(inc);

                bottom.appendChild(each);
                bottom.appendChild(stepper);
                li.appendChild(top);
                li.appendChild(bottom);
                list.appendChild(li);
            });
            var miniTotal = document.getElementById('mini-total');
            if (miniTotal) { miniTotal.textContent = summary.total; }
        }
    }

    // Sends add / dec to cart.php and redraws from the answer
    function cartAction(action, id) {
        if (busy || !window.fetch) { return; }
        busy = true;
        var data = new FormData();
        data.append('csrf', window.QB.csrf);
        data.append('action', action);
        data.append('id', id);
        data.append('ajax', '1');
        fetch(window.QB.base + '/cart.php', { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.message) { showToast(res.message, !res.ok); }
                renderCart(res, action === 'add' && res.ok);
            })
            .catch(function () { showToast('Network problem - please try again.', true); })
            .then(function () { busy = false; });
    }

    if (window.QB_CART) {
        renderCart(window.QB_CART, false);
    }

    // one delegated listener handles the card steppers AND the mini-cart steppers
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('.add-btn, .step-inc, .step-dec');
        if (!btn) { return; }
        var holder = btn.closest('[data-id]');
        if (!holder) { return; }
        ev.preventDefault();
        cartAction(btn.classList.contains('step-dec') ? 'dec' : 'add', holder.dataset.id);
    });

    // ---------- menu: search + category filter ----------
    if (grid) {
        var searchBox = document.getElementById('menu-search');
        var pills = document.querySelectorAll('#category-pills .qb-pill');
        var cards = grid.querySelectorAll('.menu-card-wrap');
        var noResults = document.getElementById('no-results');
        var activeCategory = 'all';

        var applyFilters = function () {
            var q = searchBox.value.trim().toLowerCase();
            var shown = 0;
            cards.forEach(function (card) {
                var okCat = activeCategory === 'all' || card.dataset.category === activeCategory;
                var okText = q === '' || card.dataset.name.indexOf(q) !== -1;
                var visible = okCat && okText;
                card.classList.toggle('d-none', !visible);
                if (visible) { shown++; }
            });
            noResults.classList.toggle('d-none', shown > 0);
        };

        searchBox.addEventListener('input', applyFilters);
        pills.forEach(function (pill) {
            pill.addEventListener('click', function () {
                pills.forEach(function (p) { p.classList.remove('active'); });
                pill.classList.add('active');
                activeCategory = pill.dataset.category;
                applyFilters();
            });
        });
    }

    // ---------- register form validation ----------
    var reg = document.getElementById('register-form');
    if (reg) {
        var pw = document.getElementById('password');
        var cf = document.getElementById('confirm');

        var checkMatch = function () {
            var bad = cf.value !== '' && pw.value !== cf.value;
            cf.classList.toggle('is-invalid', bad);
            cf.setCustomValidity(bad ? 'Passwords do not match' : '');
        };
        pw.addEventListener('input', checkMatch);
        cf.addEventListener('input', checkMatch);

        reg.addEventListener('submit', function (ev) {
            checkMatch();
            if (!reg.checkValidity()) {
                ev.preventDefault();
                reg.classList.add('was-validated');
            }
        });
    }

    // ---------- checkout: note character counter ----------
    var note = document.getElementById('note');
    var noteCount = document.getElementById('note-count');
    if (note && noteCount) {
        var upd = function () { noteCount.textContent = note.value.length; };
        note.addEventListener('input', upd);
        upd();
    }

    // ---------- My Orders: live status polling ----------
    if (window.QB_POLL_ORDERS) {
        var lastStatus = {};
        document.querySelectorAll('[data-order-status]').forEach(function (b) {
            lastStatus[b.dataset.orderStatus] = b.textContent.trim();
        });

        setInterval(function () {
            fetch(window.QB.base + '/api_status.php', { credentials: 'same-origin', cache: 'no-store' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data) { return; }
                    Object.keys(data).forEach(function (id) {
                        var badge = document.querySelector('[data-order-status="' + id + '"]');
                        if (!badge || lastStatus[id] === data[id].status) { return; }
                        lastStatus[id] = data[id].status;
                        badge.textContent = data[id].status;
                        badge.className = 'badge fs-6 text-bg-' + data[id].class;
                        var cancel = document.querySelector('[data-cancel-form="' + id + '"]');
                        if (cancel && data[id].status !== 'Pending') { cancel.remove(); }
                        showToast('Order status: ' + data[id].status + (data[id].status === 'Ready' ? ' - come and collect it!' : ''));
                    });
                })
                .catch(function () { /* ignore network blips */ });
        }, 10000);
    }

    // ---------- Admin dashboard: refresh every 30s ----------
    if (window.QB_ADMIN_REFRESH) {
        setInterval(function () {
            if (!document.hidden) { window.location.reload(); }
        }, 30000);
    }
});
