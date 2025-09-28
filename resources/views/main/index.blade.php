@extends('layouts.app')
@section('title', 'Главная')
@section('content')

<div class="rootpage">
<h1>Адаптивная верстка «personal-acc»</h1>
<p><b>Описание:</b> Адаптиваня верстка. Анимации и проработка адаптива. Грамотная и чистая верстка</p>
<span class="descpro">Ниже вы найдете ссылки на все страницы. Для просмотра кликайте на нужную страничку
			(откроется
			в новом окне)</span>
<div class="rootpage__info">
</div>
<h2>Cтраницы:</h2>
<ol class="rootpage__list">
<li><a href="{{ route('login') }}" target="_blank">Логин</a></li>
<li><a href="{{ route('register') }}" target="_blank">Регистрация</a></li>
<li><a href="{{ route('enter') }}" target="_blank">Прикрепите ваши документы для проверки</a></li>
<li><a href="{{ route('verification.notice') }}" target="_blank">Верификация</a></li>
<li><a href="{{ route('user.dashboard') }}" target="_blank">Личный кабинет</a></li>
<li><a href="{{ route('user.violation') }}" target="_blank">Сообщить о наружении(моб)</a></li>
<li><a href="{{ route('user.withdraw') }}" target="_blank">Вывод(моб)</a></li>
<li><a href="{{ route('admin.login') }}" target="_blank">Вход (Админка)</a></li>
<li><a href="{{ route('admin.dashboard') }}" target="_blank">Админка</a></li>
</ol>
<!-- <h2>Страницы в разработке:</h2>
    <ol class="rootpage__list">

    </ol> -->
<h2>Модальные окна:</h2>
<ol class="rootpage__list">
<!-- <li><a target="_blank" href="modal.html">Модальные окна</a></li> -->
</ol>
<!-- -->
</div>

@endsection
