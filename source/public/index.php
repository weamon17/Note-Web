<?php
declare(strict_types=1);

// ─── Bootstrap ───────────────────────────────────────────────────────────────
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

// ─── Session ─────────────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
session_name(SESSION_NAME);
session_start();

// ─── CSRF token (ensure token exists in session) ─────────────────────────────
\App\Core\CSRF::generate();

// ─── Routes ──────────────────────────────────────────────────────────────────
$router = new \App\Core\Router();

// Auth
$router->get( '/login',                 'AuthController@loginForm');
$router->post('/login',                 'AuthController@login');
$router->get( '/register',              'AuthController@registerForm');
$router->post('/register',              'AuthController@register');
$router->post('/logout',                'AuthController@logout');
$router->get( '/verify-email',          'AuthController@verifyEmail');
$router->get( '/resend-verification',   'AuthController@resendVerification');
$router->post('/resend-verification',   'AuthController@resendVerification');
$router->get( '/forgot-password',       'AuthController@forgotForm');
$router->post('/forgot-password',       'AuthController@forgotPassword');
$router->get( '/reset-password',        'AuthController@resetForm');
$router->post('/reset-password',        'AuthController@resetPassword');

// Notes — list & create
$router->get( '/',                      'NoteController@index');
$router->get( '/notes',                 'NoteController@index');
$router->get( '/notes/create',          'NoteController@create');
$router->post('/notes/create',          'NoteController@store');

// Notes — single note actions (order matters: specific before generic)
$router->get( '/notes/{id}/unlock',     'NoteController@unlockForm');
$router->post('/notes/{id}/unlock',     'NoteController@unlock');
$router->post('/notes/{id}/pin',        'NoteController@togglePin');
$router->post('/notes/{id}/lock',       'NoteController@toggleLock');
$router->get( '/notes/{id}/shares',     'ShareController@index');
$router->post('/notes/{id}/share',      'ShareController@store');
$router->post('/notes/{id}/unshare',    'ShareController@destroy');
$router->post('/notes/{id}/restore',    'NoteController@restore');
$router->post('/notes/{id}/purge',      'NoteController@purge');
$router->post('/notes/{id}/delete',     'NoteController@delete');
$router->post('/notes/{id}/image',      'NoteController@uploadImage');
$router->post('/notes/{id}/label',      'NoteController@attachLabel');
$router->post('/notes/{id}/unlabel',    'NoteController@detachLabel');
$router->get( '/notes/{id}',            'NoteController@show');
$router->post('/notes/{id}',            'NoteController@update');

// Note images
$router->post('/images/{imageId}/delete', 'NoteController@deleteImage');
$router->post('/images/{imageId}/move',   'NoteController@moveImage');

// Shared notes
$router->get( '/shared',                'NoteController@shared');
$router->get( '/shared-with-me',        'NoteController@shared');

// Trash
$router->get( '/trash',                 'NoteController@trash');

// Search (no button, live search)
$router->get( '/search',                'NoteController@search');

// Labels
$router->get( '/labels',                'LabelController@index');
$router->post('/labels',                'LabelController@store');
$router->post('/labels/{id}/update',    'LabelController@update');
$router->post('/labels/{id}/delete',    'LabelController@delete');
$router->get( '/labels/{id}/notes',     'LabelController@notes');

// Profile
$router->get( '/profile',              'ProfileController@index');
$router->get( '/profile/edit',         'ProfileController@edit');
$router->get( '/profile/password',     'ProfileController@changePasswordForm');
$router->post('/profile',              'ProfileController@update');
$router->post('/profile/password',     'ProfileController@changePassword');
$router->post('/profile/avatar',       'ProfileController@uploadAvatar');
$router->post('/profile/preferences',  'ProfileController@preferences');

// Preferences
$router->get( '/preferences',          'PreferenceController@index');
$router->post('/preferences',          'PreferenceController@update');

// ─── API (AJAX) ───────────────────────────────────────────────────────────────
$router->post('/api/notes/{id}/autosave',  'Api/NoteApiController@autosave');
$router->get( '/api/notes/{id}/history',   'Api/NoteApiController@history');
$router->get( '/api/notes/search',         'Api/NoteApiController@search');
$router->get( '/api/notes/offline',        'Api/NoteApiController@offline');
$router->post('/api/labels',               'Api/LabelApiController@store');
$router->post('/api/labels/{id}/delete',   'Api/LabelApiController@delete');
$router->get( '/api/notifications',        'Api/NotificationApiController@index');
$router->post('/api/notifications/read-all', 'Api/NotificationApiController@markAllRead');
$router->post('/api/notifications/{id}/read', 'Api/NotificationApiController@markRead');
$router->post('/api/offline/sync',         'Api/OfflineApiController@sync');
$router->get( '/api/collaboration-token',  'Api/CollaborationApiController@token');
$router->post('/api/preferences',          'Api/PreferenceApiController@update');
$router->post('/api/notes/reorder',        'NoteController@reorder');

// ─── Dispatch ─────────────────────────────────────────────────────────────────
$router->dispatch();
