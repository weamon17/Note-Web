================================================================================
  WeaNote — Note Management Web Application
  Final Project | Web Programming & Applications
================================================================================

  Live URL  : https://note-web-production.up.railway.app
  Platform  : Railway.app (PHP 8.2 + MySQL)
  Stack     : PHP 8.2 · MySQL · Bootstrap 5 · Ratchet WebSocket · PWA


────────────────────────────────────────────────────────────────────────────────
  DEMO ACCOUNTS  (work on both live site and local)
────────────────────────────────────────────────────────────────────────────────

  Role            Email                  Password
  --------------- ---------------------- -----------
  Owner           owner@example.com      Password123
  Shared Editor   editor@example.com     Password123
  Shared Viewer   viewer@example.com     Password123

  Locked note password: Secret123  (used for the "Private Thoughts" note)

  NOTE: After seeding, if login fails run:  php database/seed.php
        (regenerates bcrypt hashes for the current machine)


────────────────────────────────────────────────────────────────────────────────
  QUICK START — Run locally in 4 steps
────────────────────────────────────────────────────────────────────────────────

  Requirements: PHP >= 8.0, MySQL 5.7+, Composer

  1. Install dependencies
     Open a terminal inside the  source/  folder:
       composer install

  2. Create and seed the database
       mysql -u root -p < database/schema.sql
       php database/seed.php

     Or in phpMyAdmin:
       - Create a database named "weanote"
       - Import  database/schema.sql
       - Then run:  php database/seed.php

  3. (Optional) Configure .env
     The app works out of the box with XAMPP defaults (no .env needed).
     Copy  .env.example  to  .env  if you need custom DB credentials or email.

  4. Start the web server
     Method A — PHP built-in server (easiest):
       php -S localhost:8000 -t public router.php
       Open: http://localhost:8000

     Method B — XAMPP:
       Copy source/ to C:/xampp/htdocs/weanote/
       Open: http://localhost/weanote/public

  For real-time collaboration, also run the WebSocket server
  in a second terminal:
       php websocket/server.php


────────────────────────────────────────────────────────────────────────────────
  TECH STACK
────────────────────────────────────────────────────────────────────────────────

  Backend    PHP 8.2 — custom MVC framework (no Laravel/Symfony), PDO, bcrypt
  Database   MySQL 5.7+ / MariaDB 10.3+
  WebSocket  Ratchet 0.4 — real-time collaborative editing
  Email      PHPMailer 6.x — email verification & OTP password reset
  Frontend   Bootstrap 5.3, Bootstrap Icons, Vanilla JavaScript (no jQuery)
  PWA        Service Worker + IndexedDB offline queue + Web App Manifest


────────────────────────────────────────────────────────────────────────────────
  FEATURES  (28 criteria)
────────────────────────────────────────────────────────────────────────────────

  Authentication
    [1]  User registration (email + display name + password)
    [2]  Email verification via activation link (OTP in dev mode → error log)
    [3]  Login / logout with session management and CSRF protection
    [4]  Password reset via 6-digit OTP email (15-minute expiry)

  Profile & Settings
    [5]  View profile — name, email, verified status, avatar, preferences
    [6]  Edit profile — display name + avatar upload (JPEG/PNG/WebP ≤ 2 MB)
    [7]  Change password (requires current password)
    [8]  Preferences — dark/light theme, font size, default view, note color

  Notes
    [9]  List view — horizontal cards with color accent bar
    [10] Grid view — responsive masonry grid, auto-fill columns
    [11] Create notes — redirects to editor immediately
    [12] Edit notes — rich text editor (bold, italic, lists, headings, code)
    [13] Delete notes — soft-delete to Trash with restore/permanent delete
    [14] Auto-save — 600 ms debounce, live status indicator in toolbar
    [15] Image attachments — drag-and-drop or upload (JPEG/PNG/GIF/WebP ≤ 5 MB)
    [16] Pin notes — pinned notes always appear at the top

  Organization
    [17] Search — live search in navbar, MySQL FULLTEXT index
    [18] Label management — CRUD with color picker, inline rename
    [19] Attach labels to notes — multi-label support, pills on cards
    [20] Filter by label — chip filter bar + sidebar links

  Security & Sharing
    [21] Note password — lock/unlock individual notes
    [22] Change note password — dedicated tab in lock modal
    [23] Share notes — by email, view or edit permission, revoke anytime
    [24] Real-time collaboration — WebSocket (Ratchet), live co-editing

  Quality & Deployment
    [25] UI/UX — Bootstrap 5, dark mode, animations, responsive modals
    [26] Responsive design — tested at 360 / 768 / 1024 / 1440 px
    [27] PWA / Offline — Service Worker, IndexedDB queue, background sync
    [28] Deployment — live on Railway; dynamic BASE_URL; env-var config


────────────────────────────────────────────────────────────────────────────────
  TESTING SPECIFIC FEATURES
────────────────────────────────────────────────────────────────────────────────

  Real-time Collaboration
    1. Start web server + WebSocket server (two terminals)
    2. Browser Tab 1: log in as owner@example.com
    3. Browser Tab 2: log in as editor@example.com
    4. Tab 2 → "Shared with Me" → open "Project Specification"
    5. Type in either tab — the other updates within ~300 ms
    6. Green "Live" dot in the editor toolbar = WebSocket connected

  PWA / Offline Mode
    1. Open app in Chrome, visit a few pages to warm the cache
    2. DevTools (F12) → Network → tick "Offline"
    3. Reload — app shell and cached notes load from Service Worker
    4. Edit a note → toolbar shows "Saved offline"
    5. Untick "Offline" → "Back online, syncing…" → "Sync completed"
    6. Install as PWA: click the install icon in the browser address bar


────────────────────────────────────────────────────────────────────────────────
  TROUBLESHOOTING
────────────────────────────────────────────────────────────────────────────────

  White page / 500 error
    → Set APP_ENV=development in .env to display error details

  "Class not found" error
    → Run:  composer install

  Cannot connect to database
    → Verify MySQL is running and credentials in .env or config/database.php

  Login fails after import
    → Bcrypt hashes are machine-specific. Run:  php database/seed.php

  Email / activation link not received
    → In dev mode (no SMTP set), check the PHP error log for the link:
      XAMPP: C:/xampp/apache/logs/php_error_log
      Built-in server: terminal output

  WebSocket "Live" dot stays grey
    → Make sure  php websocket/server.php  is running in a second terminal
    → Check that port 8080 is not blocked by firewall or antivirus

  PWA not installable
    → Must use http://localhost (not 127.0.0.1) or HTTPS


────────────────────────────────────────────────────────────────────────────────
  PROJECT STRUCTURE
────────────────────────────────────────────────────────────────────────────────

  source/
  ├── app/
  │   ├── Controllers/     Auth, Note, Label, Profile, Preference + Api/
  │   ├── Core/            Router, Database, Auth, CSRF, Mailer, Validator
  │   ├── Models/          User, Note, Label, Share, Preference, NoteImage
  │   └── Views/           PHP templates (auth, notes, labels, profile, layouts)
  ├── config/
  │   ├── config.php       App constants, dynamic BASE_URL (no hardcoding)
  │   └── database.php     PDO connection (reads DB_* env vars)
  ├── database/
  │   ├── schema.sql       Full database schema — import this first
  │   ├── seed.sql         Demo data (SQL)
  │   └── seed.php         Demo data (PHP seeder — preferred)
  ├── public/              ← Document root (point web server here)
  │   ├── assets/css/      app.css
  │   ├── assets/js/       app.js, notes.js, editor.js, offline.js, ...
  │   ├── uploads/         User-uploaded images and avatars
  │   └── index.php        Front controller
  ├── websocket/
  │   └── server.php       Ratchet WebSocket server
  ├── Dockerfile           Docker build for Railway deployment
  ├── composer.json        Dependencies (PHPMailer, Ratchet)
  └── .env.example         Environment variable template

================================================================================
