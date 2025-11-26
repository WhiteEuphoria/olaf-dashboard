@extends('layouts.admin')
@section('title', 'Админка')
@section('content')
@php
    $formatMoney = static function ($amount, ?string $currency): string {
        if ($amount === null) {
            return '—';
        }

        $formatted = number_format((float) $amount, 2, '.', ' ');
        $currencyCode = $currency ?: (config('currencies.default') ?? 'EUR');

        return $formatted . ' ' . $currencyCode;
    };

    $formatDate = static function ($value, string $format = 'd.m.Y H:i'): string {
        if (! $value) {
            return '—';
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format($format);
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $documentsDisk = \App\Models\Document::storageDisk();

    $statusBadgeStyle = static function (?string $status): string {
        $normalized = strtolower((string) $status);
        if ($normalized === 'active') {
            $normalized = 'approved';
        }

        return match ($normalized) {
            'approved', 'verified', 'verificated', 'success', 'completed' => 'display:inline-flex; align-items:center; padding:0.25rem 0.75rem; border-radius:9999px; font-weight:600; font-size:0.75rem; background:#dcfce7; color:#166534; text-transform:uppercase;',
            'pending', 'hold', 'processing' => 'display:inline-flex; align-items:center; padding:0.25rem 0.75rem; border-radius:9999px; font-weight:600; font-size:0.75rem; background:#fef3c7; color:#92400e; text-transform:uppercase;',
            'blocked', 'rejected', 'failed', 'declined', 'canceled' => 'display:inline-flex; align-items:center; padding:0.25rem 0.75rem; border-radius:9999px; font-weight:600; font-size:0.75rem; background:#fee2e2; color:#b91c1c; text-transform:uppercase;',
            default => 'display:inline-flex; align-items:center; padding:0.25rem 0.75rem; border-radius:9999px; font-weight:600; font-size:0.75rem; background:#e2e8f0; color:#334155; text-transform:uppercase;',
        };
    };

    $pendingClientOptions = collect($pendingClientOptions ?? []);
    $approvedClientIds = collect($approvedClientIds ?? []);
    $selectedUserCurrency = $selectedUser?->currency ?? (config('currencies.default') ?? 'EUR');
    $hasClients = $clientOptions->isNotEmpty();
    $hasPendingClients = $pendingClientOptions->isNotEmpty();
    $hasAnyClients = $hasClients;
    $withdrawalTab = old('method', 'card');
    $createWithdrawalAccount = old('from_account_id');
    if ($createWithdrawalAccount === null || $createWithdrawalAccount === '') {
        $createWithdrawalAccount = 'main';
    } else {
        $createWithdrawalAccount = (string) $createWithdrawalAccount;
    }
    $supportErrors = $errors->support ?? null;
    $fraudErrors = $errors->fraud ?? null;
    $withdrawalErrors = $errors->withdrawal ?? null;
    $accountErrors = $errors->account ?? null;
    $accountEditErrors = $errors->account_edit ?? null;
    $userErrors = $errors->user ?? null;
    $transactionErrors = $errors->transaction ?? null;
    $withdrawalEditErrors = $errors->withdrawal_edit ?? null;
    $documentErrors = $errors->document ?? null;
    $documentCreateErrors = $errors->document_create ?? null;
    $fraudEditErrors = $errors->fraud_edit ?? null;
    $withdrawalHasComment = Schema::hasColumn('withdrawals', 'comment');
    $userEditActive = $userErrors && $userErrors->any();
    $accountEditTargetId = (int) old('editing_account_id', $selectedAccount?->id ?? 0);
    $accountEditActive = $accountEditErrors && $accountEditErrors->any() && $selectedAccount && $accountEditTargetId === $selectedAccount->id;
    $selectedUserIsPending = $selectedUserIsPending ?? false;
    $isDocumentImage = static function (?string $path): bool {
        if (! $path) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
    };
    $withdrawalMethods = [
        'card' => 'Card',
        'bank' => 'Bank',
        'crypto' => 'Crypto',
    ];
    $documentStatusOptions = $documentStatusOptions ?? [];
    $withdrawalRequisiteLabels = [
        'card_number' => 'Номер карты',
        'number' => 'Номер карты',
        'card_holder' => 'Держатель карты',
        'holder' => 'Держатель карты',
        'iban' => 'IBAN',
        'bic' => 'BIC / SWIFT',
        'swift' => 'BIC / SWIFT',
        'bank_name' => 'Банк',
        'bank' => 'Банк',
        'account_holder' => 'Владелец счёта',
        'holder_name' => 'Владелец счёта',
        'recipient_name' => 'Получатель',
        'bank_account' => 'Номер счёта',
        'account' => 'Номер счёта',
        'country' => 'Страна',
        'currency' => 'Валюта',
        'crypto_address' => 'Crypto address',
        'address' => 'Crypto address',
        'crypto_network' => 'Crypto network',
        'network' => 'Crypto network',
        'crypto_coin' => 'Crypto coin',
        'coin' => 'Crypto coin',
        'pan' => 'PAN',
        'masked' => 'Masked',
        'last4' => 'Последние 4',
        'exp_month' => 'Месяц',
        'exp_year' => 'Год',
        'cvc' => 'CVC',
        'cvc_provided' => 'CVC указан',
        'type' => 'Тип',
    ];
    $mainBalanceOptionLabel = $mainBalanceOptionLabel
        ?? ($selectedUser
            ? __('Main balance') . ' — ' . ($selectedUser->currency ?? config('currencies.default')) . ' ' . number_format((float) $selectedUser->main_balance, 2, '.', ' ')
            : __('Main balance'));
    $selectedAccountValue = $selectedAccountValue ?? ($selectedAccount ? (string) $selectedAccount->id : 'main');
    $selectedAccountValue = (string) $selectedAccountValue;
    $parseWithdrawalRequisites = static function ($withdrawal) {
        if (! $withdrawal?->requisites) {
            return collect();
        }

        $decoded = json_decode($withdrawal->requisites, true);

        return collect(is_array($decoded) ? $decoded : []);
    };
    $requisiteLabelFor = static function (string $key) use ($withdrawalRequisiteLabels) {
        return $withdrawalRequisiteLabels[$key] ?? \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', $key));
    };
    $transactionDateParts = static function ($model): array {
        $date = $model?->created_at;

        if ($date instanceof \Carbon\CarbonInterface) {
            return [$date->format('d/m/y'), $date->format('H:i:s')];
        }

        if ($date) {
            try {
                $carbon = \Illuminate\Support\Carbon::parse($date);

                return [$carbon->format('d/m/y'), $carbon->format('H:i:s')];
            } catch (\Throwable $e) {
                // ignore parsing issues and return fallback
            }
        }

        return ['—', '—'];
    };
    $statusChipClassFor = static function (?string $status): string {
        return match (strtolower((string) $status)) {
            'approved', 'success', 'completed' => 'user-table__status user-table__status--success',
            'blocked', 'failed', 'declined', 'rejected', 'canceled' => 'user-table__status user-table__status--block',
            default => 'user-table__status user-table__status--hold',
        };
    };
    $statusChipLabelFor = static function (?string $status): string {
        $status = trim((string) $status);

        return $status !== '' ? \Illuminate\Support\Str::upper($status) : 'PENDING';
    };
    $maskValue = static function (?string $value, int $prefix = 4, int $suffix = 4): string {
        $value = trim((string) $value);

        if ($value === '') {
            return '—';
        }

        $length = \Illuminate\Support\Str::length($value);

        if ($length <= $prefix + $suffix + 3) {
            return $value;
        }

        return \Illuminate\Support\Str::substr($value, 0, $prefix)
            . ' ... '
            . \Illuminate\Support\Str::substr($value, -$suffix);
    };
    $transactionOptions = ($transactionOptions ?? collect())->filter();
    $withdrawalOptions = ($withdrawalOptions ?? collect())->filter();
    $selectedTransaction = $selectedTransaction ?? null;
    $selectedWithdrawal = $selectedWithdrawal ?? null;
    $transactionEditErrors = $errors->transaction_edit ?? null;
    $transactionEditActive = $selectedTransaction
        && (int) old('editing_transaction_id', 0) === $selectedTransaction->id
        && $transactionEditErrors
        && $transactionEditErrors->any();
    $withdrawalEditErrors = $withdrawalEditErrors ?? ($errors->withdrawal_edit ?? null);
    $withdrawalEditActive = $selectedWithdrawal
        && (int) old('editing_withdrawal_id', 0) === $selectedWithdrawal->id
        && $withdrawalEditErrors
        && $withdrawalEditErrors->any();
    $transactionTypeOptions = [
        'classic' => 'Classic',
        'fast' => 'Fast',
        'conversion' => 'Conversion',
        'hold' => 'Hold',
    ];
    $transactionStatusOptions = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'blocked' => 'Blocked',
        'hold' => 'Hold',
    ];
    $withdrawalStatusOptions = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];
@endphp

@push('styles')
    <style>
        .admin-panel__select--active {
            border-color: #1d4ed8;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }
        .admin-panel__select--active:focus {
            outline: none;
            border-color: #1d4ed8;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.35);
        }
        .admin-panel__doc-thumb {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 96px;
            height: 96px;
            border-radius: 1rem;
            overflow: hidden;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            padding: 0;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .admin-panel__doc-thumb:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
        }
        .admin-panel__doc-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .admin-panel__doc-thumb--file span,
        .admin-panel__doc-thumb--empty span {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #1f2937;
            text-align: center;
            padding: 0.25rem;
        }
        .admin-panel__doc-thumb--empty {
            background: #fef2f2;
            border-color: #fecaca;
            cursor: not-allowed;
        }
        .admin-panel__doc-thumb:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
        }
    </style>
@endpush

@if($fraudClaims->isNotEmpty())
    @foreach($fraudClaims as $claim)
        @php
            $isCurrentFraudEdit = (int) old('editing_fraud_claim_id', 0) === $claim->id;
        @endphp
        <div aria-hidden="true" class="popup popup--sm" id="edit-fraud-claim-{{ $claim->id }}">
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
                                <p>Редактировать сообщение о нарушении</p>
                            </div>
                        </div>
                        <div class="modal-content__body">
                            @if($fraudEditErrors && $isCurrentFraudEdit && $fraudEditErrors->any())
                                <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fef2f2; color: #b91c1c; border-radius: 0.75rem;">{{ $fraudEditErrors->first() }}</div>
                            @endif
                            <form method="POST" action="{{ route('admin.dashboard.fraud-claims.update', $claim) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="editing_fraud_claim_id" value="{{ $claim->id }}">
                                <div class="field">
                                    <textarea name="details" placeholder="Описание" rows="6" required>{{ $isCurrentFraudEdit ? old('details') : $claim->details }}</textarea>
                                    @if($isCurrentFraudEdit)
                                        @error('details', 'fraud_edit')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="field">
                                    <select name="status" required>
                                        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($isCurrentFraudEdit ? old('status') : $claim->status) === $value || ($claim->status === 'В рассмотрении' && $value === 'pending') || ($claim->status === 'Одобрено' && $value === 'approved') || ($claim->status === 'Отклонено' && $value === 'rejected'))>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @if($isCurrentFraudEdit)
                                        @error('status', 'fraud_edit')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                @php
                                    $attachments = $claim->attachments;
                                @endphp
                                @if($attachments->isNotEmpty())
                                    <div class="admin-fraud-attachments">
                                        <div class="admin-fraud-attachments__title">Текущие вложения</div>
                                        <ul class="admin-fraud-attachments__list">
                                            @foreach($attachments as $attachment)
                                                @php
                                                    $removeAttachmentId = 'fraud-attachment-' . $claim->id . '-' . $attachment->id;
                                                    $attachmentExists = $attachment->existsInStorage();
                                                    $attachmentPreviewUrl = $attachment->previewUrl($claim);
                                                    $attachmentDownloadUrl = $attachment->downloadUrl($claim);
                                                @endphp
                                                <li>
                                                    @if($attachmentPreviewUrl)
                                                        <button type="button" class="admin-panel__doc-thumb" data-popup="#preview-fraud-attachment-{{ $attachment->id }}" title="Открыть превью">
                                                            <img src="{{ $attachmentPreviewUrl }}" alt="{{ $attachment->original_name }}">
                                                        </button>
                                                    @elseif($attachmentExists)
                                                        <a class="admin-panel__doc-thumb admin-panel__doc-thumb--file" href="{{ $attachmentDownloadUrl }}" target="_blank" rel="noopener" title="Скачать файл">
                                                            <span>{{ $attachment->extensionLabel() }}</span>
                                                        </a>
                                                    @else
                                                        <div class="admin-panel__doc-thumb admin-panel__doc-thumb--empty" title="Файл недоступен">
                                                            <span>Нет файла</span>
                                                        </div>
                                                    @endif
                                                    <div class="admin-fraud-attachments__meta">
                                                        <span>{{ $attachment->original_name ?: basename($attachment->path) }}</span>
                                                        @if($attachmentDownloadUrl)
                                                            <a href="{{ $attachmentDownloadUrl }}" style="font-size:0.75rem; color:#0b69b7;" target="_blank" rel="noopener">Скачать</a>
                                                        @endif
                                                    </div>
                                                    <div class="checkbox">
                                                        <input class="checkbox__input" type="checkbox" id="{{ $removeAttachmentId }}" name="remove_attachments[]" value="{{ $attachment->id }}">
                                                        <label class="checkbox__label" for="{{ $removeAttachmentId }}"><span class="checkbox__text">Удалить</span></label>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <div class="field field--upload" data-file-upload>
                                    <label class="modal-content__file">
                                <input hidden type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.webp,.gif,.doc,.docx" data-file-upload-input data-max-files="5">
                                        <span data-file-upload-label>Добавить файлы (до 5)</span>
                                    </label>
                                    <ul class="modal-content__file-list" data-file-upload-list></ul>
                                    @if($isCurrentFraudEdit)
                                        @php
                                            $attachmentError = $fraudEditErrors->first('attachments')
                                                ?? $fraudEditErrors->first('attachments.*')
                                                ?? $fraudEditErrors->first('remove_attachments')
                                                ?? $fraudEditErrors->first('remove_attachments.*');
                                        @endphp
                                        @if($attachmentError)
                                            <span class="error-message">{{ $attachmentError }}</span>
                                        @endif
                                    @endif
                                </div>
                                <button class="btn btn--md" type="submit">Сохранить</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
@if($fraudClaims->isNotEmpty())
    @foreach($fraudClaims as $claim)
        @foreach($claim->attachments as $attachment)
            @php
                $disk = $attachment->disk ?: 'public';
                $attachmentExists = $attachment->path && \Illuminate\Support\Facades\Storage::disk($disk)->exists($attachment->path);
                $attachmentIsImage = $attachmentExists && $isDocumentImage($attachment->path);
                $attachmentPreviewUrl = $attachmentIsImage ? route('admin.dashboard.fraud-claims.attachments.preview', [$claim, $attachment]) : null;
            @endphp
            @if($attachmentIsImage && $attachmentPreviewUrl)
                <div aria-hidden="true" class="popup popup--md" id="preview-fraud-attachment-{{ $attachment->id }}">
                    <div class="popup__wrapper">
                        <div class="popup__content">
                            <button class="popup__close" data-close type="button">
                                <svg fill="none" height="20" viewBox="0 0 20 20" width="20" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 1L19 19M19 1L1 19" stroke="black" stroke-linecap="round" stroke-width="2"></path>
                                </svg>
                            </button>
                            <div class="modal-content" style="max-width:48rem; padding:0; overflow:hidden; background:#0f172a;">
                                <img src="{{ $attachmentPreviewUrl }}" alt="{{ $attachment->original_name }}" style="width:100%; height:auto; display:block;">
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endforeach
@endif
@if($documents->isNotEmpty())
    @foreach($documents as $document)
        @php
            $isCurrentDocumentEdit = (int) old('editing_document_id', 0) === $document->id;
            $documentHasFile = false;

            if ($document->path) {
                $documentHasFile = \Illuminate\Support\Facades\Storage::disk($documentsDisk)->exists($document->path);

                if (! $documentHasFile) {
                    $symlinkedPath = public_path('storage/' . ltrim($document->path, '/'));
                    if ($symlinkedPath && file_exists($symlinkedPath)) {
                        $documentHasFile = true;
                    }
                }
            }

            $documentPreviewUrl = $documentHasFile ? route('admin.dashboard.documents.preview', $document) : null;
        @endphp
        <div aria-hidden="true" class="popup popup--sm" id="edit-document-{{ $document->id }}">
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
                                <p>Обновить документ</p>
                            </div>
                        </div>
                        <div class="modal-content__body">
                            @if($documentErrors && $isCurrentDocumentEdit && $documentErrors->any())
                                <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fef2f2; color: #b91c1c; border-radius: 0.75rem;">{{ $documentErrors->first() }}</div>
                            @endif
                            <form method="POST" action="{{ route('admin.dashboard.documents.update', $document) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="editing_document_id" value="{{ $document->id }}">
                                <div class="field">
                                    <input name="file" type="file" accept="image/*,application/pdf">
                                    @if($isCurrentDocumentEdit)
                                        @error('file', 'document')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="field">
                                    <input name="document_type" type="text" value="{{ old('document_type', $document->document_type) }}" placeholder="Тип документа">
                                    @if($isCurrentDocumentEdit)
                                        @error('document_type', 'document')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="field">
                                    <select name="status" data-native>
                                        <option value="">Выберите статус</option>
                                        @foreach($documentStatusOptions as $code => $label)
                                            <option value="{{ $code }}" @selected(old('status', $document->status) === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @if($isCurrentDocumentEdit)
                                        @error('status', 'document')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                @if($documentPreviewUrl)
                                    <p style="font-size: 0.8rem; color: #63616C; margin-bottom: 1rem;">Текущий файл: <a href="{{ $documentPreviewUrl }}" target="_blank" rel="noopener">{{ $document->original_name }}</a></p>
                                @endif
                                <button class="btn btn--md" type="submit">Сохранить</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
@if($documents->isNotEmpty())
    @foreach($documents as $document)
        @php
            $documentExists = false;
            $documentPreviewPath = $document->path;

            if ($documentPreviewPath) {
                $documentExists = \Illuminate\Support\Facades\Storage::disk($documentsDisk)->exists($documentPreviewPath);

                if (! $documentExists) {
                    $symlinkedPath = public_path('storage/' . ltrim($documentPreviewPath, '/'));
                    if ($symlinkedPath && file_exists($symlinkedPath)) {
                        $documentExists = true;
                    }
                }
            }

            $documentPreviewUrl = $documentExists ? route('admin.dashboard.documents.preview', $document) : null;
            $documentIsImage = $documentPreviewUrl && $isDocumentImage($documentPreviewPath);
        @endphp
        @if($documentIsImage && $documentPreviewUrl)
            <div aria-hidden="true" class="popup popup--md" id="preview-document-{{ $document->id }}">
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
                                    <p>{{ $document->original_name }}</p>
                                    <span style="display:block; font-size:0.85rem; color:#63616C;">{{ $formatDate($document->created_at, 'd.m.Y H:i') }}</span>
                                </div>
                            </div>
                            <div class="modal-content__body">
                                <div style="border-radius: 1rem; overflow: hidden; background: #0f172a;">
                                    <img src="{{ $documentPreviewUrl }}" alt="{{ $document->original_name }}" style="display:block; width:100%; max-height:70vh; object-fit:contain; background:#0f172a;">
                                </div>
                                <div style="margin-top: 1rem; display:flex; gap:0.75rem; flex-wrap:wrap;">
                                    <a href="{{ $documentPreviewUrl }}" target="_blank" rel="noopener" class="btn btn--md">Открыть в новой вкладке</a>
                                    <button class="btn btn--light" type="button" data-close>Закрыть</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endif
@if($withdrawals->isNotEmpty())
    @foreach($withdrawals as $withdrawal)
        @php
            $isCurrentWithdrawalEdit = (int) old('editing_withdrawal_id', 0) === $withdrawal->id;
        @endphp
        <div aria-hidden="true" class="popup popup--sm" id="edit-withdrawal-{{ $withdrawal->id }}">
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
                                <p>Редактировать заявку</p>
                            </div>
                        </div>
                        <div class="modal-content__body">
                            @if($withdrawalEditErrors && $isCurrentWithdrawalEdit && $withdrawalEditErrors->any())
                                <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fef2f2; color: #b91c1c; border-radius: 0.75rem;">{{ $withdrawalEditErrors->first() }}</div>
                            @endif
                            <form method="POST" action="{{ route('admin.dashboard.withdrawals.update', $withdrawal) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="editing_withdrawal_id" value="{{ $withdrawal->id }}">
                                @php
                                    $modalWithdrawalAccount = $isCurrentWithdrawalEdit ? old('from_account_id') : $withdrawal->from_account_id;
                                    if ($modalWithdrawalAccount === null || $modalWithdrawalAccount === '') {
                                        $modalWithdrawalAccount = $withdrawal->from_account_id ? (string) $withdrawal->from_account_id : 'main';
                                    } else {
                                        $modalWithdrawalAccount = (string) $modalWithdrawalAccount;
                                    }
                                @endphp
                                <div class="field">
                                    <label class="field__label" for="withdrawal-method-{{ $withdrawal->id }}">Метод</label>
                                    <select id="withdrawal-method-{{ $withdrawal->id }}" name="method" required>
                                        @foreach($withdrawalMethods as $value => $label)
                                            <option value="{{ $value }}" @selected(($isCurrentWithdrawalEdit ? old('method') : $withdrawal->method) === $value)>{{ ucfirst($label) }}</option>
                                        @endforeach
                                    </select>
                                    @if($isCurrentWithdrawalEdit)
                                        @error('method', 'withdrawal_edit')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="field">
                                    <label class="field__label">Списание</label>
                                    <select name="from_account_id">
                                        <option value="main" @selected($modalWithdrawalAccount === 'main')>{{ $mainBalanceOptionLabel }}</option>
                                        @foreach($accounts as $account)
                                            @php
                                                $optionValue = (string) $account->id;
                                                $optionLabel = ($account->number ?? '—') . ' • ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
                                            @endphp
                                            <option value="{{ $account->id }}" @selected($modalWithdrawalAccount === $optionValue)>{{ $optionLabel }}</option>
                                        @endforeach
                                    </select>
                                    @if($isCurrentWithdrawalEdit)
                                        @error('from_account_id', 'withdrawal_edit')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="field">
                                    <label class="field__label" for="withdrawal-amount-{{ $withdrawal->id }}">Сумма</label>
                                    <input id="withdrawal-amount-{{ $withdrawal->id }}" name="amount" placeholder="Сумма" type="number" step="0.01" min="0.01" value="{{ $isCurrentWithdrawalEdit ? old('amount') : $withdrawal->amount }}" required>
                                    @if($isCurrentWithdrawalEdit)
                                        @error('amount', 'withdrawal_edit')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="field">
                                    <label class="field__label" for="withdrawal-status-{{ $withdrawal->id }}">Статус</label>
                                    <select id="withdrawal-status-{{ $withdrawal->id }}" name="status" required>
                                        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($isCurrentWithdrawalEdit ? old('status') : $withdrawal->status) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @if($isCurrentWithdrawalEdit)
                                        @error('status', 'withdrawal_edit')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                @php
                                    $savedRequisites = $parseWithdrawalRequisites($withdrawal)->toArray();
                                    $formRequisites = $isCurrentWithdrawalEdit ? (array) old('requisites', $savedRequisites) : $savedRequisites;
                                    $orderedKeys = collect($withdrawalRequisiteLabels)
                                        ->keys()
                                        ->filter(fn ($key) => array_key_exists($key, $formRequisites))
                                        ->merge(
                                            collect($formRequisites)
                                                ->keys()
                                                ->reject(fn ($key) => array_key_exists($key, $withdrawalRequisiteLabels))
                                        )
                                        ->unique()
                                        ->values();
                                @endphp
                                <div style="display: grid; gap: 0.75rem; margin-top: 1rem;">
                                    <div style="font-weight: 600; font-size: 0.9rem;">Реквизиты</div>
                                    @forelse($orderedKeys as $reqKey)
                                        @php
                                            $value = $formRequisites[$reqKey] ?? '';
                                            $fieldId = 'withdrawal-' . $reqKey . '-' . $withdrawal->id;
                                        @endphp
                                        <div class="field">
                                            <label class="field__label" for="{{ $fieldId }}">{{ $requisiteLabelFor((string) $reqKey) }}</label>
                                            <input id="{{ $fieldId }}" name="requisites[{{ $reqKey }}]" type="text" value="{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}">
                                        </div>
                                    @empty
                                        <p style="font-size:0.85rem; color:#63616C;">Реквизиты отсутствуют.</p>
                                    @endforelse
                                </div>
                                @if($withdrawalHasComment)
                                    <div class="field">
                                        <label class="field__label" for="withdrawal-comment-{{ $withdrawal->id }}">Комментарий</label>
                                        <textarea id="withdrawal-comment-{{ $withdrawal->id }}" name="comment" placeholder="Комментарий" rows="4">{{ $isCurrentWithdrawalEdit ? old('comment') : $withdrawal->comment }}</textarea>
                                        @if($isCurrentWithdrawalEdit)
                                            @error('comment', 'withdrawal_edit')
                                                <span class="error-message">{{ $message }}</span>
                                            @enderror
                                        @endif
                                    </div>
                                @endif
                                <button class="btn btn--md" type="submit">Сохранить</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
@if($transactions->isNotEmpty())
    @foreach($transactions as $transaction)
        @php
            $isCurrentTransactionEdit = (int) old('editing_transaction_id', 0) === $transaction->id;
        @endphp
        <div aria-hidden="true" class="popup" id="edit-transaction-{{ $transaction->id }}">
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
                                <p>Редактировать транзакцию</p>
                            </div>
                        </div>
                        <div class="modal-content__body">
                            @if($transactionErrors && $isCurrentTransactionEdit && $transactionErrors->any())
                                <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fef2f2; color: #b91c1c; border-radius: 0.75rem;">{{ $transactionErrors->first() }}</div>
                            @endif
                            <form method="POST" action="{{ route('admin.dashboard.transactions.update', $transaction) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="editing_transaction_id" value="{{ $transaction->id }}">
                                @php
                                    $transactionEditAccount = $isCurrentTransactionEdit ? old('account_id') : optional($transaction->account)->id;
                                    if ($transactionEditAccount === null || $transactionEditAccount === '') {
                                        $transactionEditAccount = optional($transaction->account)->id ? (string) optional($transaction->account)->id : 'main';
                                    } else {
                                        $transactionEditAccount = (string) $transactionEditAccount;
                                    }
                                @endphp
                                <div class="field">
                                    <input name="created_at" type="datetime-local" value="{{ $isCurrentTransactionEdit ? old('created_at') : optional($transaction->created_at)->format('Y-m-d\TH:i') }}" required>
                                    @if($isCurrentTransactionEdit)
                                        @error('created_at', 'transaction')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="field">
                                    <select name="account_id">
                                        <option value="main" @selected($transactionEditAccount === 'main')>{{ $mainBalanceOptionLabel }}</option>
                                        @foreach($accounts as $account)
                                            @php
                                                $optionValue = (string) $account->id;
                                                $optionLabel = ($account->number ?? '—') . ' • ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
                                            @endphp
                                            <option value="{{ $account->id }}" @selected($transactionEditAccount === $optionValue)>Счёт {{ $optionLabel }}</option>
                                        @endforeach
                                    </select>
                                    @if($isCurrentTransactionEdit)
                                        @error('account_id', 'transaction')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="field">
                                    <input name="from" placeholder="От" type="text" value="{{ $isCurrentTransactionEdit ? old('from') : $transaction->from }}" required>
                                    @if($isCurrentTransactionEdit)
                                        @error('from', 'transaction')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="field">
                                    <input name="to" placeholder="Кому" type="text" value="{{ $isCurrentTransactionEdit ? old('to') : $transaction->to }}" required>
                                    @if($isCurrentTransactionEdit)
                                        @error('to', 'transaction')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="field">
                                    <select name="type" required>
                                        @foreach(['classic' => 'classic', 'fast' => 'fast', 'conversion' => 'conversion', 'hold' => 'hold'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($isCurrentTransactionEdit ? old('type') : $transaction->type) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @if($isCurrentTransactionEdit)
                                        @error('type', 'transaction')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="field">
                                    <input name="amount" placeholder="Сумма" type="number" step="0.01" min="0" value="{{ $isCurrentTransactionEdit ? old('amount') : $transaction->amount }}" required>
                                    @if($isCurrentTransactionEdit)
                                        @error('amount', 'transaction')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="field">
                                    <select name="status" required>
                                        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'blocked' => 'Blocked', 'hold' => 'Hold'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($isCurrentTransactionEdit ? old('status') : $transaction->status) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @if($isCurrentTransactionEdit)
                                        @error('status', 'transaction')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <button class="btn btn--md" type="submit">Сохранить</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
<div class="wrapper">
    <main class="page">
        <div class="admin-page">
            <div class="container">
                <div class="admin-panel">
                    @if(session('status'))
                        <div style="margin-bottom: 1.5rem; padding: 1rem 1.5rem; background: #ecfdf5; border-radius: 1rem; color: #047857; font-weight: 600;">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if($errors->any() && ! session('status'))
                        <div style="margin-bottom: 1.5rem; padding: 1rem 1.5rem; background: #fef2f2; border-radius: 1rem; color: #b91c1c; font-weight: 600;">
                            Исправьте ошибки формы и попробуйте снова.
                        </div>
                    @endif

                    <div class="admin-panel__block">
                        <div class="admin-panel__title">User selection</div>

                        @if(! $hasAnyClients)
                            <p class="admin-panel__empty">Нет клиентов для отображения. Создайте клиента через Filament панель.</p>
                        @else
                            @if($hasPendingClients)
                                <form method="GET" action="{{ route('admin.dashboard', [], false) }}" class="admin-panel__line" style="gap: 1rem; flex-wrap: wrap; margin-bottom: {{ $hasClients ? '1rem' : '0.5rem' }};" id="admin-dashboard-pending-form">
                                    <div class="admin-panel__field" style="min-width: 220px;">
                                        <div class="admin-panel__field-label" style="color: #b45309;">Pending user</div>
                                        <select id="admin-dashboard-pending" name="user" class="admin-panel__select" data-submit>
                                            @foreach($pendingClientOptions as $id => $name)
                                                <option value="{{ $id }}" @selected($selectedUserId === (int) $id)>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <p style="margin: 0; font-size: 0.85rem; color: #92400e;">Завершите проверку и обновите статус в блоке «User info».</p>
                                </form>
                            @endif

                            <form method="GET" action="{{ route('admin.dashboard', [], false) }}" class="admin-panel__line" style="gap: 1rem; flex-wrap: wrap;" id="admin-dashboard-user-form">
                                <div class="admin-panel__field" style="min-width: 220px;">
                                    <div class="admin-panel__field-label">All users</div>
                                    @php
                                        $selectClasses = 'admin-panel__select';
                                        if ($selectedUser) {
                                            $selectClasses .= ' admin-panel__select--active';
                                        }
                                    @endphp
                                    <select id="admin-dashboard-client" name="user" class="{{ $selectClasses }}" data-submit>
                                        @foreach($clientOptions as $id => $name)
                                            @php
                                                $statusLabel = $pendingClientOptions->has($id) ? ' (pending)' : '';
                                            @endphp
                                            <option value="{{ $id }}" @selected($selectedUserId === (int) $id)>{{ $name }}{{ $statusLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if($selectedUser)
                                    <div class="admin-panel__item">
                                        <div style="font-size: 0.75rem; text-transform: uppercase; color: #63616C; letter-spacing: 0.04em;">Primary account</div>
                                        <div style="font-weight: 600; font-size: 1.125rem;">{{ $primaryAccount ? $formatMoney($primaryAccount->balance, $primaryAccount->currency ?? $selectedUserCurrency) : '—' }}</div>
                                        @if($primaryAccount)
                                            <div style="font-size: 0.85rem; color: #63616C;">{{ $primaryAccount->number }}</div>
                                        @endif
                                    </div>
                                @endif
                            </form>

                            @if($selectedUser && ! $hasClients)
                                <div class="admin-panel__line" style="gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
                                    <div class="admin-panel__item">
                                        <div style="font-size: 0.75rem; text-transform: uppercase; color: #63616C; letter-spacing: 0.04em;">Primary account</div>
                                        <div style="font-weight: 600; font-size: 1.125rem;">{{ $primaryAccount ? $formatMoney($primaryAccount->balance, $primaryAccount->currency ?? $selectedUserCurrency) : '—' }}</div>
                                        @if($primaryAccount)
                                            <div style="font-size: 0.85rem; color: #63616C;">{{ $primaryAccount->number }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if($selectedUser)
                                <div style="margin-top: 0.75rem; display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                                    <span style="{{ $statusBadgeStyle($selectedUser->verification_status) }}">{{ strtoupper($selectedUser->verification_status ?? 'PENDING') }}</span>
                                    @if($selectedUserIsPending)
                                        <span style="font-size: 0.85rem; color: #92400e;">Пользователь ожидает одобрения.</span>
                                    @endif
                                </div>

                                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.75rem;">
                                    <button type="button" class="btn btn--md" data-popup="#support-modal">Написать в поддержку</button>
                                    <button type="button" class="btn btn--md" data-popup="#withdraw-modal">Создать вывод средств</button>
                                    <button type="button" class="btn btn--md" data-popup="#violation">Сообщить о нарушении</button>
                                    </div>
                            @endif
                        @endif
                    </div>

                    <div class="admin-panel__block">
                        <div class="admin-panel__title">User info</div>

                        @if(! $selectedUser)
                            <p class="admin-panel__empty">Выберите клиента, чтобы увидеть информацию об аккаунте.</p>
                        @else
                            <form method="POST" action="{{ route('admin.dashboard.users.update', $selectedUser) }}" id="admin-user-edit-form" data-editing="{{ $userEditActive ? 'true' : 'false' }}">
                                @csrf
                                @method('PUT')

                                <div class="admin-panel__entry">
                                    <div class="admin-panel__entry-header" style="justify-content: flex-end;">
                                        <div class="admin-panel__entry-actions" style="gap: 0.5rem;">
                                            <button class="btn btn--md" type="button" data-action="user-edit" @if($userEditActive) style="display: none;" @endif>Редактировать</button>
                                            <div data-role="user-edit-actions" style="display: {{ $userEditActive ? 'flex' : 'none' }}; gap: 0.5rem;">
                                                <button class="btn btn--md" type="submit">Сохранить</button>
                                                <button class="btn btn--md" type="button" data-action="user-cancel" style="background: #F1F5F9; color: #111827;">Отмена</button>
                                            </div>
                                        </div>
                                    </div>

                                    @if($userErrors && $userErrors->any())
                                        <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fef2f2; color: #b91c1c; border-radius: 0.75rem;">{{ $userErrors->first() }}</div>
                                    @endif

                                    <div class="admin-panel__grid">
                                    <div class="admin-panel__field">
                                        <div class="admin-panel__field-label">Full name</div>
                                        <input class="admin-panel__field-input" name="name" type="text" value="{{ old('name', $selectedUser->name) }}" @unless($userEditActive) readonly @endunless data-editable>
                                        @error('name', 'user')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="admin-panel__field">
                                        <div class="admin-panel__field-label">Email</div>
                                        <input class="admin-panel__field-info" name="email" type="email" value="{{ old('email', $selectedUser->email) }}" @unless($userEditActive) readonly @endunless data-editable>
                                        @error('email', 'user')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="admin-panel__field">
                                        <div class="admin-panel__field-label">Verification</div>
                                        <select class="admin-panel__field-info" name="verification_status" @unless($userEditActive) disabled @endunless data-editable="disabled">
                                            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('verification_status', $selectedUser->verification_status) === $value)>{{ strtoupper($label) }}</option>
                                            @endforeach
                                        </select>
                                        @error('verification_status', 'user')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="admin-panel__field">
                                        <div class="admin-panel__field-label">Currency</div>
                                        <select class="admin-panel__field-info" name="currency" @unless($userEditActive) disabled @endunless data-editable="disabled">
                                            @foreach($currencyOptions as $code => $label)
                                                <option value="{{ $code }}" @selected(old('currency', $selectedUser->currency) === $code)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('currency', 'user')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="admin-panel__field">
                                        <div class="admin-panel__field-label">Main balance</div>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <input class="admin-panel__field-info" name="main_balance" type="number" step="0.01" min="0" value="{{ old('main_balance', number_format((float) $selectedUser->main_balance, 2, '.', '')) }}" @unless($userEditActive) readonly @endunless data-editable>
                                            <span style="font-size: 0.85rem; font-weight: 600; color: #1F2937;" data-role="user-balance-currency" data-default="{{ $selectedUserCurrency }}">{{ $selectedUserCurrency }}</span>
                                        </div>
                                        @error('main_balance', 'user')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="admin-panel__field">
                                        <div class="admin-panel__field-label">Created at</div>
                                        <input class="admin-panel__field-info" name="created_at" type="datetime-local" value="{{ old('created_at', optional($selectedUser->created_at)->format('Y-m-d\TH:i')) }}" @unless($userEditActive) disabled @endunless data-editable="disabled">
                                        @error('created_at', 'user')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="admin-panel__field admin-panel__field--wide">
                                        <div class="admin-panel__field-label">Новый пароль</div>
                                        <input class="admin-panel__field-info" name="password" type="password" placeholder="Введите новый пароль" @unless($userEditActive) readonly @endunless data-editable>
                                        @error('password', 'user')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>

                    <div class="admin-panel__block">
                        <div class="admin-panel__title">Bank accounts</div>

                        @if(! $selectedUser)
                            <p class="admin-panel__empty">Выберите клиента, чтобы увидеть счета.</p>
                        @elseif($accounts->isEmpty())
                            <p class="admin-panel__empty">Нет активных счетов для клиента.</p>
                        @else
                            <div class="admin-panel__entry">
                                <form method="GET" action="{{ route('admin.dashboard', [], false) }}" class="admin-panel__grid" style="gap: 1.25rem;">
                                    <input type="hidden" name="user" value="{{ $selectedUserId }}">
                                    <div class="admin-panel__field" style="min-width: 220px;">
                                        <div class="admin-panel__field-label">Выберите счёт</div>
                                        <select class="admin-panel__field-info" name="account" data-submit data-native>
                                            @foreach($accountOptions as $id => $number)
                                                @php $optionValue = (string) $id; @endphp
                                                <option value="{{ $optionValue }}" @selected($selectedAccountValue === $optionValue)>{{ $number }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </form>

                                @if($selectedAccount)
                                    <form method="POST" action="{{ route('admin.dashboard.accounts.update', $selectedAccount) }}" id="admin-account-edit-form" data-editing="{{ $accountEditActive ? 'true' : 'false' }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="editing_account_id" value="{{ $selectedAccount->id }}">

                                        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-bottom: 1rem;">
                                            <button class="btn btn--md" type="button" data-action="account-edit" @if($accountEditActive) style="display: none;" @endif>Редактировать счёт</button>
                                            <div data-role="account-edit-actions" style="display: {{ $accountEditActive ? 'flex' : 'none' }}; gap: 0.5rem;">
                                                <button class="btn btn--md" type="submit">Сохранить</button>
                                                <button class="btn btn--md" type="button" data-action="account-cancel" style="background: #F1F5F9; color: #111827;">Отмена</button>
                                            </div>
                                        </div>

                                        @if($accountEditErrors && $accountEditErrors->any())
                                            <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fef2f2; color: #b91c1c; border-radius: 0.75rem;">{{ $accountEditErrors->first() }}</div>
                                        @endif

                                        <div class="admin-panel__grid">
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Номер счёта</div>
                                                <input class="admin-panel__field-info" name="number" type="text" value="{{ old('number', $selectedAccount->number) }}" @unless($accountEditActive) readonly @endunless data-editable>
                                                @error('number', 'account_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Статус</div>
                                                <select class="admin-panel__field-info" name="status" @unless($accountEditActive) disabled @endunless data-editable="disabled" data-native>
                                                    @foreach($accountStatusOptions as $code => $label)
                                                        <option value="{{ $code }}" @selected(old('status', $selectedAccount->status) === $code)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('status', 'account_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Тип счёта</div>
                                                <select class="admin-panel__field-info" name="type" @unless($accountEditActive) disabled @endunless data-editable="disabled" data-native>
                                                    @foreach($accountTypeOptions as $code => $label)
                                                        <option value="{{ $code }}" @selected(old('type', $selectedAccount->type) === $code)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('type', 'account_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Организация</div>
                                                <input class="admin-panel__field-info" name="organization" type="text" value="{{ old('organization', $selectedAccount->organization) }}" @unless($accountEditActive) readonly @endunless data-editable>
                                                @error('organization', 'account_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Банк</div>
                                                <input class="admin-panel__field-info" name="bank" type="text" value="{{ old('bank', $selectedAccount->bank) }}" @unless($accountEditActive) readonly @endunless data-editable>
                                                @error('bank', 'account_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Баланс счёта</div>
                                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                    <input class="admin-panel__field-info" name="balance" type="number" step="0.01" min="0" value="{{ old('balance', number_format((float) $selectedAccount->balance, 2, '.', '')) }}" @unless($accountEditActive) readonly @endunless data-editable>
                                                    <span style="font-size: 0.85rem; font-weight: 600; color: #1F2937;" data-role="account-balance-currency" data-default="{{ $selectedAccount->currency ?? $selectedUserCurrency }}">{{ $selectedAccount->currency ?? $selectedUserCurrency }}</span>
                                                </div>
                                                @error('balance', 'account_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Валюта счёта</div>
                                                <select class="admin-panel__field-info" name="currency" @unless($accountEditActive) disabled @endunless data-editable="disabled" data-native>
                                                    <option value="">Как у пользователя ({{ $selectedUserCurrency }})</option>
                                                    @foreach($currencyOptions as $code => $label)
                                                        <option value="{{ $code }}" @selected(old('currency', $selectedAccount->currency) === $code)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('currency', 'account_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Срок действия</div>
                                                <input class="admin-panel__field-info" name="term" type="date" value="{{ old('term', $selectedAccount && $selectedAccount->term ? \Illuminate\Support\Carbon::parse($selectedAccount->term)->format('Y-m-d') : '') }}" @unless($accountEditActive) disabled @endunless data-editable="disabled">
                                                @error('term', 'account_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Инициалы клиента</div>
                                                <input class="admin-panel__field-info" name="client_initials" type="text" value="{{ old('client_initials', $selectedAccount->client_initials) }}" @unless($accountEditActive) readonly @endunless data-editable>
                                                @error('client_initials', 'account_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Инициалы брокера</div>
                                                <input class="admin-panel__field-info" name="broker_initials" type="text" value="{{ old('broker_initials', $selectedAccount->broker_initials) }}" @unless($accountEditActive) readonly @endunless data-editable>
                                                @error('broker_initials', 'account_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            @php
                                                $inlineDefaultId = 'account_inline_default';
                                            @endphp
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Основной</div>
                                                <label class="checkbox__label" for="{{ $inlineDefaultId }}" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                                    <input class="checkbox__input" id="{{ $inlineDefaultId }}" type="checkbox" name="is_default" value="1" @checked(old('is_default', $selectedAccount->is_default)) @unless($accountEditActive) disabled @endunless data-editable="toggle">
                                                    <span class="checkbox__text">Отмечен как основной</span>
                                                </label>
                                            </div>
                                        </div>
                                    </form>
                                @elseif($selectedAccountValue === 'main')
                                    <div style="margin-top: 1.5rem; padding: 1rem 1.25rem; border-radius: 1rem; background: #f8fafc; border: 1px solid #e2e8f0;">
                                        <div style="font-size: 0.95rem; font-weight: 600; color: #111827; margin-bottom: 0.5rem;">{{ __('Main balance') }}</div>
                                        <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: baseline;">
                                                <div style="font-size: 1.35rem; font-weight: 700; color: #0f172a;">{{ $formatMoney($selectedUser->main_balance ?? 0, $selectedUserCurrency) }}</div>
                                                <div style="font-size: 0.85rem; color: #475569;">{{ __('Источник средств будет списан с основного баланса клиента.') }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <button class="btn btn--md" data-popup="#create-modal" type="button" @disabled(! $selectedUser)>Создать новый счёт</button>
                    </div>

                    <div class="admin-panel__block">
                        <div class="admin-panel__title">Последние транзакции</div>
                        @if(! $selectedUser)
                            <p class="admin-panel__empty">Выберите клиента, чтобы увидеть его транзакции.</p>
                        @elseif($transactionOptions->isEmpty())
                            <p class="admin-panel__empty">Для выбранного клиента ещё нет транзакций.</p>
                        @else
                            <form method="GET" action="{{ route('admin.dashboard', [], false) }}" class="admin-panel__grid admin-panel__grid--entry" style="grid-template-columns: repeat(1, minmax(240px, 1fr));">
                                <input type="hidden" name="user" value="{{ $selectedUserId }}">
                                @if(! empty($selectedAccountValue))
                                    <input type="hidden" name="account" value="{{ $selectedAccountValue }}">
                                @endif
                                @if($selectedWithdrawal)
                                    <input type="hidden" name="withdrawal" value="{{ $selectedWithdrawal->id }}">
                                @endif
                                <div class="admin-panel__field">
                                    <div class="admin-panel__field-label">Выберите транзакцию</div>
                                    <select class="admin-panel__field-info" name="transaction" data-submit data-native>
                                        @foreach($transactionOptions as $id => $label)
                                            <option value="{{ $id }}" @selected($selectedTransaction && $selectedTransaction->id === (int) $id)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>

                            @if($selectedTransaction)
                                @php
                                    [$datePart, $timePart] = $transactionDateParts($selectedTransaction);
                                    $chipClass = $statusChipClassFor($selectedTransaction->status);
                                    $chipLabel = $statusChipLabelFor($selectedTransaction->status);
                                    $accountNumber = $selectedTransaction->account?->number ?? 'Main balance';
                                    $transactionFormAccount = old('account_id');
                                    if ($transactionFormAccount === null || $transactionFormAccount === '') {
                                        $transactionFormAccount = $selectedTransaction->account_id ? (string) $selectedTransaction->account_id : 'main';
                                    } else {
                                        $transactionFormAccount = (string) $transactionFormAccount;
                                    }
                                @endphp
                                <form method="POST" action="{{ route('admin.dashboard.transactions.update', $selectedTransaction) }}" id="admin-transaction-edit-form" data-editing="{{ $transactionEditActive ? 'true' : 'false' }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="editing_transaction_id" value="{{ $selectedTransaction->id }}">

                                    <div class="admin-panel__entry">
                                        <div class="admin-panel__entry-header">
                                            <div>
                                                <div class="admin-panel__entry-title">{{ \Illuminate\Support\Str::upper($selectedTransaction->type ?? 'TRANSACTION') }}</div>
                                                <div class="admin-panel__entry-subtitle">{{ $datePart }} • {{ $timePart }}</div>
                                            </div>
                                            <div class="admin-panel__entry-actions">
                                                <span class="{{ $chipClass }}">{{ $chipLabel }}</span>
                                            </div>
                                        </div>

                                        @if($transactionEditErrors && $transactionEditErrors->any())
                                            <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fef2f2; color: #b91c1c; border-radius: 0.75rem;">{{ $transactionEditErrors->first() }}</div>
                                        @endif

                                        <div class="admin-panel__grid admin-panel__grid--entry admin-panel__grid--transaction">
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">ID</div>
                                                <input class="admin-panel__field-info" type="text" value="{{ $selectedTransaction->id }}" readonly>
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Дата</div>
                                                <input class="admin-panel__field-info" name="created_at" type="datetime-local" value="{{ old('created_at', optional($selectedTransaction->created_at)->format('Y-m-d\TH:i')) }}" data-editable="disabled">
                                                @error('created_at', 'transaction_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Счёт</div>
                                                <select class="admin-panel__field-info" name="account_id" data-editable="disabled" data-native>
                                                    <option value="main" @selected($transactionFormAccount === 'main')>{{ $mainBalanceOptionLabel }}</option>
                                                    @foreach($accounts as $account)
                                                        @php
                                                            $optionValue = (string) $account->id;
                                                            $optionLabel = ($account->number ?? '—') . ' • ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
                                                        @endphp
                                                        <option value="{{ $account->id }}" @selected($transactionFormAccount === $optionValue)>{{ $optionLabel }}</option>
                                                    @endforeach
                                                </select>
                                                @error('account_id', 'transaction_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Сумма</div>
                                                <input class="admin-panel__field-info" name="amount" type="number" step="0.01" min="0" value="{{ old('amount', $selectedTransaction->amount) }}" data-editable>
                                                @error('amount', 'transaction_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Тип</div>
                                                <select class="admin-panel__field-info" name="type" data-editable="disabled" data-native>
                                                    @foreach($transactionTypeOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(old('type', $selectedTransaction->type) === $value)>{{ strtoupper($label) }}</option>
                                                    @endforeach
                                                </select>
                                                @error('type', 'transaction_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Статус</div>
                                                <select class="admin-panel__field-info" name="status" data-editable="disabled" data-native>
                                                    @foreach($transactionStatusOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(old('status', $selectedTransaction->status) === $value)>{{ strtoupper($label) }}</option>
                                                    @endforeach
                                                </select>
                                                @error('status', 'transaction_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field admin-panel__field--span-3">
                                                <div class="admin-panel__field-label">От</div>
                                                <input class="admin-panel__field-info" name="from" type="text" value="{{ old('from', $selectedTransaction->from) }}" data-editable>
                                                @error('from', 'transaction_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field admin-panel__field--span-3">
                                                <div class="admin-panel__field-label">Кому</div>
                                                <input class="admin-panel__field-info" name="to" type="text" value="{{ old('to', $selectedTransaction->to) }}" data-editable>
                                                @error('to', 'transaction_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="admin-panel__entry-actions" data-role="transaction-edit-actions" style="display: {{ $transactionEditActive ? 'flex' : 'none' }}; justify-content: flex-end; margin-top: 1rem; gap: 0.5rem;">
                                            <button class="btn btn--md" type="submit">Сохранить</button>
                                            <button class="btn btn--md" type="button" data-action="transaction-cancel" style="background: #F1F5F9; color: #111827;">Отмена</button>
                                        </div>
                                        <div class="admin-panel__entry-actions" style="justify-content: flex-end; margin-top: 1rem;">
                                            <button class="btn btn--md" type="button" data-action="transaction-edit" @if($transactionEditActive) style="display: none;" @endif>Редактировать</button>
                                        </div>
                                    </div>
                                </form>
                            @endif
                        @endif
                        <div style="margin-top: 1rem;">
                            <button class="btn btn--md" type="button" data-popup="#create-transaction-modal" @disabled(! $selectedUser)>Создать транзакцию</button>
                        </div>
                    </div>

                    <div class="admin-panel__block">
                        <div class="admin-panel__title">Заявки на вывод средств</div>
                        @if(! $selectedUser)
                            <p class="admin-panel__empty">Выберите клиента, чтобы просмотреть его заявки на вывод.</p>
                        @elseif($withdrawalOptions->isEmpty())
                            <p class="admin-panel__empty">У выбранного клиента ещё нет заявок.</p>
                        @else
                            <form method="GET" action="{{ route('admin.dashboard', [], false) }}" class="admin-panel__grid admin-panel__grid--entry" style="grid-template-columns: repeat(1, minmax(240px, 1fr));">
                                <input type="hidden" name="user" value="{{ $selectedUserId }}">
                                @if(! empty($selectedAccountValue))
                                    <input type="hidden" name="account" value="{{ $selectedAccountValue }}">
                                @endif
                                @if($selectedTransaction)
                                    <input type="hidden" name="transaction" value="{{ $selectedTransaction->id }}">
                                @endif
                                <div class="admin-panel__field">
                                    <div class="admin-panel__field-label">Выберите заявку</div>
                                    <select class="admin-panel__field-info" name="withdrawal" data-submit data-native>
                                        @foreach($withdrawalOptions as $id => $label)
                                            <option value="{{ $id }}" @selected($selectedWithdrawal && $selectedWithdrawal->id === (int) $id)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>

                            @if($selectedWithdrawal)
                                @php
                                    [$datePart, $timePart] = $transactionDateParts($selectedWithdrawal);
                                    $chipClass = $statusChipClassFor($selectedWithdrawal->status);
                                    $chipLabel = $statusChipLabelFor($selectedWithdrawal->status);
                                    $withdrawalFormMethod = old('method', $selectedWithdrawal->method);
                                    $withdrawalFormAccount = old('from_account_id');
                                    if ($withdrawalFormAccount === null || $withdrawalFormAccount === '') {
                                        $withdrawalFormAccount = $selectedWithdrawal->from_account_id ? (string) $selectedWithdrawal->from_account_id : 'main';
                                    } else {
                                        $withdrawalFormAccount = (string) $withdrawalFormAccount;
                                    }
                                    $withdrawalFormStatus = old('status', $selectedWithdrawal->status);
                                    $withdrawalFormComment = old('comment', $selectedWithdrawal->comment);
                                    $withdrawalFormRequisites = collect(old('requisites', $parseWithdrawalRequisites($selectedWithdrawal)->toArray()));
                                    $orderedKeys = collect($withdrawalRequisiteLabels)
                                        ->keys()
                                        ->filter(fn ($key) => $withdrawalFormRequisites->has($key));
                                    $additionalKeys = $withdrawalFormRequisites->keys()
                                        ->reject(fn ($key) => array_key_exists($key, $withdrawalRequisiteLabels));
                                    $displayKeys = $orderedKeys->merge($additionalKeys)->values();
                                @endphp
                                <form method="POST" action="{{ route('admin.dashboard.withdrawals.update', $selectedWithdrawal) }}" id="admin-withdrawal-edit-form" data-editing="{{ $withdrawalEditActive ? 'true' : 'false' }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="editing_withdrawal_id" value="{{ $selectedWithdrawal->id }}">

                                    <div class="admin-panel__entry">
                                        <div class="admin-panel__entry-header">
                                            <div>
                                                <div class="admin-panel__entry-title">{{ \Illuminate\Support\Str::upper(__('Withdrawal')) }} • {{ \Illuminate\Support\Str::upper($selectedWithdrawal->method ?? 'CARD') }}</div>
                                                <div class="admin-panel__entry-subtitle">{{ $datePart }} • {{ $timePart }}</div>
                                            </div>
                                            <div class="admin-panel__entry-actions">
                                                <span class="{{ $chipClass }}">{{ $chipLabel }}</span>
                                            </div>
                                        </div>

                                        @if($withdrawalEditErrors && $withdrawalEditErrors->any())
                                            <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fef2f2; color: #b91c1c; border-radius: 0.75rem;">{{ $withdrawalEditErrors->first() }}</div>
                                        @endif

                                        <div class="admin-panel__grid admin-panel__grid--entry">
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">ID</div>
                                                <input class="admin-panel__field-info" type="text" value="{{ $selectedWithdrawal->id }}" readonly>
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Списание</div>
                                                <select class="admin-panel__field-info" name="from_account_id" data-editable="disabled" data-native>
                                                    <option value="main" @selected($withdrawalFormAccount === 'main')>{{ $mainBalanceOptionLabel }}</option>
                                                    @foreach($accounts as $account)
                                                        @php
                                                            $optionValue = (string) $account->id;
                                                            $optionLabel = ($account->number ?? '—') . ' • ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
                                                        @endphp
                                                        <option value="{{ $account->id }}" @selected($withdrawalFormAccount === $optionValue)>{{ $optionLabel }}</option>
                                                    @endforeach
                                                </select>
                                                @error('from_account_id', 'withdrawal_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Метод</div>
                                                <select class="admin-panel__field-info" name="method" data-editable="disabled" data-native>
                                                    @foreach($withdrawalMethods as $value => $label)
                                                        <option value="{{ $value }}" @selected($withdrawalFormMethod === $value)>{{ strtoupper($label) }}</option>
                                                    @endforeach
                                                </select>
                                                @error('method', 'withdrawal_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Сумма</div>
                                                <input class="admin-panel__field-info" name="amount" type="number" step="0.01" min="0" value="{{ old('amount', $selectedWithdrawal->amount) }}" data-editable>
                                                @error('amount', 'withdrawal_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="admin-panel__field">
                                                <div class="admin-panel__field-label">Статус</div>
                                                <select class="admin-panel__field-info" name="status" data-editable="disabled" data-native>
                                                    @foreach($withdrawalStatusOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected($withdrawalFormStatus === $value)>{{ strtoupper($label) }}</option>
                                                    @endforeach
                                                </select>
                                                @error('status', 'withdrawal_edit')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            @if($withdrawalHasComment)
                                                <div class="admin-panel__field admin-panel__field--wide">
                                                    <div class="admin-panel__field-label">Комментарий</div>
                                                    <input class="admin-panel__field-info" name="comment" type="text" value="{{ $withdrawalFormComment }}" data-editable>
                                                    @error('comment', 'withdrawal_edit')
                                                        <span class="error-message">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            @endif
                                            @php
                                                $requisiteErrorMessage = $withdrawalEditErrors ? ($withdrawalEditErrors->first('requisites') ?? $withdrawalEditErrors->first('requisites.*')) : null;
                                            @endphp
                                            @foreach($displayKeys as $reqKey)
                                                @php
                                                    $reqValue = old('requisites.' . $reqKey, $withdrawalFormRequisites->get($reqKey));
                                                @endphp
                                                <div class="admin-panel__field admin-panel__field--wide">
                                                    <div class="admin-panel__field-label">{{ $requisiteLabelFor((string) $reqKey) }}</div>
                                                    <input class="admin-panel__field-info" name="requisites[{{ $reqKey }}]" type="text" value="{{ $reqValue }}" data-editable>
                                                </div>
                                            @endforeach
                                            @if($requisiteErrorMessage)
                                                <div class="admin-panel__field admin-panel__field--full" style="border: none; padding: 0;">
                                                    <span class="error-message">{{ $requisiteErrorMessage }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="admin-panel__entry-actions" data-role="withdrawal-edit-actions" style="display: {{ $withdrawalEditActive ? 'flex' : 'none' }}; justify-content: flex-end; margin-top: 1rem; gap: 0.5rem;">
                                            <button class="btn btn--md" type="submit">Сохранить</button>
                                            <button class="btn btn--md" type="button" data-action="withdrawal-cancel" style="background: #F1F5F9; color: #111827;">Отмена</button>
                                        </div>
                                        <div class="admin-panel__entry-actions" style="justify-content: flex-end; margin-top: 1rem;">
                                            <button class="btn btn--md" type="button" data-action="withdrawal-edit" @if($withdrawalEditActive) style="display: none;" @endif>Редактировать</button>
                                        </div>
                                    </div>
                                </form>
                            @endif
                        @endif
                    </div>

                    <div class="admin-panel__block">
                        <div class="admin-panel__title">Документы</div>
                        @if(! $selectedUser)
                            <p class="admin-panel__empty">Выберите клиента, чтобы управлять документами.</p>
                        @else
                            @php
                                $hasDocuments = $documents->isNotEmpty();
                            @endphp
                            <p class="admin-panel__empty" data-document-empty @if($hasDocuments) style="display: none;" @endif>Документы отсутствуют.</p>
                            <ul class="admin-panel__list" data-document-list>
                                @foreach($documents as $document)
                                    @php
                                        $documentPath = $document->path;
                                        $documentExists = $documentPath && \Illuminate\Support\Facades\Storage::disk($documentsDisk)->exists($documentPath);
                                        if (! $documentExists && $documentPath) {
                                            $symlinkedPath = public_path('storage/' . ltrim($documentPath, '/'));
                                            if ($symlinkedPath && file_exists($symlinkedPath)) {
                                                $documentExists = true;
                                            }
                                        }
                                        $documentPreviewUrl = $documentExists ? route('admin.dashboard.documents.preview', $document) : null;
                                        $documentIsImage = $documentPreviewUrl && $isDocumentImage($documentPath);
                                        $documentExtensionLabel = strtoupper(pathinfo($document->original_name ?? '', PATHINFO_EXTENSION) ?: 'FILE');
                                    @endphp
                                    <li data-document-entry style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            @if($documentPreviewUrl && $documentIsImage)
                                                <button type="button" class="admin-panel__doc-thumb" data-popup="#preview-document-{{ $document->id }}" title="Открыть превью">
                                                    <img src="{{ $documentPreviewUrl }}" alt="{{ $document->original_name }}">
                                                </button>
                                            @elseif($documentPreviewUrl)
                                                <a class="admin-panel__doc-thumb admin-panel__doc-thumb--file" href="{{ $documentPreviewUrl }}" target="_blank" rel="noopener" title="Открыть файл">
                                                    <span>{{ $documentExtensionLabel }}</span>
                                                </a>
                                            @else
                                                <div class="admin-panel__doc-thumb admin-panel__doc-thumb--empty" title="Файл недоступен">
                                                    <span>Нет файла</span>
                                                </div>
                                            @endif
                                            <div>
                                                <div style="font-weight: 600;">{{ $document->original_name }}</div>
                                                <div style="font-size: 0.85rem; color: #63616C;">{{ $formatDate($document->created_at, 'd.m.Y H:i') }}</div>
                                                @if($document->document_type)
                                                    <div style="font-size: 0.8rem; color: #475569;">Тип: {{ $document->document_type }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="{{ $statusBadgeStyle($document->status) }}">{{ strtoupper($document->status ?? 'PENDING') }}</span>
                                            <button class="btn btn--md" type="button" data-popup="#edit-document-{{ $document->id }}">Редактировать</button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="admin-panel__entry admin-panel__entry--compact" style="margin-top: 1.5rem;">
                                <div class="admin-panel__entry-head" style="justify-content: space-between; align-items: center; gap: 1rem;">
                                    <div style="font-weight: 600;">Добавить документ</div>
                                </div>
                                <form method="POST" action="{{ route('admin.dashboard.documents.store') }}" enctype="multipart/form-data" class="admin-panel__grid admin-panel__grid--entry" style="margin-top: 1rem;">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ old('user_id', $selectedUserId) }}">
                                    <div class="admin-panel__field admin-panel__field--full">
                                        <div class="admin-panel__field-label">Файлы</div>
                                        <div class="field field--upload" data-file-upload data-file-upload-doc-list="[data-document-list]" data-file-upload-doc-empty="[data-document-empty]">
                                            <label class="modal-content__file">
                                                <input hidden type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.pdf,.webp,.gif,.doc,.docx,.svg" data-file-upload-input data-max-files="5">
                                                <span data-file-upload-label>Прикрепить файлы (до 5)</span>
                                            </label>
                                            <ul class="modal-content__file-list" data-file-upload-list></ul>
                                        </div>
                                        @php
                                            $documentCreateFileError = $documentCreateErrors ? ($documentCreateErrors->first('files') ?: $documentCreateErrors->first('files.*') ?: $documentCreateErrors->first('file')) : null;
                                        @endphp
                                        @if($documentCreateFileError)
                                            <span class="error-message">{{ $documentCreateFileError }}</span>
                                        @endif
                                    </div>
                                    <div class="admin-panel__field">
                                        <div class="admin-panel__field-label">Тип документа</div>
                                        <input class="admin-panel__field-info" type="text" name="document_type" value="{{ old('document_type') }}" placeholder="Например, Паспорт">
                                        @if($documentCreateErrors && $documentCreateErrors->has('document_type'))
                                            <span class="error-message">{{ $documentCreateErrors->first('document_type') }}</span>
                                        @endif
                                    </div>
                                    <div class="admin-panel__field">
                                        <div class="admin-panel__field-label">Статус</div>
                                        @php $createDocumentStatus = old('status', 'pending'); @endphp
                                        <select class="admin-panel__field-info" name="status" data-native>
                                            <option value="" @selected($createDocumentStatus === '')>Без статуса</option>
                                            @foreach($documentStatusOptions as $code => $label)
                                                <option value="{{ $code }}" @selected($createDocumentStatus === $code)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @if($documentCreateErrors && $documentCreateErrors->has('status'))
                                            <span class="error-message">{{ $documentCreateErrors->first('status') }}</span>
                                        @endif
                                    </div>
                                    @if($documentCreateErrors && $documentCreateErrors->any())
                                        <div class="admin-panel__field admin-panel__field--full" style="border: none; padding: 0;">
                                            <span class="error-message">{{ $documentCreateErrors->first() }}</span>
                                        </div>
                                    @endif
                                    <div class="admin-panel__field admin-panel__field--full" style="border: none; padding: 0; display: flex; justify-content: flex-end;">
                                        <button class="btn btn--md" type="submit">Загрузить документ</button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>

                    <div class="admin-panel__block">
                        <div class="admin-panel__title">Сообщения о нарушениях</div>
                        @if(! $selectedUser || $fraudClaims->isEmpty())
                            <p class="admin-panel__empty">Заявок нет.</p>
                        @else
                            <ul class="admin-panel__list">
                                @foreach($fraudClaims as $claim)
                                    <li>
                                        <div>
                                            <div style="font-weight: 600;">{{ str(strip_tags($claim->details))->limit(80) }}</div>
                                            <div style="font-size: 0.85rem; color: #63616C;">{{ $formatDate($claim->created_at, 'd.m.Y H:i') }}</div>
                                            @php
                                                $attachments = $claim->attachments;
                                            @endphp
                                            @if($attachments->isNotEmpty())
                                                <div class="admin-fraud-attachments admin-fraud-attachments--compact">
                                                    @foreach($attachments as $attachment)
                                                        @php
                                                            $disk = $attachment->disk ?: 'public';
                                                            $attachmentExists = $attachment->path && \Illuminate\Support\Facades\Storage::disk($disk)->exists($attachment->path);
                                                            $attachmentIsImage = $attachmentExists && $isDocumentImage($attachment->path);
                                                            $attachmentPreviewUrl = $attachmentIsImage ? route('admin.dashboard.fraud-claims.attachments.preview', [$claim, $attachment]) : null;
                                                            $downloadUrl = route('admin.dashboard.fraud-claims.attachments.download', [$claim, $attachment]);
                                                            $attachmentExtLabel = strtoupper(pathinfo($attachment->original_name ?? $attachment->path, PATHINFO_EXTENSION) ?: 'FILE');
                                                        @endphp
                                                        @if($attachmentIsImage && $attachmentPreviewUrl)
                                                            <button type="button" class="admin-panel__doc-thumb" data-popup="#preview-fraud-attachment-{{ $attachment->id }}" title="Открыть превью">
                                                                <img src="{{ $attachmentPreviewUrl }}" alt="{{ $attachment->original_name }}">
                                                            </button>
                                                        @elseif($attachmentExists)
                                                            <a class="admin-panel__doc-thumb admin-panel__doc-thumb--file" href="{{ $downloadUrl }}" target="_blank" rel="noopener" title="Скачать файл">
                                                                <span>{{ $attachmentExtLabel }}</span>
                                                            </a>
                                                        @else
                                                            <div class="admin-panel__doc-thumb admin-panel__doc-thumb--empty" title="Файл недоступен">
                                                                <span>Нет файла</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="{{ $statusBadgeStyle($claim->status) }}">{{ strtoupper($claim->status) }}</span>
                                            <button class="btn btn--md" type="button" data-popup="#edit-fraud-claim-{{ $claim->id }}">Редактировать</button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    @if($selectedUser && ! $selectedUser->is_admin)
                        <div class="admin-panel__block" style="border: 1px solid rgba(239, 68, 68, .25); background: #fff1f2;">
                            <div class="admin-panel__title" style="color: #b91c1c;">Удаление пользователя</div>
                            <p style="margin-bottom: 1rem; color: #7f1d1d;">Эта операция безвозвратна и удалит все данные выбранного пользователя.</p>
                            <button type="button" class="btn btn--md" style="background: #dc2626; border-color: #b91c1c;" data-action="delete-user-toggle">Удалить пользователя</button>
                            <div data-role="delete-user-confirm" style="display: none; margin-top: 1rem; padding: 1rem; border-radius: 0.75rem; background: #fee2e2; color: #7f1d1d;">
                                <p style="margin-bottom: 0.75rem; font-weight: 600;">Вы точно хотите удалить этого пользователя?</p>
                                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                    <form method="POST" action="{{ route('admin.dashboard.users.destroy', $selectedUser) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--md" style="background: #dc2626; border-color: #b91c1c;">Да</button>
                                    </form>
                                    <button type="button" class="btn btn--md" style="background: #e5e7eb; color: #111827;" data-action="delete-user-cancel">Нет</button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>
</div>

@php
    $supportPersistQuery = collect(request()->query())
        ->except(['support_user', 'message', 'page'])
        ->toArray();
    $supportSelectedName = $supportSelectedUser
        ? ($supportSelectedUser->name ?: ('User #' . $supportSelectedUser->id))
        : null;
@endphp

<div aria-hidden="true" class="popup popup--md" id="support-modal">
    <div class="popup__wrapper">
        <div class="popup__content">
            <button class="popup__close" data-close type="button">
                <svg fill="none" height="20" viewBox="0 0 20 20" width="20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L19 19M19 1L1 19" stroke="black" stroke-linecap="round" stroke-width="2"></path>
                </svg>
            </button>
            <div class="modal-content modal-content--support">
                <div class="modal-content__top">
                    <div class="logo"><img alt="logo" src="{{ asset('personal-acc/img/logo.svg') }}"></div>
                    <div class="modal-content__text">
                        <p>Чат с клиентами</p>
                    </div>
                </div>
                <div class="modal-content__body">
                    @if($supportErrors && $supportErrors->any())
                        <div class="admin-support__alert">{{ $supportErrors->first() }}</div>
                    @endif

                    <div class="admin-support" data-support-chat
                        data-support-threads-url="{{ route('admin.dashboard.support.threads') }}"
                        data-support-messages-url="{{ route('admin.dashboard.support.messages') }}"
                        data-support-send-url="{{ route('admin.dashboard.support.messages.store') }}"
                        data-selected-user-id="{{ $supportSelectedUserId ?? '' }}">
                        <aside class="admin-support__sidebar">
                            <div class="admin-support__new">
                                <form method="GET" class="admin-support__new-form" data-support-create-form>
                                    @foreach($supportPersistQuery as $key => $value)
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endforeach
                                    <select name="support_user" class="admin-support__select" data-support-new-select>
                                        <option value="">Новый диалог</option>
                                        @foreach($clientOptions as $id => $name)
                                            <option value="{{ $id }}" @selected((int) $supportSelectedUserId === (int) $id)>{{ $name }} (#{{ $id }})</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn--md admin-support__new-btn" type="submit">Открыть</button>
                                </form>
                            </div>

                            <div class="admin-support__threads" data-support-thread-list>
                                @forelse($supportThreads as $thread)
                                    <button type="button"
                                        class="admin-support__thread-btn {{ (int) $supportSelectedUserId === (int) $thread['user_id'] ? 'is-active' : '' }}"
                                        data-support-thread
                                        data-user-id="{{ $thread['user_id'] }}">
                                        <span class="admin-support__thread-name" data-thread-name>{{ $thread['name'] }}</span>
                                        <span class="admin-support__thread-meta" data-thread-meta>#{{ $thread['user_id'] }} · {{ $thread['last_at'] ?? '—' }}</span>
                                    </button>
                                @empty
                                    <p class="admin-support__empty" data-support-empty-state>Диалоги пока не начинались.</p>
                                @endforelse
                            </div>
                        </aside>

                        <section class="admin-support__conversation">
                            <div class="admin-support__conversation-header">
                                <div>
                                    <div class="admin-support__title">Диалог</div>
                                    <div class="admin-support__subtitle" data-support-subtitle>
                                        @if($supportSelectedUser)
                                            {{ $supportSelectedName }} · #{{ $supportSelectedUser->id }}
                                        @else
                                            Выберите клиента слева, чтобы открыть переписку
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="admin-support__alert admin-support__alert--inline" data-support-error style="display:none;"></div>

                            <div class="admin-support__messages" data-support-messages>
                                @forelse($supportMessages as $message)
                                    <div class="admin-support__bubble {{ ($message['direction'] ?? '') === 'inbound' ? 'admin-support__bubble--admin' : 'admin-support__bubble--client' }}">
                                        <div class="admin-support__bubble-meta">
                                            <span class="admin-support__bubble-author">
                                                {{ ($message['direction'] ?? '') === 'inbound' ? 'Администратор' : (($message['user_name'] ?? null) ?: ('User #' . ($message['user_id'] ?? ''))) }}
                                            </span>
                                            <span class="admin-support__bubble-time">{{ $message['created_at'] ?? '' }}</span>
                                        </div>
                                        <div class="admin-support__bubble-text">{!! nl2br(e($message['message'] ?? '')) !!}</div>
                                    </div>
                                @empty
                                    <div class="admin-support__empty" data-support-empty>
                                        @if($supportSelectedUserId)
                                            В этом диалоге ещё нет сообщений.
                                        @else
                                            Нет открытого диалога.
                                        @endif
                                    </div>
                                @endforelse
                            </div>

                            <form method="POST" action="{{ route('admin.dashboard.support') }}" class="admin-support__form" data-support-form>
                                @csrf
                                <input type="hidden" name="user_id" value="{{ old('user_id', $supportSelectedUserId) }}" data-support-user-input>
                                <div class="field @if($supportErrors && $supportErrors->has('message')) has-error @endif">
                                    <textarea name="message" rows="3" placeholder="Введите сообщение..." @disabled(! $supportSelectedUserId)>{{ old('message') }}</textarea>
                                    @error('message', 'support')
                                        <span class="error-message">{{ $message }}</span>
                                    @enderror
                                </div>
                                <button class="btn btn--md" type="submit" @disabled(! $supportSelectedUserId)>Отправить</button>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div aria-hidden="true" class="popup" id="withdraw-modal">
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
                        <p>Создать заявку на вывод средств</p>
                    </div>
                </div>
                <div class="modal-content__body">
                    @if($withdrawalErrors && $withdrawalErrors->any())
                        <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fef2f2; color: #b91c1c; border-radius: 0.75rem;">{{ $withdrawalErrors->first() }}</div>
                    @endif
                    <div class="tabs" data-tabs data-tabs-animate="300">
                        <nav class="tabs__navigation" data-tabs-titles>
                            <button class="tabs__title {{ $withdrawalTab === 'card' ? '_tab-active' : '' }}" type="button" data-tabs-title>
                                <span>На банковскую карту</span>
                            </button>
                            <button class="tabs__title {{ $withdrawalTab === 'bank' ? '_tab-active' : '' }}" type="button" data-tabs-title>
                                <span>По IBAN</span>
                            </button>
                            <button class="tabs__title {{ $withdrawalTab === 'crypto' ? '_tab-active' : '' }}" type="button" data-tabs-title>
                                <span>В криптовалюте</span>
                            </button>
                        </nav>
                        <div class="tabs__content" data-tabs-body>
                            <div class="tabs__body" data-tabs-item @if($withdrawalTab !== 'card') hidden @endif>
                                <form method="POST" action="{{ route('admin.dashboard.withdrawals.store') }}">
                                    @csrf
                                    <input type="hidden" name="method" value="card">
                                    <input type="hidden" name="user_id" value="{{ old('user_id', $selectedUserId) }}">
                                    <div class="field">
                                        <select name="from_account_id">
                                            <option value="main" @selected($createWithdrawalAccount === 'main')>{{ $mainBalanceOptionLabel }}</option>
                                            @foreach($accounts as $account)
                                                @php
                                                    $optionValue = (string) $account->id;
                                                    $optionLabel = ($account->number ?? '—') . ' • ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
                                                @endphp
                                                <option value="{{ $account->id }}" @selected($createWithdrawalAccount === $optionValue)>{{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                        @error('from_account_id', 'withdrawal')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="field">
                                        <input name="details[card_number]" placeholder="1111 2222 3333 4444" type="text" value="{{ old('details.card_number') }}">
                                        @error('details.card_number', 'withdrawal')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="field">
                                        <input name="details[card_holder]" placeholder="Fullname card holder" type="text" value="{{ old('details.card_holder') }}">
                                        @error('details.card_holder', 'withdrawal')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="field">
                                        <input name="amount" placeholder="Amount" type="number" step="0.01" min="0" value="{{ old('amount') }}">
                                        @error('amount', 'withdrawal')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <button class="btn btn--md" type="submit" @disabled(! $selectedUser)>Отправить</button>
                                </form>
                            </div>
                            <div class="tabs__body" data-tabs-item @if($withdrawalTab !== 'bank') hidden @endif>
                                <form method="POST" action="{{ route('admin.dashboard.withdrawals.store') }}">
                                    @csrf
                                    <input type="hidden" name="method" value="bank">
                                    <input type="hidden" name="user_id" value="{{ old('user_id', $selectedUserId) }}">
                                    <div class="field">
                                        <select name="from_account_id">
                                            <option value="main" @selected($createWithdrawalAccount === 'main')>{{ $mainBalanceOptionLabel }}</option>
                                            @foreach($accounts as $account)
                                                @php
                                                    $optionValue = (string) $account->id;
                                                    $optionLabel = ($account->number ?? '—') . ' • ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
                                                @endphp
                                                <option value="{{ $account->id }}" @selected($createWithdrawalAccount === $optionValue)>{{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                        @error('from_account_id', 'withdrawal')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="field">
                                        <input name="details[iban]" placeholder="Enter IBAN" type="text" value="{{ old('details.iban') }}">
                                        @error('details.iban', 'withdrawal')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="field">
                                        <input name="details[bic]" placeholder="BIC code" type="text" value="{{ old('details.bic') }}">
                                        @error('details.bic', 'withdrawal')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="field">
                                        <input name="details[holder]" placeholder="Fullname bank account holder" type="text" value="{{ old('details.holder') }}">
                                    </div>
                                    <div class="field">
                                        <input name="details[country]" placeholder="Country" type="text" value="{{ old('details.country') }}">
                                    </div>
                                    <div class="field">
                                        <input name="details[bank_name]" placeholder="Name of the bank" type="text" value="{{ old('details.bank_name') }}">
                                    </div>
                                    <div class="field">
                                        <input name="amount" placeholder="Amount" type="number" step="0.01" min="0" value="{{ old('amount') }}">
                                        @error('amount', 'withdrawal')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <button class="btn btn--md" type="submit" @disabled(! $selectedUser)>Отправить</button>
                                </form>
                            </div>
                            <div class="tabs__body" data-tabs-item @if($withdrawalTab !== 'crypto') hidden @endif>
                                <form method="POST" action="{{ route('admin.dashboard.withdrawals.store') }}" class="form-crypto">
                                    @csrf
                                    <input type="hidden" name="method" value="crypto">
                                    <input type="hidden" name="user_id" value="{{ old('user_id', $selectedUserId) }}">
                                    <div class="field">
                                        <select name="from_account_id">
                                            <option value="main" @selected($createWithdrawalAccount === 'main')>{{ $mainBalanceOptionLabel }}</option>
                                            @foreach($accounts as $account)
                                                @php
                                                    $optionValue = (string) $account->id;
                                                    $optionLabel = ($account->number ?? '—') . ' • ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
                                                @endphp
                                                <option value="{{ $account->id }}" @selected($createWithdrawalAccount === $optionValue)>{{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                        @error('from_account_id', 'withdrawal')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="field">
                                        <input name="details[address]" placeholder="Deposit address" type="text" value="{{ old('details.address') }}">
                                        @error('details.address', 'withdrawal')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="field">
                                        <input name="details[network]" placeholder="Network" type="text" value="{{ old('details.network') }}">
                                    </div>
                                    <div class="field">
                                        <input name="details[coin]" placeholder="Coin" type="text" value="{{ old('details.coin') }}">
                                    </div>
                                    <div class="field">
                                        <input name="amount" placeholder="Amount" type="number" step="0.01" min="0" value="{{ old('amount') }}">
                                        @error('amount', 'withdrawal')
                                            <span class="error-message">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <button class="btn btn--md" type="submit" @disabled(! $selectedUser)>Отправить</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                        <p>Сообщить о нарушении</p>
                    </div>
                </div>
                <div class="modal-content__body">
                    @if($fraudErrors && $fraudErrors->any())
                        <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fef2f2; color: #b91c1c; border-radius: 0.75rem;">{{ $fraudErrors->first() }}</div>
                    @endif
                    <form method="POST" action="{{ route('admin.dashboard.fraud-claims.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="field">
                            <select name="user_id" @disabled(! $hasClients)>
                                <option value="">Выберите клиента</option>
                                @foreach($clientOptions as $id => $name)
                                    <option value="{{ $id }}" @selected(old('user_id', $selectedUserId) == (int) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <textarea name="details" placeholder="Опишите нарушение" rows="6">{{ old('details') }}</textarea>
                            @error('details', 'fraud')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="field field--upload" data-file-upload>
                            <label class="modal-content__file">
                                <input hidden type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.webp,.gif,.doc,.docx" data-file-upload-input data-max-files="5">
                                <span data-file-upload-label>Прикрепить файлы (до 5)</span>
                            </label>
                            <ul class="modal-content__file-list" data-file-upload-list></ul>
                            @php
                                $attachmentError = $fraudErrors ? ($fraudErrors->first('attachments') ?: $fraudErrors->first('attachments.*')) : null;
                            @endphp
                            @if($attachmentError)
                                <span class="error-message">{{ $attachmentError }}</span>
                            @endif
                        </div>
                        <button class="btn" type="submit" @disabled(! $hasClients)>Отправить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div aria-hidden="true" class="popup popup--sm" id="create-modal">
    <div class="popup__wrapper">
        <div class="popup__content">
            <div class="create-account">
                <div class="create-account__title">Создание нового счёта</div>
                @if($accountErrors && $accountErrors->any())
                    <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fef2f2; color: #b91c1c; border-radius: 0.75rem;">{{ $accountErrors->first() }}</div>
                @endif
                <form action="{{ route('admin.dashboard.accounts.store') }}" method="POST" class="create-account__form">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ old('user_id', $selectedUserId) }}">

                    <div class="field">
                        <input name="number" placeholder="Номер счёта" type="text" value="{{ old('number') }}">
                        @error('number', 'account')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <select name="type">
                            <option value="">Тип счёта</option>
                            @foreach($accountTypeOptions as $code => $label)
                                <option value="{{ $code }}" @selected(old('type') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type', 'account')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <input name="balance" placeholder="Баланс" type="number" step="0.01" min="0" value="{{ old('balance') }}">
                        @error('balance', 'account')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <select name="currency">
                            <option value="">Валюта (по умолчанию {{ $selectedUserCurrency }})</option>
                            @foreach($currencyOptions as $code => $label)
                                <option value="{{ $code }}" @selected(old('currency') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('currency', 'account')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <input name="organization" placeholder="Организация" type="text" value="{{ old('organization') }}">
                        @error('organization', 'account')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <input name="bank" placeholder="Банк" type="text" value="{{ old('bank') }}">
                    </div>

                    <div class="field">
                        <input name="client_initials" placeholder="Инициалы клиента" type="text" value="{{ old('client_initials') }}">
                        @error('client_initials', 'account')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <input name="broker_initials" placeholder="Инициалы брокера" type="text" value="{{ old('broker_initials') }}">
                        @error('broker_initials', 'account')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <input name="term" placeholder="Срок действия" type="date" value="{{ old('term') }}">
                        @error('term', 'account')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <select name="status">
                            <option value="">Статус</option>
                            @foreach($accountStatusOptions as $code => $label)
                                <option value="{{ $code }}" @selected(old('status') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status', 'account')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="checkbox">
                        <input class="checkbox__input" id="account_is_default" name="is_default" type="checkbox" value="1" @checked(old('is_default'))>
                        <label class="checkbox__label" for="account_is_default"><span class="checkbox__text">Сделать основным</span></label>
                    </div>

                    <button class="btn btn--md" type="submit" @disabled(! $selectedUser)>Добавить счёт</button>
                </form>
            </div>
        </div>
</div>
</div>
@endsection

@if($selectedUser)
    <div aria-hidden="true" class="popup popup--sm" id="create-transaction-modal">
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
                            <p>Создать транзакцию</p>
                        </div>
                    </div>
                    <div class="modal-content__body">
                        @if($transactionErrors && $transactionErrors->any())
                            <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fef2f2; color: #b91c1c; border-radius: 0.75rem;">{{ $transactionErrors->first() }}</div>
                        @endif
                        <form method="POST" action="{{ route('admin.dashboard.transactions.store') }}">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $selectedUserId }}">
                            @php
                                $createTransactionAccount = old('account_id');
                                if ($createTransactionAccount === null || $createTransactionAccount === '') {
                                    $createTransactionAccount = 'main';
                                } else {
                                    $createTransactionAccount = (string) $createTransactionAccount;
                                }
                            @endphp
                            <div class="field">
                                <select name="account_id">
                                    <option value="main" @selected($createTransactionAccount === 'main')>{{ $mainBalanceOptionLabel }}</option>
                                    @foreach($accounts as $account)
                                        @php
                                            $optionValue = (string) $account->id;
                                            $optionLabel = ($account->number ?? '—') . ' • ' . ($account->currency ?? 'EUR') . ' ' . number_format((float) $account->balance, 2, '.', ' ');
                                        @endphp
                                        <option value="{{ $account->id }}" @selected($createTransactionAccount === $optionValue)>{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                                @error('account_id', 'transaction')
                                    <span class="error-message">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="field">
                                <input name="from" placeholder="От" type="text" value="{{ old('from') }}" required>
                                @error('from', 'transaction')
                                    <span class="error-message">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="field">
                                <input name="to" placeholder="Кому" type="text" value="{{ old('to') }}" required>
                                @error('to', 'transaction')
                                    <span class="error-message">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="field">
                                <select name="type" required>
                                    @foreach(['classic' => 'classic', 'fast' => 'fast', 'conversion' => 'conversion', 'hold' => 'hold'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ strtoupper($label) }}</option>
                                    @endforeach
                                </select>
                                @error('type', 'transaction')
                                    <span class="error-message">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="field">
                                <input name="amount" placeholder="Сумма" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                                @error('amount', 'transaction')
                                    <span class="error-message">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="field">
                                <select name="status" required>
                                    @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'blocked' => 'Blocked', 'hold' => 'Hold'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status') === $value)>{{ strtoupper($label) }}</option>
                                    @endforeach
                                </select>
                                @error('status', 'transaction')
                                    <span class="error-message">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="field">
                                <input name="created_at" type="datetime-local" value="{{ old('created_at', now()->format('Y-m-d\TH:i')) }}" required>
                                @error('created_at', 'transaction')
                                    <span class="error-message">{{ $message }}</span>
                                @enderror
                            </div>
                            <button class="btn btn--md" type="submit">Создать</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@push('scripts')
<script>
    (function () {
        function toggleFields(form, enabled) {
            form.querySelectorAll('[data-editable]').forEach(function (element) {
                var mode = element.dataset.editable;
                var tag = element.tagName.toLowerCase();
                var type = (element.getAttribute('type') || '').toLowerCase();

                if (mode === 'toggle') {
                    element.disabled = !enabled;
                    return;
                }

                var shouldDisable = (mode === 'disabled') || tag === 'select' || type === 'checkbox' || type === 'radio';
                if (shouldDisable) {
                    element.disabled = !enabled;
                } else {
                    element.readOnly = !enabled;
                }

                if (tag === 'select') {
                    var wrapper = element.closest('.select');
                    if (wrapper) {
                        if (!enabled) {
                            wrapper.classList.add('_select-disabled');
                            var title = wrapper.querySelector('.select__title');
                            if (title) {
                                title.disabled = true;
                            }
                        } else {
                            wrapper.classList.remove('_select-disabled');
                            var titleEnabled = wrapper.querySelector('.select__title');
                            if (titleEnabled) {
                                titleEnabled.disabled = false;
                            }
                        }
                    }
                }
            });
        }

        function setupEditableForm(formId) {
            var form = document.getElementById(formId);
            if (!form) {
                return;
            }

            var editButton = form.querySelector('[data-action$="edit"]');
            var cancelButton = form.querySelector('[data-action$="cancel"]');
            var actions = form.querySelector('[data-role$="actions"]');

            function setEditing(state) {
                form.dataset.editing = state ? 'true' : 'false';
                if (actions) {
                    actions.style.display = state ? 'flex' : 'none';
                }
                if (editButton) {
                    editButton.style.display = state ? 'none' : '';
                }
                toggleFields(form, state);
            }

            setEditing(form.dataset.editing === 'true');

            if (editButton) {
                editButton.addEventListener('click', function () {
                    setEditing(true);
                });
            }

            if (cancelButton) {
                cancelButton.addEventListener('click', function () {
                    form.reset();
                    form.dispatchEvent(new CustomEvent('dashboard:form-reset'));
                    setEditing(false);
                });
            }
        }

        setupEditableForm('admin-user-edit-form');
        setupEditableForm('admin-account-edit-form');
        setupEditableForm('admin-transaction-edit-form');
        setupEditableForm('admin-withdrawal-edit-form');

        var userForm = document.getElementById('admin-user-edit-form');
        var accountForm = document.getElementById('admin-account-edit-form');

        var userCurrencySpan = userForm ? userForm.querySelector('[data-role="user-balance-currency"]') : null;
        var userCurrencySelect = userForm ? userForm.querySelector('select[name="currency"]') : null;

        var accountCurrencySpan = accountForm ? accountForm.querySelector('[data-role="account-balance-currency"]') : null;
        var accountCurrencySelect = accountForm ? accountForm.querySelector('select[name="currency"]') : null;

        var currentUserCurrency = '';
        if (userCurrencySelect && userCurrencySelect.value) {
            currentUserCurrency = userCurrencySelect.value;
        } else if (userCurrencySpan) {
            currentUserCurrency = userCurrencySpan.getAttribute('data-default') || '';
        }

        function updateUserCurrencyDisplay() {
            if (!userCurrencySpan) {
                return;
            }

            var fallback = userCurrencySpan.getAttribute('data-default') || '';
            var value = userCurrencySelect && userCurrencySelect.value ? userCurrencySelect.value : (currentUserCurrency || fallback);
            userCurrencySpan.textContent = value || fallback || '—';
        }

        function updateAccountCurrencyDisplay() {
            if (!accountCurrencySpan) {
                return;
            }

            var fallback = accountCurrencySpan.getAttribute('data-default') || '';
            var value = accountCurrencySelect && accountCurrencySelect.value ? accountCurrencySelect.value : fallback;
            accountCurrencySpan.textContent = value || fallback || '—';
        }

        function syncAccountCurrencyWithUser(previousCurrency) {
            if (!accountForm || !accountCurrencySpan) {
                return;
            }

            var defaultOption = accountCurrencySelect ? accountCurrencySelect.querySelector('option[value=""]') : null;
            if (defaultOption) {
                if (!defaultOption.getAttribute('data-original-label')) {
                    defaultOption.setAttribute('data-original-label', defaultOption.textContent);
                }
                var baseLabel = defaultOption.getAttribute('data-original-label');
                defaultOption.textContent = currentUserCurrency
                    ? 'Как у пользователя (' + currentUserCurrency + ')'
                    : (baseLabel || 'Как у пользователя');
            }

            var manualChange = accountForm.dataset.currencyTouched === 'true';
            var prevValue = previousCurrency || '';
            var shouldSyncValue = !manualChange || !accountCurrencySelect || accountCurrencySelect.value === '' || accountCurrencySelect.value === prevValue;

            if (accountCurrencySelect && shouldSyncValue) {
                if (currentUserCurrency && accountCurrencySelect.querySelector('option[value="' + currentUserCurrency + '"]')) {
                    accountCurrencySelect.value = currentUserCurrency;
                } else if (!manualChange) {
                    accountCurrencySelect.value = '';
                }
                accountForm.dataset.currencyTouched = 'false';
            }

            if (currentUserCurrency) {
                accountCurrencySpan.setAttribute('data-default', currentUserCurrency);
            }

            updateAccountCurrencyDisplay();
        }

        if (userForm) {
            if (!userForm.dataset.editing) {
                userForm.dataset.editing = 'false';
            }

            if (userCurrencySelect) {
                userCurrencySelect.addEventListener('change', function () {
                    var previous = currentUserCurrency;
                    currentUserCurrency = this.value || '';
                    updateUserCurrencyDisplay();
                    syncAccountCurrencyWithUser(previous);
                });
            }

            userForm.addEventListener('dashboard:form-reset', function () {
                currentUserCurrency = userCurrencySpan ? (userCurrencySpan.getAttribute('data-default') || '') : '';
                updateUserCurrencyDisplay();
                syncAccountCurrencyWithUser();
            });

            updateUserCurrencyDisplay();
        }

        if (accountForm) {
            if (!accountForm.dataset.editing) {
                accountForm.dataset.editing = 'false';
            }
            if (!accountForm.dataset.currencyTouched) {
                accountForm.dataset.currencyTouched = 'false';
            }

            if (accountCurrencySelect) {
                accountCurrencySelect.addEventListener('change', function () {
                    accountForm.dataset.currencyTouched = 'true';
                    updateAccountCurrencyDisplay();
                });
            }

            accountForm.addEventListener('dashboard:form-reset', function () {
                accountForm.dataset.currencyTouched = 'false';
                updateAccountCurrencyDisplay();
                syncAccountCurrencyWithUser();
            });

            updateAccountCurrencyDisplay();
        }

        syncAccountCurrencyWithUser();

        function escapeHtml(value) {
            if (value === null || value === undefined) {
                return '';
            }
            return String(value).replace(/[&<>"']/g, function (match) {
                var map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return map[match] || match;
            });
        }

        function renderTextWithBreaks(value) {
            return escapeHtml(value).replace(/\n/g, '<br>');
        }

        function scrollSupportMessages() {
            var modal = document.getElementById('support-modal');
            if (!modal) {
                return;
            }
            var container = modal.querySelector('.admin-support__messages');
            if (container) {
                setTimeout(function () {
                    container.scrollTop = container.scrollHeight;
                }, 40);
            }
        }

        scrollSupportMessages();

        document.addEventListener('afterPopupOpen', function (event) {
            if (event.detail && event.detail.popup && event.detail.popup.targetOpen && event.detail.popup.targetOpen.element && event.detail.popup.targetOpen.element.id === 'support-modal') {
                scrollSupportMessages();
            }
        });

        var adminSupport = document.querySelector('[data-support-chat]');
        if (adminSupport) {
            var threadsUrl = adminSupport.getAttribute('data-support-threads-url') || '';
            var messagesUrl = adminSupport.getAttribute('data-support-messages-url') || '';
            var sendUrl = adminSupport.getAttribute('data-support-send-url') || '';
            var selectedUserId = adminSupport.getAttribute('data-selected-user-id') || '';
            var threadsContainer = adminSupport.querySelector('[data-support-thread-list]');
            var subtitle = adminSupport.querySelector('[data-support-subtitle]');
            var errorBox = adminSupport.querySelector('[data-support-error]');
            var messagesContainer = adminSupport.querySelector('[data-support-messages]');
            var messageForm = adminSupport.querySelector('[data-support-form]');
            var messageTextarea = messageForm ? messageForm.querySelector('textarea[name="message"]') : null;
            var messageSubmit = messageForm ? messageForm.querySelector('button[type="submit"]') : null;
            var messageUserInput = messageForm ? messageForm.querySelector('[data-support-user-input]') : null;
            var createForm = adminSupport.querySelector('[data-support-create-form]');
            var newSelect = adminSupport.querySelector('[data-support-new-select]');
            var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            var isLoadingAdminMessages = false;

            function showAdminError(text) {
                if (!errorBox) {
                    return;
                }
                if (text) {
                    errorBox.style.display = 'block';
                    errorBox.textContent = text;
                } else {
                    errorBox.style.display = 'none';
                    errorBox.textContent = '';
                }
            }

            function updateSubtitle(user) {
                if (!subtitle) {
                    return;
                }

                if (user && user.id) {
                    var label = user.label || user.name || ('User #' + user.id);
                    subtitle.textContent = label + ' · #' + user.id;
                } else {
                    subtitle.textContent = 'Выберите клиента слева, чтобы открыть переписку';
                }
            }

            function updateFormState(enabled) {
                if (messageTextarea) {
                    messageTextarea.disabled = !enabled;
                }
                if (messageSubmit) {
                    messageSubmit.disabled = !enabled;
                }
            }

            function setSelectedThread(userId) {
                var buttons = adminSupport.querySelectorAll('[data-support-thread]');
                buttons.forEach(function (btn) {
                    if (btn.dataset.userId === String(userId)) {
                        btn.classList.add('is-active');
                    } else {
                        btn.classList.remove('is-active');
                    }
                });
            }

            function bindThreadButtons() {
                var buttons = adminSupport.querySelectorAll('[data-support-thread]');
                buttons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        var userId = button.dataset.userId;
                        if (!userId) {
                            return;
                        }
                        loadAdminMessages(userId);
                    });
                });
            }

            function renderAdminThreads(threads) {
                if (!threadsContainer) {
                    return;
                }

                threadsContainer.innerHTML = '';

                if (!threads || !threads.length) {
                    var empty = document.createElement('p');
                    empty.className = 'admin-support__empty';
                    empty.textContent = 'Диалоги пока не начинались.';
                    threadsContainer.appendChild(empty);
                    return;
                }

                threads.forEach(function (thread) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'admin-support__thread-btn' + (String(thread.user_id) === String(selectedUserId) ? ' is-active' : '');
                    button.setAttribute('data-support-thread', '');
                    button.dataset.userId = thread.user_id;

                    var name = document.createElement('span');
                    name.className = 'admin-support__thread-name';
                    name.setAttribute('data-thread-name', '');
                    name.textContent = thread.name;

                    var meta = document.createElement('span');
                    meta.className = 'admin-support__thread-meta';
                    meta.setAttribute('data-thread-meta', '');
                    meta.textContent = '#' + thread.user_id + ' · ' + (thread.last_at || '—');

                    button.appendChild(name);
                    button.appendChild(meta);
                    threadsContainer.appendChild(button);
                });

                bindThreadButtons();
            }

            function renderAdminMessages(messages, options) {
                if (!messagesContainer) {
                    return;
                }

                messagesContainer.innerHTML = '';

                if (!messages || !messages.length) {
                    var empty = document.createElement('div');
                    empty.className = 'admin-support__empty';
                    empty.textContent = selectedUserId ? 'В этом диалоге ещё нет сообщений.' : 'Нет открытого диалога.';
                    messagesContainer.appendChild(empty);
                    return;
                }

                messages.forEach(function (message) {
                    var bubble = document.createElement('div');
                    bubble.className = 'admin-support__bubble' + ((message.direction === 'inbound') ? ' admin-support__bubble--admin' : ' admin-support__bubble--client');

                    var meta = document.createElement('div');
                    meta.className = 'admin-support__bubble-meta';

                    var author = document.createElement('span');
                    author.className = 'admin-support__bubble-author';
                    author.textContent = (message.direction === 'inbound') ? 'Администратор' : (message.user_name || ('User #' + (message.user_id ?? '')));

                    var time = document.createElement('span');
                    time.className = 'admin-support__bubble-time';
                    time.textContent = message.created_at || '';

                    meta.appendChild(author);
                    meta.appendChild(time);

                    var body = document.createElement('div');
                    body.className = 'admin-support__bubble-text';
                    body.innerHTML = renderTextWithBreaks(message.message || '');

                    bubble.appendChild(meta);
                    bubble.appendChild(body);

                    messagesContainer.appendChild(bubble);
                });

                if (!options || !options.silent) {
                    scrollSupportMessages();
                }
            }

            function fetchThreads() {
                if (!threadsUrl) {
                    return;
                }

                fetch(threadsUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        return response.ok ? response.json() : null;
                    })
                    .then(function (data) {
                        if (data && Array.isArray(data.threads)) {
                            renderAdminThreads(data.threads);
                        }
                    })
                    .catch(function () {
                        /* ignore */
                    });
            }

            function loadAdminMessages(userId, options) {
                if (!messagesUrl || !userId || isLoadingAdminMessages) {
                    return;
                }

                isLoadingAdminMessages = true;
                showAdminError('');

                var params = new URLSearchParams({ user_id: userId });

                fetch(messagesUrl + '?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        if (!response.ok) {
                            if (response.status === 404) {
                                throw new Error('Клиент не найден.');
                            }
                            throw new Error('Не удалось загрузить сообщения.');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        selectedUserId = String(data.user?.id || userId);
                        if (messageUserInput) {
                            messageUserInput.value = selectedUserId;
                        }
                        updateFormState(true);
                        updateSubtitle(data.user);
                        renderAdminMessages(data.messages || [], options);
                        setSelectedThread(selectedUserId);
                    })
                   .catch(function (error) {
                       showAdminError(error.message || 'Не удалось загрузить сообщения.');
                       updateFormState(false);
                       updateSubtitle(null);
                        selectedUserId = '';
                        if (messageUserInput) {
                            messageUserInput.value = '';
                        }
                        renderAdminMessages([], { silent: true });
                    })
                    .finally(function () {
                        isLoadingAdminMessages = false;
                    });
            }

            function sendAdminMessage() {
                if (!messageForm || !sendUrl || !selectedUserId) {
                    return;
                }

                var messageValue = messageTextarea ? messageTextarea.value.trim() : '';
                if (!messageValue) {
                    showAdminError('Введите сообщение.');
                    return;
                }

                var formData = new FormData(messageForm);
                if (!formData.has('user_id')) {
                    formData.set('user_id', selectedUserId);
                }

                showAdminError('');

                fetch(sendUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: formData
                })
                    .then(function (response) {
                        if (!response.ok) {
                            if (response.status === 422) {
                                return response.json().then(function (data) {
                                    var errors = data?.errors;
                                    var message = data?.message || (errors ? Object.values(errors)[0]?.[0] : null);
                                    throw new Error(message || 'Не удалось отправить сообщение.');
                                });
                            }

                            throw new Error('Не удалось отправить сообщение.');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        if (messageTextarea) {
                            messageTextarea.value = '';
                        }
                        renderAdminMessages(data.messages || []);
                        fetchThreads();
                    })
                    .catch(function (error) {
                        showAdminError(error.message || 'Не удалось отправить сообщение.');
                    });
            }

            if (messageForm) {
                messageForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    sendAdminMessage();
                });
            }

            if (createForm) {
                createForm.addEventListener('submit', function (event) {
                    if (newSelect) {
                        event.preventDefault();
                        var userId = newSelect.value;
                        if (userId) {
                            loadAdminMessages(userId);
                        }
                    }
                });
            }

            bindThreadButtons();
            updateFormState(!!selectedUserId);

            fetchThreads();

            if (selectedUserId) {
                loadAdminMessages(selectedUserId, { silent: true });
            }

            setInterval(function () {
                var modal = document.getElementById('support-modal');
                if (selectedUserId && modal && modal.classList.contains('popup_show')) {
                    loadAdminMessages(selectedUserId, { silent: true });
                }
            }, 5000);
        }
    })();
</script>
@endpush
