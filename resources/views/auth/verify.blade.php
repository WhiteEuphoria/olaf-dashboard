@extends('layouts.app')
@section('title', 'Подтверждение')
@section('content')

<div class="wrapper">
<main class="page">
<div class="auth-page">
<div class="auth">
<div class="logo"><img alt="logo" src="{{ asset('personal-acc/img/logo.svg') }}"/></div>
<div class="loading">
<div class="loading__circle">
<svg viewbox="0 0 120 120">
<defs>
<lineargradient id="gradient" x1="0" x2="1" y1="1" y2="0">
<stop offset="0%" stop-color="#0B69B7"></stop>
<stop offset="100%" stop-color="#052E51"></stop>
</lineargradient>
</defs>
<circle class="bg" cx="60" cy="60" r="54"></circle>
<circle class="progress" cx="60" cy="60" r="54" stroke-dasharray="339.2920065877" stroke-dashoffset="339.2920065877"></circle>
<!-- <circle class="progress" cx="60" cy="60" r="54" /> -->
</svg>
<span class="loading__percent">56%</span>
</div>
</div>
<div class="verify-text">
<p>The data verification procedure is underway. <br/>
							It may take from 10 minutes to 3 hours. <br/>
							Please wait.</p>
</div>
</div>
</div>
</main>
</div>
@endsection
