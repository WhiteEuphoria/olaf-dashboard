@php
    $violationErrors = $violationErrors ?? ($errors->violation ?? null);
    $violationStatus = $violationStatus ?? session('violation_status');
    $violationShouldOpen = $violationShouldOpen ?? (bool) ($violationStatus || ($violationErrors && $violationErrors->any()));
@endphp

<div aria-hidden="true" class="popup popup--md" id="violation">
    <div class="popup__wrapper">
        <div class="popup__content">
            <button class="popup__close" data-close type="button">
                <svg fill="none" height="20" viewBox="0 0 20 20" width="20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L19 19M19 1L1 19" stroke="black" stroke-linecap="round" stroke-width="2"></path>
                </svg>
            </button>
            <div class="modal-content">
                <div class="modal-content__top">
                    <div class="logo"><img alt="logo" src="{{ asset('personal-acc/img/logo.svg') }}"></div>
                    <div class="modal-content__text">
                        <p>Describe your complaint</p>
                    </div>
                </div>
                <div class="modal-content__body">
                    @if($violationStatus)
                        <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 0.75rem; background: #ecfdf5; color: #047857; font-weight: 600; text-align: center;">
                            {{ $violationStatus }}
                        </div>
                    @endif
                    <form action="{{ route('user.violation.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="field {{ ($violationErrors && $violationErrors->has('details')) ? 'has-error' : '' }}">
                            <textarea name="details" placeholder="Write here..." rows="6" required>{{ old('details') }}</textarea>
                            @if($violationErrors && $violationErrors->has('details'))
                                <span class="error-message">{{ $violationErrors->first('details') }}</span>
                            @endif
                        </div>
                        <div class="field field--upload" data-file-upload>
                            <label class="modal-content__file">
                                <input hidden type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.webp,.gif,.doc,.docx" data-file-upload-input data-max-files="5">
                                <span data-file-upload-label>{{ __('Attach files (up to 5)') }}</span>
                            </label>
                            <ul class="modal-content__file-list" data-file-upload-list></ul>
                            @php($attachmentError = $violationErrors ? ($violationErrors->first('attachments') ?: $violationErrors->first('attachments.*')) : null)
                            @if($attachmentError)
                                <span class="error-message">{{ $attachmentError }}</span>
                            @endif
                        </div>
                        <button class="btn" type="submit">Send</button>
                    </form>
</div>
</div>
</div>
</div>
</div>
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (@json((bool) $violationShouldOpen)) {
                document.dispatchEvent(new CustomEvent('openPopup', { detail: '#violation' }));
            }
        });
    </script>
@endpush
