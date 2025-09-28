# Project Overview — Blade Admin & User Dashboards

This project delivers two custom dashboards built on top of Laravel 12 Blade views:

- **User area** under `/user` with authentication, verification flow, financial overview, withdrawals, violations, and a realtime-like support chat.
- **Admin area** under `/admin` with rich CRUD forms for users, accounts, transactions, withdrawals, fraud claims, documents, and chat replies.

Filament panels and Livewire components have been removed in favour of first-party Blade controllers, services, and JavaScript enhancements. The codebase now relies solely on Laravel, Vite, and vanilla JS for interactive behaviour.

## Quick Start

1. **Requirements**: PHP 8.2+, Composer, Node.js (for Vite), SQLite (default DB).
2. **Install dependencies**:
   - PHP packages: `composer install`
   - Frontend packages: `npm install`
3. **Environment**:
   - Copy `.env.example` to `.env`
   - Set (already defaulted):
     - `APP_URL=http://127.0.0.1:8001`
     - `DB_CONNECTION=sqlite`
     - `DB_DATABASE=database/database.sqlite`
     - `FILESYSTEM_DISK=public`
4. **Bootstrap application**:
   - `touch database/database.sqlite`
   - `php artisan key:generate`
   - `php artisan migrate --seed`
   - `php artisan storage:link`
5. **Run the stack**:
   - `php artisan serve --host=127.0.0.1 --port=8001`
   - `npm run dev` (or `npm run build` for production)

### Default Credentials

| Role        | Email               | Password  |
|-------------|---------------------|-----------|
| Root Admin  | `root@system.com`   | `password`|
| Admin       | `admin@system.com`  | `password`|
| Demo Client | `client@demo.com`   | `password`|

## Routing & Entry Points

All Blade routes are toggled by `config('integration.theme_routes')` (enabled by default).

- `/user/login`, `/user/register`, `/user/verify` – user auth & verification.
- `/user` (named `user.dashboard`) – main user dashboard rendered by `App\Http\Controllers\Client\DashboardController`.
- `/user/withdraw`, `/user/transactions` – financial actions handled by dedicated controllers.
- `/user/support/messages` & `/user/support` – AJAX chat endpoints provided by `App\Http\Controllers\Client\SupportController` and `App\Services\SupportChatService`.
- `/admin/login` – admin login screen.
- `/admin/dashboard` – Blade admin console powered by `App\Http\Controllers\Admin\AdminDashboardController`.
- `/admin/dashboard/support/*` – admin chat endpoints served by `App\Http\Controllers\Admin\SupportMessageController`.

The legacy `/dashboard` route now redirects authenticated users to either the admin or user dashboard based on the `is_admin` flag.

## Key Components

### Controllers & Services

- `App\Http\Controllers\Client\DashboardController` delegates data loading to `App\Services\ClientDashboardService`.
- `App\Services\SupportChatService` centralises chat queries, formatting, and thread listings for both user and admin modals.
- `App\Http\Controllers\Admin\AdminDashboardController` exposes CRUD endpoints for accounts, transactions, withdrawals, fraud claims, and documents using standard form submissions.
- `App\Http\Controllers\Auth\ClientLoginController` & `AdminLoginController` contain customised redirects for the Blade dashboards.

### Frontend Assets

- `resources/personal-acc/css/style.css` – main theme stylesheet (compiled via Vite).
- `resources/personal-acc/js/app.js` – includes support chat polling, modal toggles, dashboard interactions, and general UI behaviour.
- `resources/js/personal-acc-blade.js` – user dashboard helpers (crypto toggles, loaders, password reveal).

### Views

- User layout & pages live under `resources/views/user` with shared partials in `resources/views/client`.
- Admin layout resides in `resources/views/layouts/admin.blade.php`, with the main dashboard at `resources/views/admin/dashboard.blade.php`.
- Shared base layout `resources/views/layouts/app.blade.php` injects CSRF meta tags and Vite bundles.

## Support Chat Flow

1. Users open the modal (`resources/views/user/dashboard.blade.php` partial) which fetches messages via AJAX.
2. Frontend logic in `resources/personal-acc/js/app.js` polls for updates every 5 seconds while the modal is visible and submits new messages without full page reloads.
3. Admins interact with threads and messages from their dashboard modal, backed by the same service layer.
4. Messages are stored in `support_messages` table via the `App\Models\SupportMessage` model and optionally mirrored to Telegram through `App\Services\TelegramService`.

## Admin Dashboard Highlights

- Inline editing for user details, accounts, transactions, withdrawals, documents, and fraud claims.
- Context-aware validation bags (`$errors->account`, `$errors->transaction`, etc.) keep forms scoped.
- Utility formatting closures defined inline within the Blade view to maintain readability while avoiding bulky helper classes.

## Testing & Tooling

- Feature tests live under `tests/Feature`, already covering admin dashboard flows and user auth/verification.
- Run the suite with `php artisan test`.
- Frontend build: `npm run build` (outputs to `public/build`).

## Housekeeping Notes

- Filament packages and Livewire components have been fully removed from `composer.json` and the source tree.
- `bootstrap/providers.php` now registers only `AppServiceProvider`.
- Any legacy references to `/client` or Filament routes should be migrated to the Blade equivalents under `/user` and `/admin`.

