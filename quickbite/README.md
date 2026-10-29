# QuickBite – Campus Canteen Pre-Order System

Web Programming Mini Project (HTML/CSS · Bootstrap · JavaScript · PHP · MySQL)

Students browse the canteen menu, pre-order meals, pick a pickup slot and get a token (e.g. `QB-4821`).
Canteen staff see incoming orders on an admin dashboard and move them through
**Pending → Preparing → Ready → Collected**. Students see the status update live.

## Features

**Student**
- Register / login (hashed passwords, session based)
- Menu with live search and category filter
- Dark theme by default with a ☀️/🌙 toggle (remembered in the browser)
- Add to cart without page reload (AJAX); each menu card shows a quantity stepper (− 2 +)
- Sticky cart bar on the menu page (items + total) and a slide-out mini cart where quantities can be changed
- Cart page with quantity stepper, remove items, clear cart
- Checkout with pickup time slot and note, unique pickup token
- "My Orders" page with live status updates and cancel option (while Pending)

**Admin** (`admin@quickbite.com` / `admin123` – change it after first login)
- Orders dashboard: today's stats, filter by status, one-click status updates, auto-refresh
- Menu management: add / edit / delete items, mark Sold out / In stock

**Security basics:** prepared statements (PDO), `password_hash`, CSRF tokens on every form,
output escaping, server-side validation, prices always read from the database, role check on admin pages.

## Syllabus mapping

| Unit | Where it is used |
|---|---|
| I – HTML, CSS | All pages, `assets/style.css`, Bootstrap layout and components |
| II – JavaScript | `assets/app.js`: DOM events, filtering, form validation, fetch/AJAX, timers |
| III – PHP + Bootstrap | Sessions, forms, arrays, functions, PDO + MySQL CRUD, Bootstrap grid/cards/navbar |

## Folder structure

```
quickbite/
├── index.php            menu page
├── register.php  login.php  logout.php
├── cart.php  checkout.php  order_success.php
├── orders.php           student order history
├── api_status.php       JSON endpoint for live status
├── config.php           DB settings + helper functions
├── database.sql         tables + sample menu + admin account
├── includes/            header.php, footer.php
├── admin/               index.php (orders), menu.php (menu CRUD)
└── assets/              style.css, app.js
```

## Important before you publish or deploy

- `config.php` in this repository only has the default XAMPP values (`root`, empty password).
  **Never commit your real hosting database password.** Put real values in your server copy only.
- The sample admin account (`admin@quickbite.com` / `admin123`) is for demo use. Change the password
  after the first login on any public server.

## Run locally (XAMPP)

1. Copy the `quickbite` folder into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open `http://localhost/phpmyadmin` → **New** → create a database named `quickbite`.
4. Click the `quickbite` database → **Import** → choose `database.sql` → **Go**.
5. Open `http://localhost/quickbite/`.

`config.php` already uses the XAMPP defaults (user `root`, empty password).

## Deploy online (free – InfinityFree)

1. Sign up at https://www.infinityfree.com and create a hosting account (pick a free subdomain).
2. In the client area open **MySQL Databases** → create a database. Note the **host name**
   (like `sql123.infinityfree.com`), **database name**, **username** and **password**.
3. Open **phpMyAdmin** for that database → **Import** → upload `database.sql`.
4. Edit `config.php` and put your values in `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
5. Upload everything inside the `quickbite` folder into the `htdocs` folder of your account
   (use the online File Manager or FileZilla – FTP details are in the client area).
   `index.php` must end up directly inside `htdocs`.
6. Visit your site, e.g. `http://yourname.infinityfreeapp.com`.

> The first visit can take a minute while the free host sets up. If you see a database error,
> re-check the four values in `config.php`.

## Demo flow for the viva

1. Register a student account → browse menu → search "dosa" → add items → cart → checkout.
2. Show the token page.
3. In another browser/incognito window log in as admin → mark the order Preparing → Ready.
4. Back on the student's **My Orders** page the status changes by itself within ~10 seconds.
5. In **Manage Menu** mark an item Sold out and show it disabled on the menu.

## Database tables

`users` · `menu_items` · `orders` · `order_items` (order lines keep a copy of name and price,
so past orders stay correct even if the menu changes).
