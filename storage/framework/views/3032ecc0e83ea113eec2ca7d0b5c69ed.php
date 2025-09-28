<?php
    use Illuminate\Support\Str;

    if (strtolower((string) $user->verification_status) === 'active') {
        $user->verification_status = 'approved';
        $user->save();
    }

    $formatMoney = static function ($amount, ?string $currency = null): string {
        if ($amount === null) {
            return '—';
        }

        $currency = $currency ?: (config('currencies.default') ?? 'EUR');

        return number_format((float) $amount, 2, '.', ' ') . ' ' . $currency;
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

    $accountTypeLabel = static function ($type): string {
        return config('accounts.types')[$type] ?? (string) $type ?: '—';
    };

    $accountStatusClass = static function (?string $status): string {
        return match (strtolower((string) $status)) {
            'active' => 'user-table__status user-table__status--success',
            'hold', 'on hold', 'pending', 'processing' => 'user-table__status user-table__status--hold',
            'blocked', 'rejected', 'declined' => 'user-table__status user-table__status--block',
            default => 'user-table__status user-table__status--hold',
        };
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

    $withdrawalStatusClass = static function (?string $status): string {
        return match (strtolower((string) $status)) {
            'approved', 'completed', 'success' => 'transaction-item transaction-item--success',
            'rejected', 'failed', 'declined', 'canceled' => 'transaction-item transaction-item--block',
            default => 'transaction-item transaction-item--wait',
        };
    };

    $withdrawalStatusLabel = static function (?string $status): string {
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

    $withdrawals = ($withdrawals ?? collect());

    $userStatusClass = match (strtolower((string) $user->verification_status)) {
        'approved', 'verified', 'verificated' => 'user-info__status user-info__status--verify',
        'blocked', 'rejected' => 'user-info__status user-info__status--verify',
        default => 'user-info__status',
    };

    $userStatusLabel = ucfirst($user->verification_status ?? 'pending');

    $locationPieces = array_filter([
        $country ?: null,
        $dateOfBirth ? $dateOfBirth->format('m.d.Y') : null,
    ]);

    $locationText = implode(', ', $locationPieces);

    $portfolioBalance = $totalAccountBalance ?: $mainBalance;
    $portfolioBalance = $portfolioBalance ?: optional($primaryAccount)->balance;

    $transactionsList = $transactions->take(8);
    $transactionsTable = $transactions->take(12);
    $withdrawalsList = $withdrawals->take(8);
    $withdrawalsTable = $withdrawals->take(12);
?>

<div class="main active">
    <div class="user-info" data-da=".grid,1023.98,first">
        <div class="user-info__col">
            <div class="user-info__title"><?php echo e(__('Welcome')); ?> <?php echo e($user->name); ?></div>
            <div class="user-info__text">
                <?php if($locationText): ?>
                    <?php echo e($locationText); ?>

                <?php else: ?>
                    <?php echo e($user->email); ?>

                <?php endif; ?>
            </div>
            <div class="<?php echo e($userStatusClass); ?>"><?php echo e($userStatusLabel); ?></div>
        </div>
        <div class="user-info__col">
            <div class="user-info__title" style="font-weight: 600;">
                <?php echo e(__('Balance')); ?>

                <img alt="wallet" src="<?php echo e(asset('personal-acc/img/icons/wallet.svg')); ?>"/>
            </div>
            <div class="user-info__text-lg"><?php echo e($formatMoney($portfolioBalance, $displayCurrency)); ?></div>
        </div>
    </div>

    <div class="user-table">
        <table>
            <thead>
            <tr>
                <th><?php echo e(__('Company')); ?> <br/> <?php echo e(__('Broker')); ?></th>
                <th><?php echo e(__('Bank')); ?> <br/> <?php echo e(__('Account No.')); ?></th>
                <th><?php echo e(__('Owner')); ?></th>
                <th><?php echo e(__('Type')); ?></th>
                <th class="desktop"><?php echo e(__('Expiry date')); ?></th>
                <th>
                    <span class="desktop"><?php echo e(__('Balance')); ?></span>
                    <span class="mobile"><?php echo e(__('Expiry date')); ?> <br/> <?php echo e(__('Balance')); ?></span>
                </th>
                <th><?php echo e(__('Status')); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $term = optional($account->term)->format('d/m/y');
                    $balanceLabel = $formatMoney($account->balance, $account->currency ?? $displayCurrency);
                    $statusClass = $accountStatusClass($account->status);
                ?>
                <tr>
                    <td>
                        <div class="user-table__td">
                            <?php echo e($account->organization ?? '—'); ?>

                            <span><?php echo e($account->broker_initials ?? '—'); ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="user-table__td">
                            <?php echo e($account->bank ?? '—'); ?>

                            <span><?php echo e($maskValue($account->number)); ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="user-table__td">
                            <span><?php echo e($account->client_initials ?: $user->name); ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="user-table__td">
                            <span><?php echo e($accountTypeLabel($account->type)); ?></span>
                        </div>
                    </td>
                    <td class="desktop">
                        <div class="user-table__td">
                            <b><?php echo e($term ?: '—'); ?></b>
                        </div>
                    </td>
                    <td>
                        <div class="user-table__td">
                            <span class="mobile"><b><?php echo e($term ?: '—'); ?></b></span>
                            <b><?php echo e($balanceLabel); ?></b>
                        </div>
                    </td>
                    <td>
                        <div class="<?php echo e($statusClass); ?>">
                            <?php echo e(Str::upper($account->status ?? 'Pending')); ?>

                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7">
                        <div class="user-table__td" style="text-align: center;">
                            <?php echo e(__('No accounts yet')); ?>

                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="aside" data-transaction-scope>
    <div class="transaction desktop">
        <div class="transaction-header" data-transaction-tabs>
            <button class="transaction-tab transaction-tab--active" type="button" data-transaction-tab="transactions">
                <span><?php echo e(__('Transactions')); ?></span>
                <img alt="" src="<?php echo e(asset('personal-acc/img/icons/copy.svg')); ?>"/>
            </button>
            <button class="transaction-tab" type="button" data-transaction-tab="withdrawals">
                <span><?php echo e(__('Withdrawals')); ?></span>
                <img alt="" src="<?php echo e(asset('personal-acc/img/icons/withdraw.svg')); ?>"/>
            </button>
        </div>
        <div class="transaction-panels">
            <div class="transaction-panel is-active" data-transaction-panel="transactions">
                <div class="transaction-list">
                    <?php $__empty_1 = true; $__currentLoopData = $transactionsList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            [$datePart, $timePart] = $transactionDateParts($transaction);
                            $statusClass = $transactionStatusClass($transaction->status);
                        ?>
                        <div class="<?php echo e($statusClass); ?>">
                            <div class="transaction-item__top">
                                <div class="transaction-item__title"><?php echo e($transaction->type ?? __('Transaction')); ?></div>
                                <div class="transaction-item__date">
                                    <span><?php echo e($datePart); ?></span>
                                    <span><?php echo e($timePart); ?></span>
                                </div>
                            </div>
                            <div class="transaction-item__bottom">
                                <div class="transaction-item__block">
                                    <div class="transaction-item__num"><?php echo e($maskValue($transaction->from)); ?></div>
                                    <span><img alt="arrow" src="<?php echo e(asset('personal-acc/img/icons/arrow.svg')); ?>"/></span>
                                    <div class="transaction-item__text-md"><?php echo e($maskValue($transaction->to)); ?></div>
                                </div>
                                <div class="transaction-item__sum"><?php echo e($formatMoney($transaction->amount, $transaction->currency ?? $displayCurrency)); ?></div>
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
            </div>
            <div class="transaction-panel" data-transaction-panel="withdrawals" hidden>
                <div class="transaction-list">
                    <?php $__empty_1 = true; $__currentLoopData = $withdrawalsList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $withdrawal): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            [$datePart, $timePart] = $transactionDateParts($withdrawal);
                            $statusClass = $withdrawalStatusClass($withdrawal->status);
                            $accountNumber = $withdrawal->fromAccount?->number ?? __('Main balance');
                        ?>
                        <div class="<?php echo e($statusClass); ?>">
                            <div class="transaction-item__top">
                                <div class="transaction-item__title"><?php echo e(Str::upper($withdrawal->method ?? __('Withdrawal'))); ?></div>
                                <div class="transaction-item__date">
                                    <span><?php echo e($datePart); ?></span>
                                    <span><?php echo e($timePart); ?></span>
                                </div>
                            </div>
                            <div class="transaction-item__bottom">
                                <div class="transaction-item__block transaction-item__block--single">
                                    <div class="transaction-item__text-md"><?php echo e($accountNumber); ?></div>
                                </div>
                                <div class="transaction-item__sum"><?php echo e($formatMoney($withdrawal->amount, $displayCurrency)); ?></div>
                            </div>
                            <div class="transaction-item__meta-line"><?php echo e($withdrawalStatusLabel($withdrawal->status)); ?></div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="transaction-item transaction-item--wait">
                            <div class="transaction-item__top">
                                <div class="transaction-item__title"><?php echo e(__('No withdrawals yet')); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="transaction-actions" style="display:flex; flex-direction:column; gap:0.75rem;">
            <button class="btn btn--secondary" data-popup="#violation" type="button" style="text-align:center;">
                <?php echo e(__('Report a violation')); ?>

                <span class="btn__icon"><img alt="alert" src="<?php echo e(asset('personal-acc/img/icons/loudspeaker.svg')); ?>"/></span>
            </button>
        </div>
    </div>
    <div class="user-table" data-transaction-panel="transactions">
        <table>
            <thead>
            <tr>
                <th><?php echo e(__('From')); ?></th>
                <th><?php echo e(__('To')); ?></th>
                <th><?php echo e(__('Date')); ?> <br/> <?php echo e(__('Time')); ?></th>
                <th><?php echo e(__('Amount')); ?></th>
                <th><?php echo e(__('Status')); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $transactionsTable; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    [$datePart, $timePart] = $transactionDateParts($transaction);
                    $statusLabel = $transactionStatusLabel($transaction->status);
                ?>
                <tr>
                    <td style="font-weight:400">
                        <?php echo e($maskValue($transaction->from)); ?>

                    </td>
                    <td>
                        <?php echo e($maskValue($transaction->to)); ?>

                    </td>
                    <td style="color:#747474">
                        <?php echo e($datePart); ?>

                        <br/>
                        <?php echo e($timePart); ?>

                    </td>
                    <td>
                        <b><?php echo e($formatMoney($transaction->amount, $transaction->currency ?? $displayCurrency)); ?></b>
                    </td>
                    <td>
                        <?php echo e($statusLabel); ?>

                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="5" style="text-align:center;">
                        <?php echo e(__('No transactions yet')); ?>

                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="user-table user-table--withdrawals" data-transaction-panel="withdrawals" hidden>
        <table>
            <thead>
            <tr>
                <th><?php echo e(__('Method')); ?></th>
                <th><?php echo e(__('Account')); ?></th>
                <th><?php echo e(__('Date')); ?> <br/> <?php echo e(__('Time')); ?></th>
                <th><?php echo e(__('Amount')); ?></th>
                <th><?php echo e(__('Status')); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $withdrawalsTable; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $withdrawal): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    [$datePart, $timePart] = $transactionDateParts($withdrawal);
                    $statusLabel = $withdrawalStatusLabel($withdrawal->status);
                    $accountNumber = $withdrawal->fromAccount?->number ?? __('Main balance');
                ?>
                <tr>
                    <td style="font-weight:600; text-transform:uppercase;"><?php echo e($withdrawal->method ?? '—'); ?></td>
                    <td><?php echo e($accountNumber); ?></td>
                    <td style="color:#747474">
                        <?php echo e($datePart); ?>

                        <br/>
                        <?php echo e($timePart); ?>

                    </td>
                    <td><b><?php echo e($formatMoney($withdrawal->amount, $displayCurrency)); ?></b></td>
                    <td><?php echo e($statusLabel); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="5" style="text-align:center;">
                        <?php echo e(__('No withdrawals yet')); ?>

                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php /**PATH /Users/admin/Pictures/html_blade-codex-fix-edit-user-and-edit-account-functionality-50to50/resources/views/client/dashboard/partials/overview.blade.php ENDPATH**/ ?>