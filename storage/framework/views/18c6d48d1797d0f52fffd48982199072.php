<?php $__env->startSection('title', __('All transactions')); ?>
<?php $__env->startSection('content'); ?>

<?php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $withdrawCardErrors = $errors->withdraw_card ?? null;
    $withdrawIbanErrors = $errors->withdraw_iban ?? null;
    $withdrawCryptoErrors = $errors->withdraw_crypto ?? null;
    $withdrawInitialMethod = old('method') ?: session('last_withdraw_method');
    $withdrawActiveMethod = $withdrawInitialMethod ?: 'card';

    $formatMoney = static function ($amount, ?string $currency = null): string {
        if ($amount === null) {
            return '—';
        }

        $currency = $currency ?: (config('currencies.default') ?? 'EUR');

        return number_format((float) $amount, 2, '.', ' ') . ' ' . $currency;
    };

    $transactionStatusClass = static function (?string $status): string {
        return match (strtolower((string) $status)) {
            'success', 'approved', 'completed' => 'transaction-item transaction-item--success',
            'blocked', 'failed', 'declined', 'rejected' => 'transaction-item transaction-item--block',
            default => 'transaction-item transaction-item--wait',
        };
    };

    $transactionStatusLabel = static function (?string $status): string {
        $status = (string) $status;

        return $status !== '' ? Str::title($status) : 'Pending';
    };

    $transactionDateParts = static function ($transaction): array {
        $date = optional($transaction->created_at);

        return [
            $date?->format('d/m/y') ?? '—',
            $date?->format('H:i:s') ?? '—',
        ];
    };

    $maskValue = static function (?string $value, int $prefix = 4, int $suffix = 4): string {
        $value = trim((string) $value);

        if ($value === '') {
            return '—';
        }

        $length = Str::length($value);

        if ($length <= $prefix + $suffix + 3) {
            return $value;
        }

        return Str::substr($value, 0, $prefix) . ' ... ' . Str::substr($value, -$suffix);
    };

    $backRoute = Route::has('user.dashboard') ? route('user.dashboard') : '#';
?>

<div class="wrapper">
    <header class="header">
        <div class="container">
            <div class="header__inner">
                <a class="header__logo logo" href="#"><img alt="logo" src="<?php echo e(asset('personal-acc/img/logo.svg')); ?>"></a>
                <div class="header__actions">
                    <a class="btn btn--light" href="<?php echo e($backRoute); ?>">
                        <span><?php echo e(__('Back to dashboard')); ?></span>
                        <span class="btn__icon"><img alt="back" src="<?php echo e(asset('personal-acc/img/icons/arrow.svg')); ?>"></span>
                    </a>
                    <button class="btn btn--light btn-support" data-support-btn type="button">
                        Support
                        <span class="btn__icon"><img alt="support" src="<?php echo e(asset('personal-acc/img/icons/support.svg')); ?>"></span>
                    </button>
                    <div class="desktop">
                        <button class="btn" data-popup="#withdraw-modal" type="button"><span>Withdrawal of funds</span></button>
                    </div>
                    <div class="mobile">
                        <a class="btn" href="<?php echo e(route('user.withdraw')); ?>">Withdrawal <span class="btn__icon"><img alt="withdraw" src="<?php echo e(asset('personal-acc/img/icons/withdraw.svg')); ?>"></span></a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="page">
        <div class="container" style="padding-top: 2rem; padding-bottom: 3rem;">
            <div class="transactions-overview" style="display: flex; flex-direction: column; gap: 2rem;">
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <h1 style="font-size: 2rem; font-weight: 700;"><?php echo e(__('All transactions')); ?></h1>
                    <p style="color: #63616C; max-width: 720px;">
                        <?php echo e(__('Browse the complete history of your account activity. Use this list to review transfers, conversions, holds and other operations recorded in your profile.')); ?>

                    </p>
                </div>

                <div class="transaction-feed" style="display: grid; gap: 1rem;">
                    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            [$datePart, $timePart] = $transactionDateParts($transaction);
                            $statusClass = $transactionStatusClass($transaction->status);
                        ?>
                        <div class="<?php echo e($statusClass); ?>">
                            <div class="transaction-item__top">
                                <div class="transaction-item__title"><?php echo e(Str::upper($transaction->type ?? __('Transaction'))); ?></div>
                                <div class="transaction-item__date">
                                    <span><?php echo e($datePart); ?></span>
                                    <span><?php echo e($timePart); ?></span>
                                </div>
                            </div>
                            <div class="transaction-item__bottom">
                                <div class="transaction-item__block">
                                    <div class="transaction-item__num"><?php echo e($maskValue($transaction->from)); ?></div>
                                    <span><img alt="arrow" src="<?php echo e(asset('personal-acc/img/icons/arrow.svg')); ?>"></span>
                                    <div class="transaction-item__text-md"><?php echo e($maskValue($transaction->to)); ?></div>
                                </div>
                                <div class="transaction-item__sum"><?php echo e($formatMoney($transaction->amount, $transaction->currency ?? $displayCurrency)); ?></div>
                            </div>
                            <div style="margin-top: 0.75rem; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                                <span class="user-table__status user-table__status--hold" style="text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.04em;"><?php echo e($transactionStatusLabel($transaction->status)); ?></span>
                                <span style="font-size: 0.875rem; color: #63616C;">
                                    <?php echo e(__('Account')); ?>: <?php echo e($transaction->account?->number ?? __('Main balance')); ?>

                                </span>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="transaction-item transaction-item--wait">
                            <div class="transaction-item__top">
                                <div class="transaction-item__title"><?php echo e(__('No transactions yet')); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if($transactions->hasPages()): ?>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between;">
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <?php if($transactions->previousPageUrl()): ?>
                                <a class="btn btn--light" href="<?php echo e($transactions->previousPageUrl()); ?>"><?php echo e(__('Previous')); ?></a>
                            <?php else: ?>
                                <span class="btn btn--light" style="pointer-events: none; opacity: 0.6;"><?php echo e(__('Previous')); ?></span>
                            <?php endif; ?>

                            <?php if($transactions->nextPageUrl()): ?>
                                <a class="btn" href="<?php echo e($transactions->nextPageUrl()); ?>"><?php echo e(__('Next')); ?></a>
                            <?php else: ?>
                                <span class="btn" style="pointer-events: none; opacity: 0.6;"><?php echo e(__('Next')); ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.875rem; color: #63616C;">
                            <?php echo e(__('Page :current of :total', ['current' => $transactions->currentPage(), 'total' => $transactions->lastPage()])); ?>

                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php echo $__env->make('user.partials.withdraw-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('user.partials.violation-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/admin/Pictures/html_blade-codex-fix-edit-user-and-edit-account-functionality-50to50/resources/views/user/transactions/index.blade.php ENDPATH**/ ?>