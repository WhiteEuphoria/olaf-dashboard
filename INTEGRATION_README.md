# Интеграция кастомной темы в Laravel

Эта сборка добавляет кастомную верстку (из архива `personal-acc.zip`) в Laravel‑проект `dash_ui`:

- Ассеты скопированы в: `public/personal-acc/{css,js,img,fonts,favicon.ico}`
- Общие layout’ы: `resources/views/layouts/app.blade.php`, `resources/views/layouts/admin.blade.php`
- Страницы (Blade):
  - Главная: `resources/views/main/index.blade.php`
  - Пользователь:
    - Кабинет: `resources/views/user/dashboard.blade.php`
    - Вывод средств: `resources/views/user/withdraw.blade.php`
    - Нарушения: `resources/views/user/violation.blade.php`
  - Аутентификация:
    - Вход: `resources/views/auth/login.blade.php`
    - Регистрация: `resources/views/auth/register.blade.php`
    - Подтверждение: `resources/views/auth/verify.blade.php`
  - Прочее:
    - Enter: `resources/views/enter.blade.php`
  - Админка:
    - Вход администратора: `resources/views/admin/login.blade.php`
    - Дешборд: `resources/views/admin/dashboard.blade.php`

## Маршруты

В `routes/web.php` добавлены маршруты для предпросмотра страниц (без бизнес‑логики):

```php
Route::view('/', 'main.index')->name('home');
Route::prefix('user')->name('user.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', fn () => view('user.auth.login'))->name('login');
        Route::get('/register', fn () => view('auth.register'))->name('register');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/', fn () => view('user.dashboard'))->name('dashboard');
        Route::get('/withdraw', fn () => view('user.withdraw'))->name('withdraw');
        Route::get('/violation', fn () => view('user.violation'))->name('violation');
    });
});

Route::view('/enter', 'enter')->name('enter');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::view('/login', 'admin.login')->name('login');
    Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
});
```

> ⚠️ **Важно:** формы в разметке снабжены `@csrf`, но реальные `action`/обработчики нужно сопоставить с существующими контроллерами/роутами приложения. Текущие маршруты добавлены для быстрого просмотра UI.

## Как смотреть локально

1. Установите зависимости проекта, если нужно: `composer install`, скопируйте `.env`, сгенерируйте ключ `php artisan key:generate`.
2. Поднимите сервер: `php artisan serve`.
3. Откройте:
   - `/` — главная (тема подключена)
   - `/login`, `/register`, `/user`, `/user/withdraw`, `/user/violation`
   - `/admin/login`, `/admin/dashboard`

## Что осталось сделать под конкретный функционал

- Сопоставить формы (login/register/withdraw и др.) с реальными экшенами контроллеров, выставить корректные `name` полей, `action` и `method`.
- Перенести динамические списки/таблицы в циклы Blade и обеспечить передачу данных из контроллеров (сейчас верстка перенесена «как есть» с базовой динамикой).
- Если требуется отдельный макет для страниц логина (без сайдбара/хедера), можно создать `layouts/blank.blade.php` и переключить нужные страницы.

## Примечания

- Все относительные пути ассетов из темы переписаны на `{{ asset('personal-acc/...') }}`, чтобы корректно работало в Laravel.
- Если используете Vite/Mix — можно перенести ассеты темы в пайплайн сборки для оптимизации.
