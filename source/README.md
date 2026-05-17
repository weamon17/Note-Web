# WeaNote

> A full-featured, collaborative note-taking web application built with PHP 8.2 and vanilla JavaScript.

[![Live Demo](https://img.shields.io/badge/Live%20Demo-Railway-7C3AED?style=for-the-badge&logo=railway)](https://note-web-production.up.railway.app)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap)](https://getbootstrap.com)

---

## Features

| Category | Features |
|----------|----------|
| **Auth** | Register, login, logout, email verification, OTP password reset |
| **Notes** | Create, edit, delete, auto-save, pin, search, filter by label |
| **Organization** | Color-coded labels, drag-to-reorder, list/grid view toggle |
| **Security** | Per-note password lock, CSRF protection, bcrypt hashing |
| **Collaboration** | Share notes (read/edit), real-time co-editing via WebSocket |
| **Media** | Attach images to notes (JPEG/PNG/GIF/WebP, max 5 MB) |
| **Offline** | PWA with Service Worker, IndexedDB queue, background sync |
| **UI/UX** | Dark/light mode, responsive design, custom note colors, font size |

---

## Tech Stack

```
Backend     PHP 8.2 — custom MVC (no framework), PDO, bcrypt
Database    MySQL 5.7+ / MariaDB 10.3+
WebSocket   Ratchet 0.4 — real-time collaboration
Email       PHPMailer 6.x — verification & OTP reset
Frontend    Bootstrap 5.3, Bootstrap Icons, Vanilla JS
PWA         Service Worker + IndexedDB + Web App Manifest
```

---

## Live Demo

**URL:** https://note-web-production.up.railway.app

| Email | Password | Role |
|-------|----------|------|
| `owner@example.com` | `Password123` | Note owner (verified) |
| `editor@example.com` | `Password123` | Shared editor (verified) |
| `viewer@example.com` | `Password123` | Shared viewer (unverified) |

> Locked note password: `Secret123`

---

## Getting Started

### Requirements

- PHP >= 8.0 with extensions: `pdo`, `pdo_mysql`, `mbstring`, `fileinfo`, `gd`
- MySQL 5.7+ or MariaDB 10.3+
- Composer

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/weamon17/Note-Web.git
cd Note-Web/source

# 2. Install dependencies
composer install

# 3. Create the database
mysql -u root -p < database/schema.sql

# 4. Seed demo data
php database/seed.php

# 5. Start the server
php -S localhost:8000 -t public router.php
```

Open **http://localhost:8000**

### Environment Variables (optional)

By default the app runs without any configuration (dev mode).
Copy `.env.example` to `.env` to customize:

```env
APP_ENV=production

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=weanote
DB_USER=root
DB_PASS=

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your@gmail.com
MAIL_FROM_NAME=WeaNote
```

> **Dev mode:** If `MAIL_HOST` is not set, activation links and OTP codes are written to the PHP error log instead of being emailed.

### Real-time Collaboration (WebSocket)

Open a **second terminal** in the `source/` directory:

```bash
php websocket/server.php
```

WebSocket listens on port 8080. The green **Live** dot in the editor toolbar confirms the connection.

---

## Project Structure

```
source/
├── app/
│   ├── Controllers/       MVC controllers (Auth, Note, Label, Profile…)
│   │   └── Api/           AJAX / JSON API controllers
│   ├── Core/              Router, Database, Auth, CSRF, Mailer
│   ├── Models/            User, Note, Label, Share, Preference…
│   └── Views/             PHP templates (layouts, notes, auth, profile…)
├── config/
│   ├── config.php         App constants & dynamic BASE_URL
│   └── database.php       DB connection (reads env vars)
├── database/
│   ├── schema.sql         Full DB schema
│   ├── seed.sql           Demo data
│   └── seed.php           PHP seeder (fresh bcrypt hashes)
├── public/                ← Document root
│   ├── assets/css/        app.css
│   ├── assets/js/         app.js, notes.js, editor.js, offline.js…
│   ├── uploads/           User-uploaded files
│   └── index.php          Front controller
├── websocket/
│   └── server.php         Ratchet WebSocket server
├── Dockerfile             Docker build (Railway deployment)
├── composer.json
└── README.md
```

---

## Deployment (Railway)

The app is deployed via Docker on Railway.app.

```dockerfile
FROM php:8.2-cli
RUN docker-php-ext-install pdo pdo_mysql mbstring gd fileinfo zip
CMD php -S 0.0.0.0:${PORT:-8080} -t public/
```

Set the following environment variables in Railway:
`APP_ENV`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `MAIL_*`

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| White page / 500 error | Set `APP_ENV=development` in `.env` to see errors |
| "Class not found" | Run `composer install` |
| DB connection failed | Check `.env` credentials and ensure MySQL is running |
| Login fails after seeding | Use `php database/seed.php` instead of `seed.sql` |
| Email not received | In dev mode, check PHP error log for the activation link |
| WebSocket dot is red | Run `php websocket/server.php` in a second terminal |
| PWA not installable | Use `http://localhost` (not `127.0.0.1`); requires HTTPS in production |

---

## License

Academic project — Ton Duc Thang University, Web Programming & Applications.
