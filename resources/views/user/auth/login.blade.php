@extends('layouts.app')

@section('title', 'Вход в личный кабинет')

@section('content')
<div class="wrapper">
    <main class="page">
        <div class="auth-page">
            <div class="auth">
                <div class="logo">
                    <img src="{{ asset('personal-acc/img/logo.svg') }}" alt="logo">
                </div>
                <form class="auth-form" method="POST" action="{{ route('user.login.attempt') }}">
                    @csrf

                    <div class="field">
                        <input type="email" name="email" placeholder="E-mail" value="{{ old('email') }}" required autofocus autocomplete="email">
                        @error('email')
                            <span class="field__error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <div class="field__wrapper">
                            <input type="password" name="password" placeholder="Пароль" required autocomplete="current-password">
                            <button class="field__icon" type="button" data-password-toggle>
                                <img src="{{ asset('personal-acc/img/icons/eye.svg') }}" alt="eye">
                            </button>
                        </div>
                        @error('password')
                            <span class="field__error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field" style="display:flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                        <label class="checkbox__label" style="gap:0.5rem; margin:0;">
                            <input class="checkbox__input" type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                            <span class="checkbox__text">Запомнить меня</span>
                        </label>
                        <a href="{{ route('user.register') }}" style="font-size:0.875rem; font-weight:600; color:#0B69B7;">Регистрация</a>
                    </div>

                    <button class="btn" type="submit">Войти</button>

                    @if (session('status'))
                        <div class="field__status">{{ session('status') }}</div>
                    @endif
                </form>
            </div>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.querySelector('[data-password-toggle]');
        if (!toggle) {
            return;
        }
        const input = toggle.previousElementSibling;
        toggle.addEventListener('click', function () {
            if (!input) {
                return;
            }
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            toggle.classList.toggle('is-active', type === 'text');
        });
    });
</script>
@endpush
