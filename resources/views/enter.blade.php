@extends('layouts.app')
@section('title', 'Enter')
@section('content')

<div class="wrapper">
<main class="page">
<div class="auth-page">
<div class="auth">
<div class="logo"><img alt="logo" src="{{ asset('personal-acc/img/logo.svg') }}"/></div>
<form class="enter-block" method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
@csrf

<div class="enter-block__title">Attach your documents for verification</div>
<div class="enter-block__file">
<label class="file-btn">
<input hidden="" type="file" name="document" accept=".png,.jpg,.jpeg,.pdf" required/>
<span class="file-btn__icon"><img alt="download" src="{{ asset('personal-acc/img/icons/download.svg') }}"/></span>
<span>Upload file</span>
</label>
@error('document')
<span class="field__error">{{ $message }}</span>
@enderror
</div>
<div class="field">
<textarea placeholder="Write here..." name="comment" rows="4">{{ old('comment') }}</textarea>
</div>
<div class="enter-block__info">
<div class="enter-block__info-item">
<span>Allowed format</span>
<br/>
								PNG, JPG, JPEG and PDF
							</div>
<div class="enter-block__info-item">
<span>Max file size</span>
<br/>
								10MB
							</div>
</div>
@error('comment')
<span class="field__error">{{ $message }}</span>
@enderror
<button class="btn btn--light" type="submit">Send</button>
@if (session('status'))
<div class="field__status">{{ session('status') }}</div>
@endif
</form>
</div>
</div>
</main>
</div>

@endsection
