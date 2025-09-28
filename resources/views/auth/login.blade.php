@extends('layouts.app')
@section('title', 'Вход')
@section('content')

<div class="wrapper">
<main class="page">
<div class="auth-page">
<div class="auth">
<div class="logo"><img alt="logo" src="{{ asset('personal-acc/img/logo.svg') }}"/></div>
<form class="auth-form" method="POST" action="{{ route('login.attempt') }}">
@csrf

<div class="field">
<input placeholder="E-mail" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"/>
@error('email')
<span class="field__error">{{ $message }}</span>
@enderror
</div>
<div class="field">
<div class="field__wrapper">
<input placeholder="Password" type="password" name="password" required autocomplete="current-password"/>
<button class="field__icon" type="button">
<img alt="eye" src="{{ asset('personal-acc/img/icons/eye.svg') }}"/>
</button>
</div>
@error('password')
<span class="field__error">{{ $message }}</span>
@enderror
</div>
<button class="btn" type="submit">Login</button>
@if (session('status'))
<div class="field__status">{{ session('status') }}</div>
@endif
</form>
</div>
</div>
</main>
</div>

@endsection
