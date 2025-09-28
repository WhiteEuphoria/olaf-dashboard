@extends('layouts.app')
@section('title', __('All transactions'))
@section('content')

@php
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
@endphp

<div class="wrapper">
    <header class="header">
        <div class="container">
            <div class="header__inner">
                <a class="header__logo logo" href="#"><img alt="logo" src="{{ asset('personal-acc/img/logo.svg') }}"></a>
                <div class="header__actions">
                    <a class="btn btn--light" href="{{ $backRoute }}">
                        <span>{{ __('Back to dashboard') }}</span>
                        <span class="btn__icon"><img alt="back" src="{{ asset('personal-acc/img/icons/arrow.svg') }}"></span>
                    </a>
                    <button class="btn btn--light btn-support" data-support-btn type="button">
                        Support
                        <span class="btn__icon"><img alt="support" src="{{ asset('personal-acc/img/icons/support.svg') }}"></span>
                    </button>
                    <div class="desktop">
                        <button class="btn" data-popup="#withdraw-modal" type="button"><span>Withdrawal of funds</span></button>
                    </div>
                    <div class="mobile">
                        <a class="btn" href="{{ route('user.withdraw') }}">Withdrawal <span class="btn__icon"><img alt="withdraw" src="{{ asset('personal-acc/img/icons/withdraw.svg') }}"></span></a>
                    </div>
                    <form method="POST" action="{{ route('user.logout') }}" style="margin-left: 0.5rem;">
                        @csrf
                        <button class="btn btn--light" type="submit">Выход</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="page">
        <div class="container" style="padding-top: 2rem; padding-bottom: 3rem;">
            <div class="transactions-overview" style="display: flex; flex-direction: column; gap: 2rem;">
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <h1 style="font-size: 2rem; font-weight: 700;">{{ __('All transactions') }}</h1>
                    <p style="color: #63616C; max-width: 720px;">
                        {{ __('Browse the complete history of your account activity. Use this list to review transfers, conversions, holds and other operations recorded in your profile.') }}
                    </p>
                </div>

                <div class="transaction-feed" style="display: grid; gap: 1rem;">
                    @forelse($transactions as $transaction)
                        @php
                            [$datePart, $timePart] = $transactionDateParts($transaction);
                            $statusClass = $transactionStatusClass($transaction->status);
                        @endphp
                        <div class="{{ $statusClass }}">
                            <div class="transaction-item__top">
                                <div class="transaction-item__title">{{ Str::upper($transaction->type ?? __('Transaction')) }}</div>
                                <div class="transaction-item__date">
                                    <span>{{ $datePart }}</span>
                                    <span>{{ $timePart }}</span>
                                </div>
                            </div>
                            <div class="transaction-item__bottom">
                                <div class="transaction-item__block">
                                    <div class="transaction-item__num">{{ $maskValue($transaction->from) }}</div>
                                    <span><img alt="arrow" src="{{ asset('personal-acc/img/icons/arrow.svg') }}"></span>
                                    <div class="transaction-item__text-md">{{ $maskValue($transaction->to) }}</div>
                                </div>
                                <div class="transaction-item__sum">{{ $formatMoney($transaction->amount, $transaction->currency ?? $displayCurrency) }}</div>
                            </div>
                            <div style="margin-top: 0.75rem; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                                <span class="user-table__status user-table__status--hold" style="text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.04em;">{{ $transactionStatusLabel($transaction->status) }}</span>
                                <span style="font-size: 0.875rem; color: #63616C;">
                                    {{ __('Account') }}: {{ $transaction->account?->number ?? __('Main balance') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="transaction-item transaction-item--wait">
                            <div class="transaction-item__top">
                                <div class="transaction-item__title">{{ __('No transactions yet') }}</div>
                            </div>
                        </div>
                    @endforelse
                </div>

                @if($transactions->hasPages())
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between;">
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            @if($transactions->previousPageUrl())
                                <a class="btn btn--light" href="{{ $transactions->previousPageUrl() }}">{{ __('Previous') }}</a>
                            @else
                                <span class="btn btn--light" style="pointer-events: none; opacity: 0.6;">{{ __('Previous') }}</span>
                            @endif

                            @if($transactions->nextPageUrl())
                                <a class="btn" href="{{ $transactions->nextPageUrl() }}">{{ __('Next') }}</a>
                            @else
                                <span class="btn" style="pointer-events: none; opacity: 0.6;">{{ __('Next') }}</span>
                            @endif
                        </div>
                        <div style="font-size: 0.875rem; color: #63616C;">
                            {{ __('Page :current of :total', ['current' => $transactions->currentPage(), 'total' => $transactions->lastPage()]) }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>

    @include('user.partials.withdraw-modal')
    @include('user.partials.violation-modal')
@endsection
