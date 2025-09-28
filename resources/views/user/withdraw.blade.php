@extends('layouts.app')
@section('title', 'Вывод средств')
@section('content')

@php
    $cardErrors = $errors->withdraw_card ?? null;
    $ibanErrors = $errors->withdraw_iban ?? null;
    $cryptoErrors = $errors->withdraw_crypto ?? null;
    $initialMethod = old('method') ?: session('last_withdraw_method');
    $accountsList = isset($accounts) ? collect($accounts) : collect();
    $mainBalanceLabel = __('Main balance') . ' — ' . ($user->currency ?? config('currencies.default')) . ' ' . number_format((float) $user->main_balance, 2, '.', ' ');
    $selectedAccountOld = old('from_account_id');
    if ($selectedAccountOld === null || $selectedAccountOld === '') {
        $selectedAccountOld = 'main';
    }
@endphp

<div class="wrapper">
<header class="header">
<div class="container">
<div class="header__inner">
<a class="header__logo logo" href="#"><img alt="" src="{{ asset('personal-acc/img/logo.svg') }}"/></a>
<div class="header__actions">
<button class="btn" data-popup="#withdraw-modal" type="button"><span>Withdrawal <span class="desktop">of
									funds</span></span>
<span class="btn__icon mobile">
<img alt="withdraw" src="{{ asset('personal-acc/img/icons/withdraw.svg') }}"/>
</span>
</button>
<form method="POST" action="{{ route('user.logout') }}" style="margin-left: 0.5rem;">
@csrf
<button class="btn btn--light" type="submit">Выход</button>
</form>
</div>
</div>
</div>
</header>
<main class="page">
<div class="container">
<div class="grid">
<div class="withdrawal" @if($initialMethod) data-initial-method="{{ $initialMethod }}" @endif>
@if(session('status'))
    <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 0.75rem; background: #ecfdf5; color: #047857; font-weight: 600; text-align: center;">
        {{ session('status') }}
    </div>
@endif
<button class="withdrawal__back" type="button">
<span><svg fill="none" height="24" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
<path d="M14.9999 7L9.99994 12L14.9999 17" stroke="black" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
</svg>
</span>
<span>Back</span>
</button>
<div class="withdrawal__block withdrawal__block--centered" data-withdraw-nav="">
<div class="withdrawal__title">Choose a withdrawal method</div>
<div class="withdrawal__nav">
<button class="withdrawal__btn active" data-target="card" type="button"><span>Withdrawal
										to the
										card</span>
<svg fill="none" height="40" viewbox="0 0 41 40" width="41" xmlns="http://www.w3.org/2000/svg">
<mask height="32" id="mask0_110_5752" maskunits="userSpaceOnUse" style="mask-type:luminance" width="37" x="2" y="4">
<path d="M12.1666 10.8335V7.50016C12.1666 7.05814 12.3422 6.63421 12.6548 6.32165C12.9673 6.00909 13.3913 5.8335 13.8333 5.8335H35.5C35.942 5.8335 36.3659 6.00909 36.6785 6.32165C36.991 6.63421 37.1666 7.05814 37.1666 7.50016V22.5002C37.1666 22.9422 36.991 23.3661 36.6785 23.6787C36.3659 23.9912 35.942 24.1668 35.5 24.1668H33.8333" stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
<path d="M27.1666 15.8335H5.49998C4.57951 15.8335 3.83331 16.5797 3.83331 17.5002V32.5002C3.83331 33.4206 4.57951 34.1668 5.49998 34.1668H27.1666C28.0871 34.1668 28.8333 33.4206 28.8333 32.5002V17.5002C28.8333 16.5797 28.0871 15.8335 27.1666 15.8335Z" fill="white" stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
<path d="M3.83331 23.3335H28.8333" stroke="black" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
<path d="M28.8333 19.167V29.167M3.83331 19.167V29.167" stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
<path d="M9.66663 28.3335H16.3333M21.3333 28.3335H23" stroke="black" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
</mask>
<g mask="url(#mask0_110_5752)">
<path d="M0.5 0H40.5V40H0.5V0Z" fill="currentColor"></path>
</g>
</svg>
</button>
<button class="withdrawal__btn" data-target="iban" type="button">
<span>Withdrawal by IBAN</span>
<svg fill="none" height="40" viewbox="0 0 41 40" width="41" xmlns="http://www.w3.org/2000/svg">
<path d="M25.5 25.0002V20.0002H30.5V16.6668L37.1666 22.5002L30.5 28.3335V25.0002H25.5ZM23.8333 14.5002V16.6668H3.83331V14.5002L13.8333 8.3335L23.8333 14.5002ZM3.83331 28.3335H23.8333V31.6668H3.83331V28.3335ZM12.1666 18.3335H15.5V26.6668H12.1666V18.3335ZM5.49998 18.3335H8.83331V26.6668H5.49998V18.3335ZM18.8333 18.3335H22.1666V26.6668H18.8333V18.3335Z" fill="currentColor"></path>
</svg>
</button>
<button class="withdrawal__btn" data-target="crypto" type="button">
<span>Withdrawal to cryptocash</span>
<svg fill="none" height="40" viewbox="0 0 41 40" width="41" xmlns="http://www.w3.org/2000/svg">
<path d="M31.75 6.25H28.3617C27.8424 6.25036 27.3394 6.4314 26.939 6.76205C26.5386 7.09271 26.2658 7.55238 26.1672 8.06223C26.0687 8.57208 26.1506 9.10032 26.399 9.55637C26.6473 10.0124 27.0466 10.3678 27.5284 10.5617L30.9684 11.9383C31.4501 12.1322 31.8494 12.4876 32.0978 12.9436C32.3461 13.3997 32.428 13.9279 32.3295 14.4378C32.231 14.9476 31.9581 15.4073 31.5577 15.7379C31.1573 16.0686 30.6543 16.2496 30.135 16.25H29.25M29.25 6.25V5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
<path d="M30.55 21.1667C32.4004 20.9237 34.1459 20.1677 35.589 18.9844C37.0322 17.8011 38.1155 16.2376 38.7165 14.4708C39.3174 12.7039 39.412 10.8041 38.9896 8.9863C38.5672 7.16847 37.6445 5.50509 36.326 4.18426C35.0076 2.86343 33.3458 1.93785 31.5288 1.51217C29.7117 1.0865 27.8118 1.17772 26.0438 1.77552C24.2759 2.37331 22.7105 3.45384 21.5246 4.8949C20.3387 6.33597 19.5797 8.08009 19.3334 9.93003M26.75 26.25C26.75 22.9348 25.4331 19.7554 23.0889 17.4112C20.7446 15.067 17.5652 13.75 14.25 13.75M4.25002 18.75C3.23676 20.1147 2.51246 21.6717 2.12141 23.3258C1.73035 24.9799 1.68074 26.6964 1.97563 28.3703C2.27051 30.0443 2.90368 31.6405 3.83644 33.0614C4.7692 34.4823 5.98195 35.6981 7.40054 36.6343C8.81914 37.5706 10.4138 38.2077 12.087 38.5068C13.7602 38.8058 15.4768 38.7604 17.1319 38.3735C18.7869 37.9865 20.3457 37.2661 21.7129 36.2562C23.0801 35.2463 24.2269 33.9682 25.0834 32.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
<path d="M15.5 26.25C16.163 26.25 16.7989 25.9866 17.2678 25.5178C17.7366 25.0489 18 24.413 18 23.75C18 23.087 17.7366 22.4511 17.2678 21.9822C16.7989 21.5134 16.163 21.25 15.5 21.25H11.75V31.25H15.5C16.163 31.25 16.7989 30.9866 17.2678 30.5178C17.7366 30.0489 18 29.413 18 28.75C18 28.087 17.7366 27.4511 17.2678 26.9822C16.7989 26.5134 16.163 26.25 15.5 26.25ZM15.5 26.25H11.75M14.25 21.25V18.75M14.25 31.25V33.75M1.75 7.5L5.5 13.75L11.75 10" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
<path d="M14.1483 1.5C11.8341 2.3433 9.8606 3.92279 8.53074 5.99601C7.20088 8.06923 6.58817 10.5216 6.78666 12.9767M39.25 32.5L35.5 26.25L29.25 30" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
<path d="M26.8517 38.5002C29.1653 37.657 31.1384 36.0781 32.4682 34.0055C33.798 31.933 34.4111 29.4814 34.2134 27.0269" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
</svg>
</button>
</div>
</div>
<div class="withdrawal__block" data-withdraw-card="">
<div class="withdrawal__title">Please complete the information below</div>
<form action="{{ route('user.withdraw.store') }}" method="POST">
@csrf
<input type="hidden" name="method" value="card">

<div class="field @if($cardErrors && $cardErrors->has('from_account_id')) has-error @endif">
    <select name="from_account_id">
        <option value="main" @selected($selectedAccountOld === 'main')>{{ $mainBalanceLabel }}</option>
        @foreach($accountsList as $account)
            @php
                $accountOptionValue = (string) $account->id;
                $accountOptionLabel = ($account->name ?? __('Account')) . ' (' . ($account->type ?? '-') . ') — ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
            @endphp
            <option value="{{ $account->id }}" @selected($selectedAccountOld === $accountOptionValue)>{{ $accountOptionLabel }}</option>
        @endforeach
    </select>
    @if($cardErrors && $cardErrors->has('from_account_id'))
        <span class="error-message">{{ $cardErrors->first('from_account_id') }}</span>
    @endif
</div>

@if($cardErrors && $cardErrors->has('method'))
    <div class="field has-error">
        <span class="error-message">{{ $cardErrors->first('method') }}</span>
    </div>
@endif

<div class="field @if($cardErrors && $cardErrors->has('card.number')) has-error @endif">
<input name="card[number]" placeholder="1111 2222 3333 4444" type="text" inputmode="numeric" value="{{ old('card.number') }}" required>
@if($cardErrors && $cardErrors->has('card.number'))
<span class="error-message">{{ $cardErrors->first('card.number') }}</span>
@endif
</div>
<div class="field @if($cardErrors && $cardErrors->has('card.holder')) has-error @endif">
<input name="card[holder]" placeholder="Fullname card holder" type="text" value="{{ old('card.holder') }}" required>
@if($cardErrors && $cardErrors->has('card.holder'))
<span class="error-message">{{ $cardErrors->first('card.holder') }}</span>
@endif
</div>
<div class="field @if($cardErrors && $cardErrors->has('amount')) has-error @endif">
<input name="amount" placeholder="Amount" type="number" step="0.01" min="1" value="{{ old('amount') }}" required>
@if($cardErrors && $cardErrors->has('amount'))
<span class="error-message">{{ $cardErrors->first('amount') }}</span>
@endif
</div>
<button class="btn btn--md" type="submit">Withdrawal</button>
</form>
</div>
<div class="withdrawal__block" data-withdraw-iban="">
<div class="withdrawal__title">Please complete the information below</div>
<form action="{{ route('user.withdraw.store') }}" method="POST">
@csrf
<input type="hidden" name="method" value="iban">

<div class="field @if($ibanErrors && $ibanErrors->has('from_account_id')) has-error @endif">
    <select name="from_account_id">
        <option value="main" @selected($selectedAccountOld === 'main')>{{ $mainBalanceLabel }}</option>
        @foreach($accountsList as $account)
            @php
                $accountOptionValue = (string) $account->id;
                $accountOptionLabel = ($account->name ?? __('Account')) . ' (' . ($account->type ?? '-') . ') — ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
            @endphp
            <option value="{{ $account->id }}" @selected($selectedAccountOld === $accountOptionValue)>{{ $accountOptionLabel }}</option>
        @endforeach
    </select>
    @if($ibanErrors && $ibanErrors->has('from_account_id'))
        <span class="error-message">{{ $ibanErrors->first('from_account_id') }}</span>
    @endif
</div>

@if($ibanErrors && $ibanErrors->has('method'))
    <div class="field has-error">
        <span class="error-message">{{ $ibanErrors->first('method') }}</span>
    </div>
@endif

<div class="field @if($ibanErrors && $ibanErrors->has('iban.iban')) has-error @endif">
<input name="iban[iban]" placeholder="Enter IBAN" type="text" value="{{ old('iban.iban') }}" required>
@if($ibanErrors && $ibanErrors->has('iban.iban'))
<span class="error-message">{{ $ibanErrors->first('iban.iban') }}</span>
@endif
</div>
<div class="field @if($ibanErrors && $ibanErrors->has('iban.bic')) has-error @endif">
<input name="iban[bic]" placeholder="BIC code" type="text" value="{{ old('iban.bic') }}" required>
@if($ibanErrors && $ibanErrors->has('iban.bic'))
<span class="error-message">{{ $ibanErrors->first('iban.bic') }}</span>
@endif
</div>
<div class="field @if($ibanErrors && $ibanErrors->has('iban.holder')) has-error @endif">
<input name="iban[holder]" placeholder="Fullname bank account holder" type="text" value="{{ old('iban.holder') }}" required>
@if($ibanErrors && $ibanErrors->has('iban.holder'))
<span class="error-message">{{ $ibanErrors->first('iban.holder') }}</span>
@endif
</div>
<div class="field @if($ibanErrors && $ibanErrors->has('iban.country')) has-error @endif">
<input name="iban[country]" placeholder="Country" type="text" value="{{ old('iban.country') }}" required>
@if($ibanErrors && $ibanErrors->has('iban.country'))
<span class="error-message">{{ $ibanErrors->first('iban.country') }}</span>
@endif
</div>
<div class="field @if($ibanErrors && $ibanErrors->has('iban.bank')) has-error @endif">
<input name="iban[bank]" placeholder="Name of the bank" type="text" value="{{ old('iban.bank') }}" required>
@if($ibanErrors && $ibanErrors->has('iban.bank'))
<span class="error-message">{{ $ibanErrors->first('iban.bank') }}</span>
@endif
</div>
<div class="field @if($ibanErrors && $ibanErrors->has('amount')) has-error @endif">
<input name="amount" placeholder="Amount" type="number" step="0.01" min="1" value="{{ old('amount') }}" required>
@if($ibanErrors && $ibanErrors->has('amount'))
<span class="error-message">{{ $ibanErrors->first('amount') }}</span>
@endif
</div>
<button class="btn btn--md" type="submit">Withdrawal</button>
</form>
</div>
<div class="withdrawal__block" data-withdraw-crypto="">
<button class="btn-toggle-crypto-window" style="padding: 0.5rem; border-radius: 0.25rem; border: 1px solid #63616C; font-size: 0.625rem; margin-bottom: 0.8rem; margin-inline: auto;" type="button">click
								show crypto type window</button>
<div class="type-crypto-window">
<img alt="attention" src="{{ asset('personal-acc/img/icons/attention.svg') }}"/>
<p>At first you need to change the type of account to "Crypto" type</p>
</div>
<form action="{{ route('user.withdraw.store') }}" method="POST" class="form-crypto">
@csrf
<input type="hidden" name="method" value="crypto">

<div class="field @if($cryptoErrors && $cryptoErrors->has('from_account_id')) has-error @endif">
    <select name="from_account_id">
        <option value="main" @selected($selectedAccountOld === 'main')>{{ $mainBalanceLabel }}</option>
        @foreach($accountsList as $account)
            @php
                $accountOptionValue = (string) $account->id;
                $accountOptionLabel = ($account->name ?? __('Account')) . ' (' . ($account->type ?? '-') . ') — ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
            @endphp
            <option value="{{ $account->id }}" @selected($selectedAccountOld === $accountOptionValue)>{{ $accountOptionLabel }}</option>
        @endforeach
    </select>
    @if($cryptoErrors && $cryptoErrors->has('from_account_id'))
        <span class="error-message">{{ $cryptoErrors->first('from_account_id') }}</span>
    @endif
</div>

@if($cryptoErrors && $cryptoErrors->has('method'))
    <div class="field has-error">
        <span class="error-message">{{ $cryptoErrors->first('method') }}</span>
    </div>
@endif

<div class="withdrawal__title">Please complete the information below</div>
<div class="field @if($cryptoErrors && $cryptoErrors->has('crypto.address')) has-error @endif">
<input name="crypto[address]" placeholder="Deposit address" type="text" value="{{ old('crypto.address') }}" required>
@if($cryptoErrors && $cryptoErrors->has('crypto.address'))
<span class="error-message">{{ $cryptoErrors->first('crypto.address') }}</span>
@endif
</div>
<div class="field @if($cryptoErrors && $cryptoErrors->has('crypto.network')) has-error @endif">
<input name="crypto[network]" placeholder="Network" type="text" value="{{ old('crypto.network') }}" required>
@if($cryptoErrors && $cryptoErrors->has('crypto.network'))
<span class="error-message">{{ $cryptoErrors->first('crypto.network') }}</span>
@endif
</div>
<div class="field @if($cryptoErrors && $cryptoErrors->has('crypto.coin')) has-error @endif">
<input name="crypto[coin]" placeholder="Coin" type="text" value="{{ old('crypto.coin') }}" required>
@if($cryptoErrors && $cryptoErrors->has('crypto.coin'))
<span class="error-message">{{ $cryptoErrors->first('crypto.coin') }}</span>
@endif
</div>
<div class="field @if($cryptoErrors && $cryptoErrors->has('amount')) has-error @endif">
<input name="amount" placeholder="Amount" type="number" step="0.01" min="1" value="{{ old('amount') }}" required>
@if($cryptoErrors && $cryptoErrors->has('amount'))
<span class="error-message">{{ $cryptoErrors->first('amount') }}</span>
@endif
</div>
<button class="btn btn--md" type="submit">Withdrawal</button>
</form>
</div>
</div>
</div>
</div>
</main>
</div>
@include('user.partials.violation-modal')
<div aria-hidden="true" class="popup popup--sm" id="create-modal">
<div class="popup__wrapper">
<div class="popup__content">
<div class="create-account">
<div class="create-account__title">Создание нового счёта</div>
<form action="#" class="create-account__form">
@csrf

<div class="field"><input placeholder="Название счёта (Account name)" type="text"/></div>
<div class="field"><input placeholder="Номер счёта (Account number)" type="text"/></div>
<div class="field"><input placeholder="Тип счёта (Account type)" type="text"/></div>
<div class="field"><input placeholder="Банк (Bank)" type="text"/></div>
<div class="field"><input placeholder="Инициалы клиента (Client's fullname)" type="text"/></div>
<div class="field"><input placeholder="Срок действия (Expiration date)" type="text"/></div>
<select>
<option disabled="" selected="" value="">Статус</option>
<option value="2">Active</option>
<option value="3">Hold</option>
<option value="4">Blocked</option>
</select>
<button class="btn btn--md" type="submit">Добавить новый счёт</button>
</form>
</div>
</div>
</div>
</div>
<div aria-hidden="true" class="popup" id="withdraw-modal">
<div class="popup__wrapper">
<div class="popup__content">
<button class="popup__close" data-close="" type="button">
<svg fill="none" height="20" viewbox="0 0 20 20" width="20" xmlns="http://www.w3.org/2000/svg">
<path d="M1 1L19 19M19 1L1 19" stroke="black" stroke-linecap="round" stroke-width="2"></path>
</svg>
</button>
<div class="modal-content">
<div class="modal-content__top">
<div class="logo"><img alt="logo" src="{{ asset('personal-acc/img/logo.svg') }}"/></div>
<div class="modal-content__text">
<p>Choose a withdrawal method</p>
</div>
</div>
<div class="modal-content__body">
<div class="tabs" data-tabs="">
<nav class="tabs__navigation" data-tabs-titles="">
<button class="tabs__title _tab-active" type="button">
<span>
										Withdrawal to the card
									</span>
<span>
<svg fill="none" height="40" viewbox="0 0 41 40" width="41" xmlns="http://www.w3.org/2000/svg">
<mask height="32" id="mask0_41_232" maskunits="userSpaceOnUse" style="mask-type:luminance" width="37" x="2" y="4">
<path d="M12.1666 10.8333V7.49998C12.1666 7.05795 12.3422 6.63403 12.6548 6.32147C12.9673 6.00891 13.3913 5.83331 13.8333 5.83331H35.5C35.942 5.83331 36.3659 6.00891 36.6785 6.32147C36.991 6.63403 37.1666 7.05795 37.1666 7.49998V22.5C37.1666 22.942 36.991 23.3659 36.6785 23.6785C36.3659 23.991 35.942 24.1666 35.5 24.1666H33.8333" stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
<path d="M27.1666 15.8333H5.49998C4.57951 15.8333 3.83331 16.5795 3.83331 17.5V32.5C3.83331 33.4205 4.57951 34.1666 5.49998 34.1666H27.1666C28.0871 34.1666 28.8333 33.4205 28.8333 32.5V17.5C28.8333 16.5795 28.0871 15.8333 27.1666 15.8333Z" fill="white" stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
<path d="M3.83331 23.3333H28.8333" stroke="black" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
<path d="M28.8333 19.1666V29.1666M3.83331 19.1666V29.1666" stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
<path d="M9.66663 28.3333H16.3333M21.3333 28.3333H23" stroke="black" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
</mask>
<g mask="url(#mask0_41_232)">
<path d="M0.5 0H40.5V40H0.5V0Z" fill="currentColor"></path>
</g>
</svg>
</span>
</button>
<button class="tabs__title" type="button">
<span>Withdrawal by IBAN</span>
<span><svg fill="none" height="40" viewbox="0 0 41 40" width="41" xmlns="http://www.w3.org/2000/svg">
<path d="M25.5 25V20H30.5V16.6666L37.1667 22.5L30.5 28.3333V25H25.5ZM23.8334 14.5V16.6666H3.83337V14.5L13.8334 8.33331L23.8334 14.5ZM3.83337 28.3333H23.8334V31.6666H3.83337V28.3333ZM12.1667 18.3333H15.5V26.6666H12.1667V18.3333ZM5.50004 18.3333H8.83337V26.6666H5.50004V18.3333ZM18.8334 18.3333H22.1667V26.6666H18.8334V18.3333Z" fill="currentColor"></path>
</svg>
</span>
</button>
<button class="tabs__title" type="button">
<span>Withdrawal to cryptocash</span>
<span><svg fill="none" height="40" viewbox="0 0 41 40" width="41" xmlns="http://www.w3.org/2000/svg">
<path d="M31.75 6.25H28.3617C27.8424 6.25036 27.3394 6.4314 26.939 6.76205C26.5386 7.09271 26.2658 7.55238 26.1672 8.06223C26.0687 8.57208 26.1506 9.10032 26.399 9.55637C26.6473 10.0124 27.0466 10.3678 27.5284 10.5617L30.9684 11.9383C31.4501 12.1322 31.8494 12.4876 32.0978 12.9436C32.3461 13.3997 32.428 13.9279 32.3295 14.4378C32.231 14.9476 31.9581 15.4073 31.5577 15.7379C31.1573 16.0686 30.6543 16.2496 30.135 16.25H29.25M29.25 6.25V5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
<path d="M30.55 21.1667C32.4003 20.9237 34.1458 20.1677 35.589 18.9844C37.0322 17.8011 38.1155 16.2376 38.7164 14.4708C39.3174 12.7039 39.412 10.8041 38.9895 8.9863C38.5671 7.16847 37.6445 5.50509 36.326 4.18426C35.0075 2.86343 33.3458 1.93785 31.5287 1.51217C29.7116 1.0865 27.8117 1.17772 26.0438 1.77552C24.2758 2.37331 22.7104 3.45384 21.5245 4.8949C20.3387 6.33597 19.5796 8.08009 19.3333 9.93003M26.75 26.25C26.75 22.9348 25.433 19.7554 23.0888 17.4112C20.7446 15.067 17.5652 13.75 14.25 13.75M4.24996 18.75C3.2367 20.1147 2.5124 21.6717 2.12134 23.3258C1.73029 24.9799 1.68068 26.6964 1.97557 28.3703C2.27045 30.0443 2.90362 31.6405 3.83638 33.0614C4.76914 34.4823 5.98189 35.6981 7.40048 36.6343C8.81908 37.5706 10.4137 38.2077 12.0869 38.5068C13.7601 38.8058 15.4767 38.7604 17.1318 38.3735C18.7869 37.9865 20.3457 37.2661 21.7128 36.2562C23.08 35.2463 24.2269 33.9682 25.0833 32.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
<path d="M15.5 26.25C16.163 26.25 16.7989 25.9866 17.2678 25.5178C17.7366 25.0489 18 24.413 18 23.75C18 23.087 17.7366 22.4511 17.2678 21.9822C16.7989 21.5134 16.163 21.25 15.5 21.25H11.75V31.25H15.5C16.163 31.25 16.7989 30.9866 17.2678 30.5178C17.7366 30.0489 18 29.413 18 28.75C18 28.087 17.7366 27.4511 17.2678 26.9822C16.7989 26.5134 16.163 26.25 15.5 26.25ZM15.5 26.25H11.75M14.25 21.25V18.75M14.25 31.25V33.75M1.75 7.5L5.5 13.75L11.75 10" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
<path d="M14.1484 1.5C11.8342 2.3433 9.86066 3.92279 8.5308 5.99601C7.20094 8.06923 6.58823 10.5216 6.78672 12.9767M39.2501 32.5L35.5001 26.25L29.2501 30" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
<path d="M26.8517 38.5C29.1653 37.6569 31.1384 36.0779 32.4682 34.0053C33.798 31.9328 34.4111 29.4812 34.2134 27.0267" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
</svg>
</span>
</button>
</nav>
<div class="tabs__content" data-tabs-body="">
<div class="tabs__body">
<form action="#">
@csrf

<div class="field has-error">
<input placeholder="1111 2222 3333 4444" type="number"/>
<span class="error-message">message error</span>
</div>
<div class="field">
<input placeholder="Fullname card holder" type="text"/>
<span class="error-message">message error</span>
</div>
<div class="field">
<input placeholder="Amount" type="number"/>
<span class="error-message">message error</span>
</div>
<button class="btn btn--md" disabled="" type="submit">Withdrawal</button>
</form>
</div>
<div class="tabs__body">
<form action="#">
@csrf

<div class="field">
<input placeholder="Enter IBAN" type="text"/>
<span class="error-message">message error</span>
</div>
<div class="field">
<input placeholder="BIC code" type="text"/>
<span class="error-message">message error</span>
</div>
<div class="field">
<input placeholder="Fullname bank account holder" type="text"/>
<span class="error-message">message error</span>
</div>
<div class="field">
<select>
<option selected="" value="">Country</option>
<option value="2">Пункт №2</option>
<option value="3">Пункт №3</option>
<option value="4">Пункт №4</option>
</select>
</div>
<div class="field">
<input placeholder="Name of the bank" type="text"/>
<span class="error-message">message error</span>
</div>
<div class="field">
<input placeholder="Amount" type="number"/>
<span class="error-message">message error</span>
</div>
<button class="btn btn--md" disabled="" type="submit">Withdrawal</button>
</form>
</div>
<div class="tabs__body">
<button class="btn-toggle-crypto-window" style="padding: 0.5rem; border-radius: 0.25rem; border: 1px solid #63616C; font-size: 0.625rem; margin-bottom: 0.8rem; margin-inline: auto;" type="button">click
										show crypto type window</button>
<div class="type-crypto-window">
<img alt="attention" src="{{ asset('personal-acc/img/icons/attention.svg') }}"/>
<p>At first you need to change the type of account to "Crypto" type</p>
</div>
<form action="#" class="form-crypto">
@csrf

<div class="field">
<input placeholder="Deposit address" type="text"/>
<span class="error-message">message error</span>
</div>
<div class="field">
<select>
<option selected="" value="">Network</option>
<option value="2">Пункт №2</option>
<option value="3">Пункт №3</option>
<option value="4">Пункт №4</option>
</select>
</div>
<div class="field">
<select>
<option selected="" value="">Coin</option>
<option value="2">Пункт №2</option>
<option value="3">Пункт №3</option>
<option value="4">Пункт №4</option>
</select>
</div>
<button class="btn btn--md" disabled="" type="submit">Withdrawal</button>
</form>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
@endsection
