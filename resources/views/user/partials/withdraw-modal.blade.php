@php
    $modalUser = isset($user) ? $user : auth()->user();
    $modalAccountsCollection = isset($accounts)
        ? collect($accounts)
        : ($modalUser ? $modalUser->accounts()->orderByDesc('is_default')->orderBy('number')->get() : collect());
    $modalMainBalanceLabel = $modalUser
        ? __('Main balance') . ' — ' . ($modalUser->currency ?? config('currencies.default')) . ' ' . number_format((float) $modalUser->main_balance, 2, '.', ' ')
        : __('Main balance');
    $modalSelectedAccount = old('from_account_id');
    if ($modalSelectedAccount === null || $modalSelectedAccount === '') {
        $modalSelectedAccount = 'main';
    }
@endphp

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
@if(session('status'))
    <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 0.75rem; background: #ecfdf5; color: #047857; font-weight: 600; text-align: center;">
        {{ session('status') }}
    </div>
@endif
<nav class="tabs__navigation" data-tabs-titles="">
    <button class="tabs__title {{ $withdrawActiveMethod === 'card' ? '_tab-active' : '' }}" type="button">
        <span>Withdrawal to the card</span>
        <span>
            <svg fill="none" height="40" viewBox="0 0 41 40" width="41" xmlns="http://www.w3.org/2000/svg">
                <mask id="withdraw-card-mask" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="2" y="4" width="37" height="32">
                    <path d="M12.1666 10.8333V7.5C12.1666 7.05795 12.3422 6.63403 12.6548 6.32147C12.9673 6.00891 13.3913 5.83333 13.8333 5.83333H35.5C35.942 5.83333 36.3659 6.00891 36.6785 6.32147C36.991 6.63403 37.1666 7.05795 37.1666 7.5V22.5C37.1666 22.942 36.991 23.3659 36.6785 23.6785C36.3659 23.991 35.942 24.1667 35.5 24.1667H33.8333" stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    <path d="M27.1666 15.8333H5.5C4.57953 15.8333 3.83333 16.5796 3.83333 17.5V32.5C3.83333 33.4205 4.57953 34.1667 5.5 34.1667H27.1666C28.0871 34.1667 28.8333 33.4205 28.8333 32.5V17.5C28.8333 16.5796 28.0871 15.8333 27.1666 15.8333Z" fill="white" stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    <path d="M3.83333 23.3333H28.8333" stroke="black" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    <path d="M28.8333 19.1667V29.1667M3.83333 19.1667V29.1667" stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    <path d="M9.66667 28.3333H16.3333M21.3333 28.3333H23" stroke="black" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                </mask>
                <g mask="url(#withdraw-card-mask)">
                    <rect x="0.5" width="40" height="40" fill="currentColor"/>
                </g>
            </svg>
        </span>
    </button>
    <button class="tabs__title {{ $withdrawActiveMethod === 'iban' ? '_tab-active' : '' }}" type="button">
        <span>Withdrawal by IBAN</span>
        <span>
            <svg fill="none" height="40" viewBox="0 0 41 40" width="41" xmlns="http://www.w3.org/2000/svg">
                <path d="M25.5 25V20H30.5V16.6667L37.1667 22.5L30.5 28.3333V25H25.5ZM23.8333 14.5V16.6667H3.83331V14.5L13.8333 8.33333L23.8333 14.5ZM3.83331 28.3333H23.8333V31.6667H3.83331V28.3333ZM12.1666 18.3333H15.5V26.6667H12.1666V18.3333ZM5.5 18.3333H8.83331V26.6667H5.5V18.3333ZM18.8333 18.3333H22.1666V26.6667H18.8333V18.3333Z" fill="currentColor"/>
            </svg>
        </span>
    </button>
    <button class="tabs__title {{ $withdrawActiveMethod === 'crypto' ? '_tab-active' : '' }}" type="button">
        <span>Withdrawal to cryptocash</span>
        <span>
            <svg fill="none" height="40" viewBox="0 0 41 40" width="41" xmlns="http://www.w3.org/2000/svg">
                <path d="M31.75 6.25H28.3617C27.8424 6.25036 27.3394 6.4314 26.939 6.76205C26.5386 7.09271 26.2658 7.55238 26.1672 8.06223C26.0687 8.57208 26.1506 9.10032 26.399 9.55637C26.6473 10.0124 27.0466 10.3678 27.5284 10.5617L30.9684 11.9383C31.4501 12.1322 31.8494 12.4876 32.0978 12.9436C32.3461 13.3997 32.428 13.9279 32.3295 14.4378C32.231 14.9476 31.9581 15.4073 31.5577 15.7379C31.1573 16.0686 30.6543 16.2496 30.135 16.25H29.25M29.25 6.25V5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
                <path d="M30.55 21.1667C32.4003 20.9237 34.1458 20.1677 35.589 18.9844C37.0322 17.8011 38.1155 16.2376 38.7164 14.4708C39.3174 12.7039 39.412 10.8041 38.9895 8.9863C38.5671 7.16847 37.6445 5.50509 36.326 4.18426C35.0075 2.86343 33.3458 1.93785 31.5287 1.51217C29.7116 1.0865 27.8117 1.17772 26.0438 1.77552C24.2758 2.37331 22.7104 3.45384 21.5245 4.8949C20.3387 6.33597 19.5796 8.08009 19.3333 9.93003M26.75 26.25C26.75 22.9348 25.433 19.7554 23.0888 17.4112C20.7446 15.067 17.5652 13.75 14.25 13.75M4.24996 18.75C3.2367 20.1147 2.5124 21.6717 2.12134 23.3258C1.73029 24.9799 1.68068 26.6964 1.97557 28.3703C2.27045 30.0443 2.90362 31.6405 3.83638 33.0614C4.76914 34.4823 5.98189 35.6981 7.40048 36.6343C8.81908 37.5706 10.4137 38.2077 12.0869 38.5068C13.7601 38.8058 15.4767 38.7604 17.1318 38.3735C18.7869 37.9865 20.3457 37.2661 21.7128 36.2562C23.08 35.2463 24.2269 33.9682 25.0833 32.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
            </svg>
        </span>
    </button>
</nav>
<div class="tabs__content" data-tabs-body="">
    <div class="tabs__body" {{ $withdrawActiveMethod !== 'card' ? 'hidden' : '' }}>
        <form action="{{ route('user.withdraw.store') }}" method="POST">
            @csrf
            <input type="hidden" name="method" value="card">
            <div class="field {{ ($withdrawCardErrors && $withdrawCardErrors->has('from_account_id')) ? 'has-error' : '' }}">
                <select name="from_account_id">
                    <option value="main" @selected($modalSelectedAccount === 'main')>{{ $modalMainBalanceLabel }}</option>
                    @foreach($modalAccountsCollection as $account)
                        @php
                            $optionValue = (string) $account->id;
                            $optionLabel = ($account->name ?? __('Account')) . ' (' . ($account->type ?? '-') . ') — ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
                        @endphp
                        <option value="{{ $account->id }}" @selected($modalSelectedAccount === $optionValue)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
                @if($withdrawCardErrors && $withdrawCardErrors->has('from_account_id'))
                    <span class="error-message">{{ $withdrawCardErrors->first('from_account_id') }}</span>
                @endif
            </div>
            <div class="field {{ ($withdrawCardErrors && $withdrawCardErrors->has('card.number')) ? 'has-error' : '' }}">
                <input name="card[number]" placeholder="1111 2222 3333 4444" type="text" inputmode="numeric" value="{{ old('card.number') }}" required>
                @if($withdrawCardErrors && $withdrawCardErrors->has('card.number'))
                    <span class="error-message">{{ $withdrawCardErrors->first('card.number') }}</span>
                @endif
            </div>
            <div class="field {{ ($withdrawCardErrors && $withdrawCardErrors->has('card.holder')) ? 'has-error' : '' }}">
                <input name="card[holder]" placeholder="Fullname card holder" type="text" value="{{ old('card.holder') }}" required>
                @if($withdrawCardErrors && $withdrawCardErrors->has('card.holder'))
                    <span class="error-message">{{ $withdrawCardErrors->first('card.holder') }}</span>
                @endif
            </div>
            <div class="field {{ ($withdrawCardErrors && $withdrawCardErrors->has('amount')) ? 'has-error' : '' }}">
                <input name="amount" placeholder="Amount" type="number" step="0.01" min="1" value="{{ old('amount') }}" required>
                @if($withdrawCardErrors && $withdrawCardErrors->has('amount'))
                    <span class="error-message">{{ $withdrawCardErrors->first('amount') }}</span>
                @endif
            </div>
            <button class="btn btn--md" type="submit">Withdrawal</button>
        </form>
    </div>
    <div class="tabs__body" {{ $withdrawActiveMethod !== 'iban' ? 'hidden' : '' }}>
        <form action="{{ route('user.withdraw.store') }}" method="POST">
            @csrf
            <input type="hidden" name="method" value="iban">
            <div class="field {{ ($withdrawIbanErrors && $withdrawIbanErrors->has('from_account_id')) ? 'has-error' : '' }}">
                <select name="from_account_id">
                    <option value="main" @selected($modalSelectedAccount === 'main')>{{ $modalMainBalanceLabel }}</option>
                    @foreach($modalAccountsCollection as $account)
                        @php
                            $optionValue = (string) $account->id;
                            $optionLabel = ($account->name ?? __('Account')) . ' (' . ($account->type ?? '-') . ') — ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
                        @endphp
                        <option value="{{ $account->id }}" @selected($modalSelectedAccount === $optionValue)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
                @if($withdrawIbanErrors && $withdrawIbanErrors->has('from_account_id'))
                    <span class="error-message">{{ $withdrawIbanErrors->first('from_account_id') }}</span>
                @endif
            </div>
            <div class="field {{ ($withdrawIbanErrors && $withdrawIbanErrors->has('iban.iban')) ? 'has-error' : '' }}">
                <input name="iban[iban]" placeholder="Enter IBAN" type="text" value="{{ old('iban.iban') }}" required>
                @if($withdrawIbanErrors && $withdrawIbanErrors->has('iban.iban'))
                    <span class="error-message">{{ $withdrawIbanErrors->first('iban.iban') }}</span>
                @endif
            </div>
            <div class="field {{ ($withdrawIbanErrors && $withdrawIbanErrors->has('iban.bic')) ? 'has-error' : '' }}">
                <input name="iban[bic]" placeholder="BIC code" type="text" value="{{ old('iban.bic') }}" required>
                @if($withdrawIbanErrors && $withdrawIbanErrors->has('iban.bic'))
                    <span class="error-message">{{ $withdrawIbanErrors->first('iban.bic') }}</span>
                @endif
            </div>
            <div class="field {{ ($withdrawIbanErrors && $withdrawIbanErrors->has('iban.holder')) ? 'has-error' : '' }}">
                <input name="iban[holder]" placeholder="Fullname bank account holder" type="text" value="{{ old('iban.holder') }}" required>
                @if($withdrawIbanErrors && $withdrawIbanErrors->has('iban.holder'))
                    <span class="error-message">{{ $withdrawIbanErrors->first('iban.holder') }}</span>
                @endif
            </div>
            <div class="field {{ ($withdrawIbanErrors && $withdrawIbanErrors->has('iban.country')) ? 'has-error' : '' }}">
                <input name="iban[country]" placeholder="Country" type="text" value="{{ old('iban.country') }}" required>
                @if($withdrawIbanErrors && $withdrawIbanErrors->has('iban.country'))
                    <span class="error-message">{{ $withdrawIbanErrors->first('iban.country') }}</span>
                @endif
            </div>
            <div class="field {{ ($withdrawIbanErrors && $withdrawIbanErrors->has('iban.bank')) ? 'has-error' : '' }}">
                <input name="iban[bank]" placeholder="Name of the bank" type="text" value="{{ old('iban.bank') }}" required>
                @if($withdrawIbanErrors && $withdrawIbanErrors->has('iban.bank'))
                    <span class="error-message">{{ $withdrawIbanErrors->first('iban.bank') }}</span>
                @endif
            </div>
            <div class="field {{ ($withdrawIbanErrors && $withdrawIbanErrors->has('amount')) ? 'has-error' : '' }}">
                <input name="amount" placeholder="Amount" type="number" step="0.01" min="1" value="{{ old('amount') }}" required>
                @if($withdrawIbanErrors && $withdrawIbanErrors->has('amount'))
                    <span class="error-message">{{ $withdrawIbanErrors->first('amount') }}</span>
                @endif
            </div>
            <button class="btn btn--md" type="submit">Withdrawal</button>
        </form>
    </div>
    <div class="tabs__body" {{ $withdrawActiveMethod !== 'crypto' ? 'hidden' : '' }}>
        <button class="btn-toggle-crypto-window" style="padding: 0.5rem; border-radius: 0.25rem; border: 1px solid #63616C; font-size: 0.625rem; margin-bottom: 0.8rem; margin-inline: auto;" type="button">click
								show crypto type window</button>
        <div class="type-crypto-window">
            <img alt="attention" src="{{ asset('personal-acc/img/icons/attention.svg') }}"/>
            <p>At first you need to change the type of account to "Crypto" type</p>
        </div>
        <form action="{{ route('user.withdraw.store') }}" method="POST" class="form-crypto">
            @csrf
            <input type="hidden" name="method" value="crypto">
            <div class="field {{ ($withdrawCryptoErrors && $withdrawCryptoErrors->has('from_account_id')) ? 'has-error' : '' }}">
                <select name="from_account_id">
                    <option value="main" @selected($modalSelectedAccount === 'main')>{{ $modalMainBalanceLabel }}</option>
                    @foreach($modalAccountsCollection as $account)
                        @php
                            $optionValue = (string) $account->id;
                            $optionLabel = ($account->name ?? __('Account')) . ' (' . ($account->type ?? '-') . ') — ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
                        @endphp
                        <option value="{{ $account->id }}" @selected($modalSelectedAccount === $optionValue)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
                @if($withdrawCryptoErrors && $withdrawCryptoErrors->has('from_account_id'))
                    <span class="error-message">{{ $withdrawCryptoErrors->first('from_account_id') }}</span>
                @endif
            </div>
            <div class="field {{ ($withdrawCryptoErrors && $withdrawCryptoErrors->has('crypto.address')) ? 'has-error' : '' }}">
                <input name="crypto[address]" placeholder="Deposit address" type="text" value="{{ old('crypto.address') }}" required>
                @if($withdrawCryptoErrors && $withdrawCryptoErrors->has('crypto.address'))
                    <span class="error-message">{{ $withdrawCryptoErrors->first('crypto.address') }}</span>
                @endif
            </div>
            <div class="field {{ ($withdrawCryptoErrors && $withdrawCryptoErrors->has('crypto.network')) ? 'has-error' : '' }}">
                <input name="crypto[network]" placeholder="Network" type="text" value="{{ old('crypto.network') }}" required>
                @if($withdrawCryptoErrors && $withdrawCryptoErrors->has('crypto.network'))
                    <span class="error-message">{{ $withdrawCryptoErrors->first('crypto.network') }}</span>
                @endif
            </div>
            <div class="field {{ ($withdrawCryptoErrors && $withdrawCryptoErrors->has('crypto.coin')) ? 'has-error' : '' }}">
                <input name="crypto[coin]" placeholder="Coin" type="text" value="{{ old('crypto.coin') }}" required>
                @if($withdrawCryptoErrors && $withdrawCryptoErrors->has('crypto.coin'))
                    <span class="error-message">{{ $withdrawCryptoErrors->first('crypto.coin') }}</span>
                @endif
            </div>
            <div class="field {{ ($withdrawCryptoErrors && $withdrawCryptoErrors->has('amount')) ? 'has-error' : '' }}">
                <input name="amount" placeholder="Amount" type="number" step="0.01" min="1" value="{{ old('amount') }}" required>
                @if($withdrawCryptoErrors && $withdrawCryptoErrors->has('amount'))
                    <span class="error-message">{{ $withdrawCryptoErrors->first('amount') }}</span>
                @endif
            </div>
            <button class="btn btn--md" type="submit">Withdrawal</button>
        </form>
    
