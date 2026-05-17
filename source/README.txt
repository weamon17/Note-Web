╔══════════════════════════════════════════════════════════════════════════════╗
║                NoteFlow – Note Management Application                        ║
║                Final Project – Web Programming & Applications                ║
╚══════════════════════════════════════════════════════════════════════════════╝

VERSION  : 1.0.0
STACK    : PHP 8.x | MySQL/MariaDB | Bootstrap 5 | Ratchet WebSocket | PWA

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PROJECT DESCRIPTION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

NoteFlow is a full-featured, collaborative note management web application.
Users can create, organise, lock, and share notes. It supports real-time
collaboration via WebSocket, works offline as a Progressive Web App (PWA),
and provides a responsive, accessible UI with dark mode support.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TECHNOLOGIES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Backend      : PHP 8.x — custom MVC (no framework), PDO, bcrypt
  Database     : MySQL 5.7+ / MariaDB 10.3+
  WebSocket    : Ratchet 0.4 (cboden/ratchet) — real-time collaboration
  Email        : PHPMailer 6.x — email verification & OTP password reset
  Frontend     : Bootstrap 5.3, Bootstrap Icons, Vanilla JavaScript
  PWA          : Service Worker + IndexedDB offline queue + Web App Manifest

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REQUIREMENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  • PHP >= 8.0  with extensions: pdo, pdo_mysql, mbstring, openssl, fileinfo
  • MySQL 5.7+ or MariaDB 10.3+
  • Composer (https://getcomposer.org)
  • XAMPP (Windows) OR any web server supporting PHP 8

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PROJECT STRUCTURE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  source/
  ├── app/
  │   ├── Controllers/          MVC controllers (Auth, Note, Label, Profile…)
  │   │   └── Api/              AJAX / JSON API controllers
  │   ├── Core/                 Framework core: Router, DB, Auth, CSRF, Mailer
  │   ├── Models/               Database models (User, Note, Label, Share…)
  │   └── Views/                PHP templates
  │       ├── auth/             Login, register, forgot/reset password
  │       ├── layouts/          header.php, sidebar.php, footer.php
  │       ├── notes/            List, editor, trash, search, shared, unlock
  │       ├── labels/           Label manager
  │       ├── profile/          Profile view, edit, change password
  │       └── preferences/      Theme, font, colour preferences
  ├── config/
  │   ├── config.php            App constants & dynamic BASE_URL
  │   └── database.php          DB connection (reads env vars)
  ├── database/
  │   ├── schema.sql            Full DB schema — import first
  │   ├── seed.sql              Demo data with pre-computed bcrypt hashes
  │   ├── seed.php              PHP seeder — generates fresh bcrypt hashes
  │   └── migrations/           Incremental migration scripts (already
  │                             incorporated into schema.sql)
  ├── public/                   ← Web root (point your server here)
  │   ├── assets/
  │   │   ├── css/app.css       All application styles
  │   │   └── js/               app.js, notes.js, editor.js, autosave.js,
  │   │                         collaboration.js, offline.js
  │   ├── uploads/              User file uploads (avatars, note images)
  │   ├── index.php             Front controller — all requests go here
  │   ├── manifest.json         PWA manifest
  │   ├── service-worker.js     PWA service worker (offline support)
  │   └── offline.html          Fallback page when network is unavailable
  ├── vendor/                   Composer packages (restore with composer install)
  ├── websocket/
  │   └── server.php            Ratchet WebSocket server
  ├── router.php                PHP built-in server router
  ├── composer.json             Dependency definitions
  └── .gitignore

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
INSTALLATION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 1 – Install PHP dependencies
  Open a terminal in the source/ directory and run:

    composer install

STEP 2 – Create the database
  In MySQL Workbench, phpMyAdmin, or MySQL CLI:

    mysql -u root -p < database/schema.sql

  OR in phpMyAdmin:
    1. Create database "noteflow"
    2. Select it → Import → choose database/schema.sql

STEP 3 – Seed demo data
  Option A (recommended — generates fresh bcrypt hashes):
    php database/seed.php

  Option B (quick SQL import):
    mysql -u root -p noteflow < database/seed.sql

STEP 4 – Configure the database connection
  Edit  config/database.php  or set environment variables:

    DB_HOST   = 127.0.0.1      (default)
    DB_PORT   = 3306           (default)
    DB_NAME   = noteflow       (default)
    DB_USER   = root           (default)
    DB_PASS   =                (default — empty)

  For XAMPP with default settings no changes are required.

STEP 5 – Configure email (optional — dev mode works without it)
  ── Dev mode (no setup needed) ─────────────────────────────────────────────
  If MAIL_HOST / MAIL_USERNAME are not set, the app logs email content to
  PHP's error log instead of sending. Find activation links and OTP codes in:
    xampp/apache/logs/php_error_log   (XAMPP)
    Terminal output                    (PHP built-in server)

  ── Real SMTP (Mailtrap or production) ─────────────────────────────────────
  Set these environment variables (or add them to php.ini):

    MAIL_HOST         smtp.mailtrap.io
    MAIL_PORT         587
    MAIL_ENCRYPTION   tls
    MAIL_USERNAME     your_mailtrap_username
    MAIL_PASSWORD     your_mailtrap_password
    MAIL_FROM_ADDRESS noreply@noteflow.local
    MAIL_FROM_NAME    NoteFlow

  Mailtrap free account: https://mailtrap.io
  Go to Email Testing → Inboxes → your inbox → SMTP Settings → PHPMailer

  PowerShell (before starting the server):
    $env:MAIL_HOST="smtp.mailtrap.io"
    $env:MAIL_USERNAME="your_username"
    $env:MAIL_PASSWORD="your_password"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
RUNNING THE APPLICATION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

METHOD A – PHP Built-in Server (recommended for demo)
  Open a terminal in the source/ directory:

    php -S localhost:8000 -t public router.php

  Open: http://localhost:8000

METHOD B – XAMPP (Apache)
  Option 1 — Virtual Host (recommended):
    1. Add to C:/xampp/apache/conf/extra/httpd-vhosts.conf:
         <VirtualHost *:80>
             DocumentRoot "C:/xampp/htdocs/NoteFlow/source/public"
             ServerName noteflow.local
             <Directory "C:/xampp/htdocs/NoteFlow/source/public">
                 AllowOverride All
                 Require all granted
             </Directory>
         </VirtualHost>
    2. Add to C:/Windows/System32/drivers/etc/hosts:
         127.0.0.1  noteflow.local
    3. Restart Apache → Open: http://noteflow.local

  Option 2 — Subfolder (no virtual host):
    Copy source/ to C:/xampp/htdocs/noteflow/
    Open: http://localhost/noteflow/public

METHOD C – WebSocket Server (required for real-time collaboration)
  Open a SECOND terminal in the source/ directory:

    php websocket/server.php

  WebSocket listens on port 8080 by default.

  Windows PowerShell (with custom port or token):
    $env:WS_PORT="8080"
    $env:WS_TOKEN_SECRET="replace-with-any-long-random-string"
    php websocket/server.php

  IMPORTANT: The WS_TOKEN_SECRET env var must be the SAME value on both
  the web server and the WebSocket server. If unset, a default is used
  (fine for local demo, but change it in production).

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
COLLABORATION DEMO FLOW
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  1. Start the web server (Method A) AND the WebSocket server (Method C).
  2. Tab 1: Log in as owner@example.com
  3. Tab 1: Open "Project Specification" note
  4. Tab 2: Log in as editor@example.com
  5. Tab 2: Go to "Shared with Me" → open "Project Specification"
  6. Type in either tab — changes appear live in the other tab within ~300 ms
  7. The green "Live" dot in the toolbar confirms the WebSocket connection.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PWA / OFFLINE TESTING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  1. Open the app in Chrome/Edge and browse a few pages (warms the cache).
  2. DevTools (F12) → Application → Service Workers → verify registered.
  3. DevTools → Network → check "Offline" → reload the page.
     → App shell and previously visited notes load from cache.
  4. Edit a note while offline → autosave status shows "Saved offline".
  5. "You are offline" banner appears at the top.
  6. Uncheck "Offline" → "Back online, syncing…" then "Sync completed".
  7. Refresh the page to confirm the server received the offline edits.
  8. Install as PWA: look for the install icon in the browser address bar.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
DEMO ACCOUNTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Email                   Password        Role / Notes
  ──────────────────────  ──────────────  ─────────────────────────────────
  owner@example.com       Password123     Alice – note owner, verified
  editor@example.com      Password123     Bob   – shared editor, verified
  viewer@example.com      Password123     Carol – shared viewer, NOT verified
                                          (demonstrates verification banner)

  Locked note password    Secret123       (for "Private Thoughts" note)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
LOCAL ACCESS URLS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  PHP built-in server  : http://localhost:8000
  XAMPP virtual host   : http://noteflow.local
  XAMPP subfolder      : http://localhost/noteflow/public
  WebSocket server     : ws://localhost:8080  (internal, not opened in browser)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
RUBRIC – 28 CRITERIA STATUS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  #   Criterion                              Status   Notes
  ──  ─────────────────────────────────────  ───────  ─────────────────────────
   1  User registration                      ✓ Done   /register with email,
                                                      display name, password
   2  Account activation (email verify)      ✓ Done   Activation link via email
                                                      (or logged to error_log
                                                      in dev mode)
   3  User login and logout                  ✓ Done   Session-based auth,
                                                      CSRF-protected logout
   4  Password reset                         ✓ Done   6-digit OTP via email,
                                                      15-minute expiry
   5  View profile and avatar                ✓ Done   /profile – shows name,
                                                      email, verified status,
                                                      avatar, preferences
   6  Edit profile and avatar                ✓ Done   /profile/edit – display
                                                      name + JPG/PNG/WebP
                                                      avatar upload (max 2 MB)
   7  Change password                        ✓ Done   /profile/password –
                                                      current + new password
   8  User preferences                       ✓ Done   Theme (light/dark), font
                                                      size, default view,
                                                      note background colour
   9  Display notes in list view             ✓ Done   List toggle – horizontal
                                                      cards with accent bar
  10  Display notes in grid view             ✓ Done   Grid toggle – responsive
                                                      card grid, auto-fill
  11  Create notes                           ✓ Done   "New Note" → auto-creates
                                                      blank note, redirect to
                                                      editor immediately
  12  Update notes                           ✓ Done   Live editing with 600 ms
                                                      debounced autosave
  13  Delete notes                           ✓ Done   Soft-delete to Trash;
                                                      confirmation modal
  14  Auto-save notes                        ✓ Done   autosave.js – 600 ms
                                                      debounce, status
                                                      indicator in toolbar
  15  Attach images to notes                 ✓ Done   Upload button in editor
                                                      toolbar; gallery in note
                                                      (JPEG/PNG/GIF/WebP ≤5 MB)
  16  Pin notes to top                       ✓ Done   Pin/unpin via toolbar or
                                                      card button; pinned notes
                                                      appear first
  17  Search notes                           ✓ Done   Live search in navbar
                                                      (debounced); fulltext
                                                      MySQL index on title+body
  18  Label management                       ✓ Done   CRUD labels with colour
                                                      picker; inline rename;
                                                      sidebar quick-add
  19  Attach labels to notes                 ✓ Done   Label modal in editor;
                                                      label pills on cards
  20  Filter notes based on labels           ✓ Done   Chip filter bar on /notes;
                                                      sidebar label links
  21  Enable and disable password on notes   ✓ Done   Lock/unlock via toolbar;
                                                      disable lock tab in
                                                      lock modal
  22  Password protection / change password  ✓ Done   Change password tab in
      on notes                                        lock modal; separate
                                                      unlock form for locked
                                                      notes
  23  Share and receive notes                ✓ Done   Share modal → email +
                                                      permission (read/edit);
                                                      revoke; notifications
  24  Collaboration and realtime             ✓ Done   Ratchet WebSocket server;
      modification                                    HMAC token auth; room
                                                      broadcast; collab status
                                                      dot in editor toolbar
  25  UI and UX                              ✓ Done   Bootstrap 5, custom CSS
                                                      design tokens, dark mode,
                                                      focus rings, animations,
                                                      empty states, modals
  26  Responsive design                      ✓ Done   Tested at 360/768/1024/
                                                      1440px; mobile sidebar
                                                      off-canvas; toolbar
                                                      scroll on small screens
  27  Offline capabilities (PWA)             ✓ Done   Service Worker v2;
                                                      IndexedDB offline queue;
                                                      background sync;
                                                      installable PWA
  28  Online deployment / docker-compose /   ✓ Done   README with full setup
      readme readiness                                steps; no hardcoded
                                                      localhost; BASE_URL
                                                      computed dynamically;
                                                      env-var configuration

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TROUBLESHOOTING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

PROBLEM : White page / 500 error
FIX     : Enable PHP error display temporarily:
            Edit config/config.php → change APP_ENV to 'development'
            OR add to public/index.php:  ini_set('display_errors','1');

PROBLEM : "Class not found" error
FIX     : Composer autoloader is missing. Run:  composer install

PROBLEM : Database connection failed
FIX     : Check config/database.php credentials.
          Ensure MySQL/MariaDB is running.
          Ensure the "noteflow" database exists (import schema.sql first).

PROBLEM : Login fails after seeding with seed.sql
FIX     : The pre-computed bcrypt hashes in seed.sql were generated on this
          machine. If PHP's bcrypt verification fails, use the PHP seeder:
            php database/seed.php

PROBLEM : Email not received / activation link not working
FIX     : In dev mode (no SMTP configured), the link is written to the
          PHP error log. On XAMPP, check:
            C:/xampp/apache/logs/php_error_log
          Search for "activation" or "reset" to find the link.

PROBLEM : WebSocket connection fails ("Offline" dot stays red)
FIX     : Make sure the WebSocket server is running in a separate terminal:
            php websocket/server.php
          Check firewall/antivirus is not blocking port 8080.
          Check browser console for WebSocket errors.

PROBLEM : PWA not installable / service worker not registering
FIX     : Service workers require HTTPS or localhost. Use http://localhost:8000
          (not http://127.0.0.1:8000) or set up HTTPS.
          Clear browser cache and reload after first visit.

PROBLEM : XAMPP – "Forbidden" when accessing /noteflow/public
FIX     : Add AllowOverride All to your Apache Directory block (see METHOD B
          above) and ensure mod_rewrite is enabled in httpd.conf.

PROBLEM : Uploaded images not showing
FIX     : Check that public/uploads/ directory is writable:
          Windows: right-click → Properties → Security → allow write
          Linux/Mac: chmod -R 775 public/uploads/

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SUBMISSION PACKAGE CHECKLIST
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Before zipping for submission, verify:

  [x] vendor/ folder is included (or note that `composer install` is required)
  [x] database/schema.sql  — database structure
  [x] database/seed.sql    — demo data
  [x] database/seed.php    — PHP seeder (generates fresh bcrypt hashes)
  [x] config/database.php  — no real passwords (uses env vars / defaults)
  [x] public/uploads/      — empty (only .gitkeep files inside)
  [x] README.txt           — this file
  [x] DEMO_CHECKLIST.md    — demo walkthrough guide

To create the submission archive:
  Windows (PowerShell):
    Compress-Archive -Path source -DestinationPath NoteFlow_submission.zip

  OR right-click the source/ folder → Send to → Compressed (zipped) folder

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
NOTE ON BASE URL (NO HARDCODING)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

The application detects its own URL from $_SERVER at runtime:
  config/config.php → define('BASE_URL', ...)

This means it works at:
  http://localhost:8000          (PHP built-in server)
  http://noteflow.local          (XAMPP virtual host)
  http://localhost/noteflow/public  (XAMPP subfolder)
  https://your-domain.com        (any production host)

No code changes required when moving between environments.
