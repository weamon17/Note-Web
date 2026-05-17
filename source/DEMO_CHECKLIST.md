# NoteFlow – Demo Checklist (28 Criteria)

> **Before demo:** Start the web server AND (for criterion 24) the WebSocket server.
> ```
> Terminal 1:  php -S localhost:8000 -t public router.php
> Terminal 2:  php websocket/server.php
> ```
> Open: **http://localhost:8000**

---

## Criterion 1 — User Registration

**Goal:** New user can register with email, display name, and password.

- **Account:** Use a new email (e.g. `test@demo.com`)
- **Steps:**
  1. Go to `/register`
  2. Fill in email, display name, password, confirm password
  3. Click **Create Account**
- **Expected:** Flash message "Account created. Check your email to activate."
  Redirect to `/login`

---

## Criterion 2 — Account Activation (Email Verification)

**Goal:** Unverified account shows warning; clicking the activation link verifies it.

- **Account:** `viewer@example.com` / `Password123` (seeded as NOT verified)
- **Steps:**
  1. Log in as `viewer@example.com`
  2. A yellow banner appears: "Your account is not verified."
  3. Click **Resend email** → check PHP error log (dev mode) or Mailtrap for link
  4. Open the activation link in the browser
- **Expected:** Banner disappears; flash "Email verified successfully."

---

## Criterion 3 — User Login and Logout

**Goal:** Authenticated session is created on login and destroyed on logout.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Go to `/login` → enter credentials → click **Sign In**
  2. Redirected to `/notes` (note list)
  3. Click avatar dropdown → **Sign Out**
- **Expected:**
  - Login: redirected to notes, navbar shows user name
  - Logout: redirected to `/login`; accessing `/notes` redirects back to login

---

## Criterion 4 — Password Reset

**Goal:** User can reset password via 6-digit OTP sent to email.

- **Account:** Any registered email (e.g. `owner@example.com`)
- **Steps:**
  1. Go to `/login` → click **Forgot?**
  2. Enter email → click **Send Reset OTP**
  3. Find the 6-digit code in PHP error log or Mailtrap
  4. Go to `/reset-password` → enter email, OTP code, new password
  5. Click **Reset Password**
  6. Log in with the new password
- **Expected:** Flash "Password reset successfully. Please log in."

---

## Criterion 5 — View Profile and Avatar

**Goal:** Logged-in user can view their profile with avatar, name, email, status.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Click avatar in top-right navbar → **Profile & Settings**
  2. Profile page at `/profile` shows:
     - Avatar (initials circle if no photo)
     - Display name, email
     - Verified badge (green)
     - Account creation date
     - Quick action buttons
- **Expected:** All fields populated; verified badge shows for owner/editor

---

## Criterion 6 — Edit Profile and Avatar

**Goal:** User can update display name and upload a profile avatar image.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Go to `/profile` → click **Edit Profile**
  2. Change Display Name to "Alice Demo"
  3. Upload a JPG/PNG image as avatar (< 2 MB)
  4. Click **Save Changes**
- **Expected:**
  - Flash "Profile updated."
  - New name and avatar visible in the navbar top-right
  - Avatar appears as a circular thumbnail

---

## Criterion 7 — Change Password

**Goal:** Logged-in user can change account password.

- **Account:** `editor@example.com` / `Password123`
- **Steps:**
  1. Go to `/profile` → click **Change Password**
  2. Enter Current Password: `Password123`
  3. Enter New Password: `NewPass456!`
  4. Confirm: `NewPass456!`
  5. Click **Update Password**
  6. Log out → log back in with `NewPass456!`
- **Expected:** Flash "Password changed successfully."; new password works.

---

## Criterion 8 — User Preferences

**Goal:** User can change theme, font size, default view, and note background colour.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Sidebar → **Preferences** (or `/preferences`)
  2. Switch **Theme** to Dark → page turns dark immediately on Save
  3. Change **Font Size** to Large
  4. Change **Default View** to List
  5. Change **Note Background Color** (pick any colour)
  6. Click **Save Preferences**
- **Expected:**
  - `[data-bs-theme="dark"]` applied to `<html>`
  - Font size class applied globally
  - Notes list uses selected default view on next page load

---

## Criterion 9 — Display Notes in List View

**Goal:** Notes render in a horizontal list layout with title, preview, and footer.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Go to `/notes`
  2. Click the **list** icon (☰) in the top-right view toggle
- **Expected:**
  - Cards switch to horizontal rows
  - Each row shows left-accent bar, title, preview, date, action buttons
  - "Active" state highlighted on the list button

---

## Criterion 10 — Display Notes in Grid View

**Goal:** Notes render in a responsive card grid layout.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Go to `/notes`
  2. Click the **grid** icon (⊞) in the view toggle
- **Expected:**
  - Cards arranged in responsive multi-column grid
  - Grid layout persists after page reload (saved to localStorage)

---

## Criterion 11 — Create Notes

**Goal:** User can create a new note from the New Note button.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Click **New Note** (sidebar or navbar)
  2. Editor opens with a blank "Untitled" note
  3. Type a title: "My Test Note"
  4. Type some content
- **Expected:**
  - Redirect to `/notes/{id}` immediately
  - New note appears in the list when navigating back to `/notes`

---

## Criterion 12 — Update Notes

**Goal:** Editing a note updates its content.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Open any note (e.g. "Welcome to NoteFlow")
  2. Edit the title or content
  3. Wait 600 ms without typing
- **Expected:**
  - Autosave status shows "Saving…" then "All changes saved"
  - Changes visible after refreshing the page

---

## Criterion 13 — Delete Notes

**Goal:** User can delete a note (soft-delete to Trash); restored or purged from Trash.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. On the notes list, click the trash icon on a note card
  2. Confirm in the Bootstrap modal → "Move to Trash"
  3. Note disappears from list
  4. Go to `/trash` → note is there
  5. Click **Restore** to bring it back, OR **Delete forever** to purge
- **Expected:**
  - Note moves to Trash (soft-delete)
  - Restore brings it back to the main list

---

## Criterion 14 — Auto-save Notes

**Goal:** Notes save automatically without the user pressing Save.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Open any note
  2. Start typing in the title or content field
  3. Observe the autosave status indicator in the toolbar
- **Expected:**
  - Shows cloud-check icon + "All changes saved"
  - While saving: shows spinner + "Saving…"
  - No Save button exists — saving is fully automatic

---

## Criterion 15 — Attach Images to Notes

**Goal:** User can upload images that are displayed in the note editor.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Open any note (must be owner)
  2. Click the **image** icon (🖼) in the toolbar
  3. Select a JPEG or PNG file (< 5 MB)
- **Expected:**
  - Thumbnail gallery appears below the editor
  - Each thumbnail has a ✕ button to delete
  - Clicking the image opens it full-size in a new tab

---

## Criterion 16 — Pin Notes to Top

**Goal:** Pinned notes appear at the top of the list with a visual indicator.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. On the notes list, click the pin icon on a note card (or inside the editor)
  2. The note moves to the top of the list
  3. Pin icon turns filled / primary colour
- **Expected:**
  - "Pinned" badge appears on the card
  - Note stays at top even after page reload
  - Click pin again to unpin

---

## Criterion 17 — Search Notes

**Goal:** Live search finds notes by title and content without pressing Enter.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Click the search bar in the top navbar (or the inline search on `/notes`)
  2. Type "meeting" — results appear within ~300 ms
  3. Clear the search — results disappear, notes list returns
- **Expected:**
  - Results update without page reload
  - Both title and content are searched
  - "N results for 'keyword'" count shown

---

## Criterion 18 — Label Management

**Goal:** User can create, rename, colour, and delete labels.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Sidebar → **Manage Labels** (gear icon) or `/labels`
  2. Create a new label "Urgent" with a red colour (#dc2626)
  3. Click the pencil icon to rename "Urgent" → "High Priority"
  4. Click the trash icon → confirm deletion
- **Expected:**
  - New label appears in the sidebar list
  - Rename updates inline without page reload
  - Deleted label is removed from sidebar and notes

---

## Criterion 19 — Attach Labels to Notes

**Goal:** User can attach labels to a note via the editor.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Open any note you own
  2. Click the **tag** icon (🏷) in the toolbar → Label modal opens
  3. Click on a label to attach (check appears)
  4. Click the label again to detach
  5. Close modal → label pills appear in the note card on the list
- **Expected:**
  - Colour-coded label pills visible in the note editor
  - Pills also appear on the note card in the list view

---

## Criterion 20 — Filter Notes Based on Labels

**Goal:** Clicking a label filter shows only notes with that label.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Go to `/notes` — label filter chips appear below the search bar
  2. Click the **"Work"** chip
  3. Only notes tagged "Work" are shown
  4. Click **"All"** chip to reset
  5. Alternatively: click "Work" in the sidebar → `/labels/1/notes`
- **Expected:**
  - Notes without the selected label are hidden
  - Active chip is highlighted in the label's colour

---

## Criterion 21 — Enable and Disable Password on Notes

**Goal:** Note owner can lock a note with a password and later disable the lock.

- **Account:** `owner@example.com` / `Password123`
- **Steps:**
  1. Open any unlocked note
  2. Click the **lock** icon in the toolbar → Lock modal opens
  3. Enter password `Test1234` and confirm → click **Lock Note**
  4. Navigate away → clicking the note shows the unlock form
  5. Re-open the note → lock modal → **Disable** tab
  6. Enter `Test1234` → click **Disable Lock**
- **Expected:**
  - Locked: amber left-border and lock badge on card; unlock form before editing
  - Unlocked: lock removed, content accessible directly

---

## Criterion 22 — Password Protection / Change Password on Notes

**Goal:** Note lock password can be changed; unlock session is required to change it.

- **Account:** `owner@example.com` / `Password123`
- **Demo note:** "Private Thoughts" (already locked with password `Secret123`)
- **Steps:**
  1. Click "Private Thoughts" → unlock form appears
  2. Enter `Secret123` → click **Unlock**
  3. Note opens; lock icon is filled (🔒)
  4. Click lock icon → **Change Password** tab
  5. Enter Current: `Secret123`, New: `NewSecret456`, Confirm: `NewSecret456`
  6. Click **Change Password**
- **Expected:**
  - Flash "Note password changed."
  - Old password `Secret123` no longer works; new password `NewSecret456` does

---

## Criterion 23 — Share and Receive Notes

**Goal:** Note owner can share with another user; recipient receives a notification.

- **Account (owner):** `owner@example.com`  
- **Account (recipient):** `editor@example.com`
- **Steps:**
  1. Log in as `owner@example.com`
  2. Open any note → click the **share** icon → Share modal
  3. Enter `editor@example.com`, set permission to **Can edit** → click **Share**
  4. Open second browser / incognito tab → log in as `editor@example.com`
  5. Click the 🔔 bell icon → notification: "Alice Owner shared X with you"
  6. Go to **Shared with Me** → see the shared note
  7. As owner: open Share modal → click **Revoke** next to Bob
- **Expected:**
  - Note appears in Bob's "Shared with Me" list
  - Permission badge shows "Can edit" or "View only"
  - Revoking removes the note from Bob's shared list

---

## Criterion 24 — Collaboration and Realtime Modification

**Goal:** Two users editing a shared note see each other's changes in real time.

- **Prerequisite:** WebSocket server running (`php websocket/server.php`)
- **Tab 1:** `owner@example.com`  
- **Tab 2:** `editor@example.com`
- **Steps:**
  1. Tab 1: Open **"Project Specification"** (note 4)
  2. Confirm green **"Live"** dot appears in the editor toolbar
  3. Tab 2: Go to **Shared with Me** → open **"Project Specification"**
  4. Confirm green "Live" dot in Tab 2 as well
  5. Tab 1: Type some text in the content area
  6. Tab 2: Observe changes appearing within ~300 ms — no page reload
  7. Tab 2: Type text — appears in Tab 1 in real time
  8. Collaboration user count updates in the toolbar
- **Expected:**
  - Changes sync instantly between both tabs
  - Green dot = WebSocket connected; if WebSocket is down, falls back to autosave

---

## Criterion 25 — UI and UX

**Goal:** Application has a polished, modern UI with consistent design language.

- **Account:** Any
- **Check the following:**
  - Dark mode toggle (Preferences) — entire app theme switches
  - Flash messages slide in from bottom-right; auto-dismiss after 6 s
  - Empty states (no notes, no labels, empty trash) show friendly icons + text
  - Delete actions require confirmation modals (never immediate)
  - Focus rings visible on all interactive elements (keyboard navigation)
  - Smooth hover transitions on note cards (lift + shadow)
  - Lock badge (amber) and pinned badge on note cards
  - Permission badges (edit / view-only) on shared notes
- **Expected:** Clean, consistent Bootstrap 5 design with custom CSS polish

---

## Criterion 26 — Responsive Design

**Goal:** Layout adapts correctly from mobile (360px) to desktop (1440px+).

- **Account:** Any
- **Steps using DevTools (F12) → Device Toolbar:**
  1. Set to **375px** (iPhone SE):
     - Sidebar hidden; hamburger (☰) button opens slide-in mobile sidebar
     - Note grid switches to 1-column layout
     - Toolbar buttons remain visible (horizontal scroll if needed)
  2. Set to **768px** (iPad):
     - 2-column note grid
     - Mobile sidebar still used (desktop sidebar at ≥992px)
  3. Set to **1024px**:
     - Desktop sidebar visible; 3-column grid
  4. Set to **1440px**:
     - Wide layout; 4-column grid
- **Expected:** No horizontal overflow, no overlapping elements at any size

---

## Criterion 27 — Offline Capabilities (PWA)

**Goal:** App loads and allows editing offline; changes sync when back online.

- **Account:** `owner@example.com`
- **Steps:**
  1. Visit the app in Chrome/Edge; browse to `/notes` and open a note
  2. F12 → **Application** → Service Workers → confirm "noteflow-v2" registered
  3. F12 → **Network** → check **"Offline"**
  4. Reload the page → app shell loads from cache (no network error)
  5. Open a note → content visible from cache
  6. Edit the note → autosave shows **"Saved offline"**
  7. **"You are offline"** yellow banner visible at top
  8. Uncheck "Offline" in DevTools
  9. **"Back online, syncing…"** banner → **"Sync completed"** banner
  10. Hard-refresh the page → edits are on the server
  11. To install as PWA: look for install icon in browser address bar → **Add to Home Screen**
- **Expected:** Full offline-first experience with sync on reconnect

---

## Criterion 28 — Online Deployment / README Readiness

**Goal:** Project can be set up on a fresh machine following only the README.

- **Check the following:**
  - `README.txt` covers all installation steps (composer install, schema, seed, server)
  - No hardcoded `localhost:8000` in PHP source (`BASE_URL` is dynamic)
  - `config/database.php` reads credentials from env vars (no hardcoded password)
  - `composer.json` declares all dependencies
  - `.gitignore` excludes vendor/, uploads/, and logs
  - `public/` is clearly identified as the web root
  - `database/schema.sql` creates the complete schema in one import
  - `database/seed.sql` / `seed.php` provides reproducible demo data
  - WebSocket server start command is documented
  - PWA testing steps are documented
  - Demo accounts are clearly listed

---

## Quick Demo Order (Suggested 15-minute flow)

| Step | Action                                               | Criterion |
|------|------------------------------------------------------|-----------|
| 1    | Register new account                                 | 1         |
| 2    | Show unverified banner with viewer@                  | 2         |
| 3    | Login / logout cycle                                 | 3         |
| 4    | View profile → edit avatar                           | 5, 6      |
| 5    | Change preferences (dark mode, font size)            | 8         |
| 6    | Create new note                                      | 11        |
| 7    | Edit + watch autosave indicator                      | 12, 14    |
| 8    | Upload image to note                                 | 15        |
| 9    | Pin note → verify it moves to top                    | 16        |
| 10   | Lock note → navigate away → unlock                   | 21        |
| 11   | Change note password                                 | 22        |
| 12   | Search "meeting" in navbar                           | 17        |
| 13   | Create label, attach to note, filter by label        | 18, 19, 20|
| 14   | Share with editor → show notification                | 23        |
| 15   | Open in two tabs → real-time collab                  | 24        |
| 16   | Toggle grid ↔ list view                              | 9, 10     |
| 17   | Delete note → restore from Trash                     | 13        |
| 18   | Resize to 375px → mobile sidebar                     | 26        |
| 19   | Go offline in DevTools → edit → sync                 | 27        |
| 20   | Show README, describe deployment steps               | 28        |
| 21   | Show profile, change password                        | 5–7       |
| 22   | Password reset demo                                  | 4         |
| 23   | Describe UI polish (dark mode, focus, animations)    | 25        |
