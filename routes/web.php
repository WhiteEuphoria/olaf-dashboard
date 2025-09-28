<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\SupportMessageController as AdminSupportMessageController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\ClientLoginController;
use App\Http\Controllers\Auth\ClientRegisterController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\SupportController;
use App\Http\Controllers\Client\TransactionsController;
use App\Http\Controllers\Client\ViolationController;
use App\Http\Controllers\Client\WithdrawalRequestController;
use App\Http\Controllers\DocumentUploadController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function (Request $request) {
    $user = $request->user();

    if ($user?->is_admin) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('user.dashboard');
})->middleware('auth')->name('dashboard');


// === Theme/Static integration routes (disabled by default) ===
if (config('integration.theme_routes')) {
    Route::view('/', 'main.index')->name('home');
    Route::prefix('user')->name('user.')->group(function () {
        Route::middleware('guest')->group(function () {
            Route::get('/login', fn () => view('user.auth.login'))->name('login');
            Route::post('/login', [ClientLoginController::class, 'store'])->name('login.attempt');
            Route::get('/register', [ClientRegisterController::class, 'create'])->name('register');
            Route::post('/register', [ClientRegisterController::class, 'store'])->name('register.store');
        });

        Route::middleware('auth')->group(function () {
            Route::post('/logout', function (Request $request) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('user.login');
            })->name('logout');

            Route::get('/verify', function (Request $request) {
                $user = $request->user();

                abort_if(! $user || $user->is_admin, 403);

                $status = strtolower((string) $user->verification_status);

                if ($status === 'active') {
                    $user->verification_status = 'approved';
                    $user->save();
                    $status = 'approved';
                }

                if ($status === 'approved') {
                    return redirect()->route('user.dashboard');
                }

                return view('user.auth.verify', [
                    'user' => $user,
                ]);
            })->name('verify');

            Route::get('/withdraw', [WithdrawalRequestController::class, 'create'])->name('withdraw');
            Route::post('/withdraw', [WithdrawalRequestController::class, 'store'])->name('withdraw.store');
            Route::post('/violation', [ViolationController::class, 'store'])->name('violation.store');
            Route::get('/support/messages', [SupportController::class, 'index'])->name('support.messages');
            Route::post('/support', [SupportController::class, 'store'])->name('support.store');
            Route::get('/transactions', [TransactionsController::class, 'index'])->name('transactions');
            Route::get('/', ClientDashboardController::class)->name('dashboard');
        });

        Route::get('/violation', [ViolationController::class, 'index'])->name('violation');
    });

    Route::get('/login', fn () => redirect()->route('user.login'))->name('login');
    Route::get('/register', fn () => redirect()->route('user.register'))->name('register');
    Route::get('/verify', fn () => redirect()->route('user.verify'))->name('verification.notice');
    Route::view('/enter', 'enter')->name('enter');
    Route::post('/enter', DocumentUploadController::class)
        ->name('documents.store');
    Route::prefix('admin')->group(function () {
        Route::view('/login', 'admin.login')->name('admin.login');
        Route::post('/login', [AdminLoginController::class, 'store'])->name('admin.login.attempt');

        Route::middleware(['auth', 'admin'])->group(function () {
            Route::redirect('/', '/admin/dashboard')->name('admin.home');
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
            Route::post('/dashboard/support', [AdminDashboardController::class, 'storeSupport'])->name('admin.dashboard.support');
            Route::get('/dashboard/support/threads', [AdminSupportMessageController::class, 'threads'])->name('admin.dashboard.support.threads');
            Route::get('/dashboard/support/messages', [AdminSupportMessageController::class, 'index'])->name('admin.dashboard.support.messages');
            Route::post('/dashboard/support/messages', [AdminSupportMessageController::class, 'store'])->name('admin.dashboard.support.messages.store');
            Route::post('/dashboard/withdrawals', [AdminDashboardController::class, 'storeWithdrawal'])->name('admin.dashboard.withdrawals.store');
            Route::post('/dashboard/fraud-claims', [AdminDashboardController::class, 'storeFraudClaim'])->name('admin.dashboard.fraud-claims.store');
            Route::post('/dashboard/accounts', [AdminDashboardController::class, 'storeAccount'])->name('admin.dashboard.accounts.store');
            Route::post('/dashboard/documents', [AdminDashboardController::class, 'storeDocument'])->name('admin.dashboard.documents.store');
            Route::put('/dashboard/users/{user}', [AdminDashboardController::class, 'updateUser'])->name('admin.dashboard.users.update');
            Route::put('/dashboard/accounts/{account}', [AdminDashboardController::class, 'updateAccount'])->name('admin.dashboard.accounts.update');
            Route::put('/dashboard/transactions/{transaction}', [AdminDashboardController::class, 'updateTransaction'])->name('admin.dashboard.transactions.update');
            Route::post('/dashboard/transactions', [AdminDashboardController::class, 'storeTransaction'])->name('admin.dashboard.transactions.store');
            Route::put('/dashboard/withdrawals/{withdrawal}', [AdminDashboardController::class, 'updateWithdrawal'])->name('admin.dashboard.withdrawals.update');
            Route::put('/dashboard/documents/{document}', [AdminDashboardController::class, 'updateDocument'])->name('admin.dashboard.documents.update');
            Route::delete('/dashboard/users/{user}', [AdminDashboardController::class, 'destroyUser'])->name('admin.dashboard.users.destroy');
            Route::get('/dashboard/documents/{document}/preview', [AdminDashboardController::class, 'previewDocument'])->name('admin.dashboard.documents.preview');
            Route::put('/dashboard/fraud-claims/{fraudClaim}', [AdminDashboardController::class, 'updateFraudClaim'])->name('admin.dashboard.fraud-claims.update');
            Route::get('/dashboard/fraud-claims/{fraudClaim}/attachments/{attachment}', [AdminDashboardController::class, 'downloadFraudClaimAttachment'])->name('admin.dashboard.fraud-claims.attachments.download');
            Route::get('/dashboard/fraud-claims/{fraudClaim}/attachments/{attachment}/preview', [AdminDashboardController::class, 'previewFraudClaimAttachment'])->name('admin.dashboard.fraud-claims.attachments.preview');
        });
    });
}
// When disabled, Filament panels handle /admin and /client entirely.
