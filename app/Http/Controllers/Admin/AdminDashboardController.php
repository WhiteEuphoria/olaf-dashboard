<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\FraudClaim;
use App\Models\FraudClaimAttachment;
use App\Models\Document;
use App\Models\SupportMessage;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\WithdrawalAttachment;
use App\Services\SupportChatService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminDashboardController extends Controller
{
    public function index(Request $request, SupportChatService $supportChatService): View
    {
        $clients = User::query()
            ->where('is_admin', false)
            ->orderBy('name')
            ->get(['id', 'name', 'verification_status']);

        $labelForUser = static function (User $user): string {
            return $user->name ?: sprintf('User #%d', $user->id);
        };

        $approvedStatuses = ['approved'];

        $clientOptions = $clients
            ->mapWithKeys(static function (User $user) use ($labelForUser) {
                return [$user->id => $labelForUser($user)];
            });

        $pendingClientOptions = $clients
            ->filter(function (User $user) use ($approvedStatuses) {
                return ! in_array(strtolower((string) $user->verification_status), $approvedStatuses, true);
            })
            ->mapWithKeys(static function (User $user) use ($labelForUser) {
                return [$user->id => $labelForUser($user)];
            });

        $selectedUserId = $request->integer('user') ?: null;

        $selectedUser = null;
        $accounts = collect();
        $transactions = collect();
        $transactionOptions = collect();
        $selectedTransaction = null;
        $documents = collect();
        $fraudClaims = collect();
        $withdrawals = collect();
        $withdrawalOptions = collect();
        $selectedWithdrawal = null;

        $selectedAccount = null;
        $selectedAccountValue = 'main';

        $loadSelectedUser = static function (int $userId): ?User {
            return User::query()
                ->where('is_admin', false)
                ->with([
                    'accounts' => function (Relation $query) {
                        $query
                            ->orderByDesc('is_default')
                            ->orderBy('status')
                            ->orderByDesc('created_at');
                    },
                    'transactions' => fn (Relation $query) => $query->latest()->limit(10),
                    'documents' => fn (Relation $query) => $query->latest()->limit(10),
                    'fraudClaims' => fn (Relation $query) => $query->with('attachments')->latest()->limit(5),
                    'withdrawals' => function (Relation $query) {
                        $query->with('fromAccount')->latest()->limit(5);
                    },
                ])
                ->find($userId);
        };

        $validUserIds = $clientOptions->keys()
            ->map(static fn ($key) => (int) $key);

        if ($selectedUserId !== null && $validUserIds->contains($selectedUserId)) {
            $selectedUser = $loadSelectedUser($selectedUserId);
        }

        if (! $selectedUser && $clientOptions->isNotEmpty()) {
            $selectedUserId = (int) $clientOptions->keys()->first();
            $selectedUser = $loadSelectedUser($selectedUserId);
        }

        if (! $selectedUser && $pendingClientOptions->isNotEmpty()) {
            $selectedUserId = (int) $pendingClientOptions->keys()->first();
            $selectedUser = $loadSelectedUser($selectedUserId);
        }

        if ($selectedUser) {
            $accounts = $selectedUser->accounts;
            $transactions = $selectedUser->transactions;
            $documents = $selectedUser->documents;
            $fraudClaims = $selectedUser->fraudClaims;
            $withdrawals = $selectedUser->withdrawals;

            if ($accounts->isNotEmpty()) {
                $requestedAccountRaw = $request->input('account');

                if ($requestedAccountRaw === 'main') {
                    $selectedAccount = null;
                    $selectedAccountValue = 'main';
                } else {
                    $requestedAccountId = is_numeric($requestedAccountRaw)
                        ? (int) $requestedAccountRaw
                        : $request->integer('account');

                    $selectedAccount = $accounts->firstWhere('id', $requestedAccountId) ?? $accounts->first();
                    $selectedAccountValue = $selectedAccount ? (string) $selectedAccount->id : 'main';
                }
            } else {
                $selectedAccountValue = 'main';
            }

            if ($transactions->isNotEmpty()) {
                $transactionOptions = $transactions->mapWithKeys(static function (Transaction $transaction) {
                    $label = Str::upper((string) ($transaction->type ?? 'transaction'));
                    $date = optional($transaction->created_at)->format('d.m.Y H:i') ?? '—';

                    return [$transaction->id => sprintf('%s • %s', $label, $date)];
                });

                $requestedTransactionId = $request->integer('transaction');
                $selectedTransaction = $transactions->firstWhere('id', $requestedTransactionId) ?? $transactions->first();
            }

            if ($withdrawals->isNotEmpty()) {
                $withdrawalOptions = $withdrawals->mapWithKeys(static function (Withdrawal $withdrawal) {
                    $label = Str::upper((string) ($withdrawal->method ?? 'withdrawal'));
                    $date = optional($withdrawal->created_at)->format('d.m.Y H:i') ?? '—';

                    return [$withdrawal->id => sprintf('%s • %s', $label, $date)];
                });

                $requestedWithdrawalId = $request->integer('withdrawal');
                $selectedWithdrawal = $withdrawals->firstWhere('id', $requestedWithdrawalId) ?? $withdrawals->first();
            }
        }

        $primaryAccount = $accounts->firstWhere('is_default', true) ?? $accounts->first();
        $defaultCurrency = config('currencies.default') ?? 'EUR';
        $mainBalanceOptionLabel = __('Main balance');

        if ($selectedUser) {
            $mainBalanceOptionLabel = sprintf(
                '%s — %s %s',
                __('Main balance'),
                $selectedUser->currency ?? $defaultCurrency,
                number_format((float) $selectedUser->main_balance, 2, '.', ' ')
            );
        }

        $accountOptions = collect(['main' => $mainBalanceOptionLabel]);

        if ($accounts->isNotEmpty()) {
            $accountOptions = $accountOptions->merge(
                $accounts->mapWithKeys(fn (Account $account) => [$account->id => $account->number])
            );
        }

        $accountOptions = $accountOptions->all();

        $selectedUserIsPending = $selectedUser
            ? ! in_array(strtolower((string) $selectedUser->verification_status), $approvedStatuses, true)
            : false;

        $supportThreadsCollection = $supportChatService->getThreads(function (?User $threadUser, int $userId) use ($labelForUser) {
            return $threadUser ? $labelForUser($threadUser) : ('User #' . $userId);
        });

        $supportSelectedUserId = $request->integer('support_user') ?: null;
        $supportSelectedUser = null;

        if ($supportSelectedUserId) {
            $supportSelectedUser = $clients->firstWhere('id', $supportSelectedUserId)
                ?: $supportChatService->getUser($supportSelectedUserId);
        }

        if (! $supportSelectedUser && $selectedUser) {
            $supportSelectedUser = $selectedUser;
        }

        if (! $supportSelectedUser && $supportThreadsCollection->isNotEmpty()) {
            $firstThread = $supportThreadsCollection->first();
            if ($firstThread) {
                $supportSelectedUser = $supportChatService->getUser($firstThread['user_id']);
            }
        }

        if ($supportSelectedUser) {
            $supportSelectedUserId = $supportSelectedUser->id;

            if (! $supportThreadsCollection->contains(fn ($thread) => (int) $thread['user_id'] === $supportSelectedUserId)) {
                $supportThreadsCollection->prepend([
                    'user_id' => $supportSelectedUser->id,
                    'name' => $labelForUser($supportSelectedUser),
                    'last_at' => null,
                ]);
            } else {
                $supportThreadsCollection = $supportThreadsCollection->map(function ($thread) use ($supportSelectedUser, $supportSelectedUserId, $labelForUser) {
                    if ((int) $thread['user_id'] === $supportSelectedUserId) {
                        $thread['name'] = $labelForUser($supportSelectedUser);
                    }

                    return $thread;
                });
            }
        } else {
            $supportSelectedUserId = null;
        }

        $supportMessages = [];

        if ($supportSelectedUserId) {
            $supportMessages = $supportChatService->getMessagesForUser($supportSelectedUserId);
        }

        $supportThreads = $supportThreadsCollection
            ->unique(fn ($thread) => $thread['user_id'])
            ->values()
            ->toArray();

        $documentStatusOptions = [
            'pending' => __('Pending'),
            'approved' => __('Approved'),
            'rejected' => __('Rejected'),
        ];

        return view('admin.dashboard', [
            'clientOptions' => $clientOptions,
            'pendingClientOptions' => $pendingClientOptions,
            'selectedUserId' => $selectedUserId,
            'selectedUser' => $selectedUser,
            'accounts' => $accounts,
            'transactions' => $transactions,
            'documents' => $documents,
            'fraudClaims' => $fraudClaims,
            'withdrawals' => $withdrawals,
            'transactionOptions' => $transactionOptions,
            'selectedTransaction' => $selectedTransaction,
            'withdrawalOptions' => $withdrawalOptions,
            'selectedWithdrawal' => $selectedWithdrawal,
            'primaryAccount' => $primaryAccount,
            'selectedAccount' => $selectedAccount,
            'accountOptions' => $accountOptions,
            'selectedAccountValue' => $selectedAccountValue,
            'mainBalanceOptionLabel' => $mainBalanceOptionLabel,
            'selectedUserIsPending' => $selectedUserIsPending,
            'accountTypeOptions' => config('accounts.types', []),
            'withdrawalMethods' => [
                'card' => 'Card',
                'bank' => 'Bank account',
                'crypto' => 'Crypto',
            ],
            'accountStatusOptions' => [
                'Pending' => 'Pending',
                'Active' => 'Active',
                'Hold' => 'Hold',
                'Blocked' => 'Blocked',
            ],
            'currencyOptions' => collect(config('currencies.allowed', []))
                ->mapWithKeys(fn ($currency) => [$currency => $currency])
                ->all(),
            'supportThreads' => $supportThreads,
            'supportSelectedUserId' => $supportSelectedUserId,
            'supportSelectedUser' => $supportSelectedUser,
            'supportMessages' => $supportMessages,
            'documentStatusOptions' => $documentStatusOptions,
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        if ($user->is_admin) {
            abort(404);
        }

        $data = $request->validateWithBag('user', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'currency' => ['required', Rule::in(config('currencies.allowed', []))],
            'main_balance' => ['required', 'numeric', 'min:0'],
            'verification_status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'created_at' => ['required', 'date'],
        ]);

        $originalMainBalance = (float) $user->getOriginal('main_balance');
        $originalCurrency = (string) $user->getOriginal('currency');

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'currency' => $data['currency'],
            'main_balance' => $data['main_balance'],
            'verification_status' => $data['verification_status'],
        ]);

        if (! empty($data['created_at'])) {
            $user->created_at = Carbon::parse($data['created_at']);
        }

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        $mainBalanceChanged = (float) $data['main_balance'] !== $originalMainBalance;
        $currencyChanged = $data['currency'] !== $originalCurrency;

        if ($mainBalanceChanged || $currencyChanged) {
            $primaryAccount = $user->accounts()
                ->orderByDesc('is_default')
                ->orderBy('status')
                ->orderByDesc('created_at')
                ->first();

            if ($primaryAccount) {
                if ($mainBalanceChanged) {
                    $primaryAccount->balance = $data['main_balance'];
                }

                if ($currencyChanged) {
                    $primaryAccount->currency = $user->currency;
                }

                $primaryAccount->save();
            }
        }

        return redirect()
            ->route('admin.dashboard', ['user' => $user->id])
            ->with('status', 'User updated.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($user->is_admin) {
            abort(404);
        }

        DB::transaction(function () use ($user) {
            $user->supportMessages()->delete();
            $user->transactions()->delete();

            $documentsDisk = Document::storageDisk();
            foreach ($user->documents()->get() as $document) {
                if ($document->path && Storage::disk($documentsDisk)->exists($document->path)) {
                    Storage::disk($documentsDisk)->delete($document->path);
                }

                $document->delete();
            }

            foreach ($user->fraudClaims()->with('attachments')->get() as $fraudClaim) {
                if ($fraudClaim->attachments->isNotEmpty()) {
                    $fraudClaim->removeAttachments($fraudClaim->attachments->pluck('id'));
                }

                $fraudClaim->delete();
            }

            foreach ($user->withdrawals()->get() as $withdrawal) {
                $withdrawal->delete();
            }

            foreach ($user->accounts()->get() as $account) {
                $account->delete();
            }

            $user->delete();
        });

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'User deleted.');
    }

    public function updateAccount(Request $request, Account $account): RedirectResponse
    {
        $user = $account->user;

        if (! $user || $user->is_admin) {
            abort(404);
        }

        $data = $request->validateWithBag('account_edit', [
            'number' => ['required', 'string', 'max:255', Rule::unique('accounts', 'number')->ignore($account->id)],
            'type' => ['required', Rule::in(array_keys(config('accounts.types', [])))],
            'balance' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', Rule::in(config('currencies.allowed', []))],
            'organization' => ['required', 'string', 'max:255'],
            'bank' => ['nullable', 'string', 'max:255'],
            'client_initials' => ['required', 'string', 'max:255'],
            'broker_initials' => ['required', 'string', 'max:255'],
            'term' => ['required', 'date'],
            'status' => ['required', Rule::in(['Active', 'Hold', 'Blocked'])],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $account->fill([
            'number' => $data['number'],
            'type' => $data['type'],
            'balance' => $data['balance'],
            'currency' => $data['currency'] ?: $user->currency,
            'organization' => $data['organization'],
            'bank' => $data['bank'],
            'client_initials' => $data['client_initials'],
            'broker_initials' => $data['broker_initials'],
            'term' => $data['term'],
            'status' => $data['status'],
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        $account->save();

        if ($account->is_default) {
            Account::query()
                ->where('user_id', $user->id)
                ->where('id', '!=', $account->id)
                ->update(['is_default' => false]);
        }

        return redirect()
            ->route('admin.dashboard', ['user' => $user->id, 'account' => $account->id])
            ->with('status', 'Account updated.');
    }

    public function updateTransaction(Request $request, Transaction $transaction): RedirectResponse
    {
        $user = $transaction->user;

        if (! $user || $user->is_admin) {
            abort(404);
        }

        $data = $request->validateWithBag('transaction_edit', [
            'created_at' => ['required', 'date'],
            'account_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($user) {
                    if ($value === null || $value === '' || $value === 'main') {
                        return;
                    }

                    if (! is_numeric($value)) {
                        $fail(__('Selected account is invalid.'));
                        return;
                    }

                    $accountId = (int) $value;
                    if (! $user->accounts()->whereKey($accountId)->exists()) {
                        $fail(__('Selected account is invalid.'));
                    }
                },
            ],
            'from' => ['required', 'string', 'max:255'],
            'to' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['classic', 'fast', 'conversion', 'hold'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'status' => ['required', Rule::in(['pending', 'approved', 'blocked', 'hold'])],
        ]);

        $transaction->fill([
            'created_at' => $data['created_at'],
            'account_id' => $this->normalizeAccountId($data['account_id'] ?? null),
            'from' => $data['from'],
            'to' => $data['to'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'status' => $data['status'],
            'currency' => $user->currency ?? (config('currencies.default') ?? 'EUR'),
        ]);

        $transaction->save();

        return redirect()
            ->route('admin.dashboard', ['user' => $user->id])
            ->with('status', 'Transaction updated.');
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('transaction', [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_admin', 0))],
            'account_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value === null || $value === '' || $value === 'main') {
                        return;
                    }

                    if (! is_numeric($value)) {
                        $fail(__('Selected account is invalid.'));
                        return;
                    }

                    $userId = (int) $request->input('user_id');
                    $accountId = (int) $value;
                    $exists = Account::where('user_id', $userId)->whereKey($accountId)->exists();

                    if (! $exists) {
                        $fail(__('Selected account is invalid.'));
                    }
                },
            ],
            'from' => ['required', 'string', 'max:255'],
            'to' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['classic', 'fast', 'conversion', 'hold'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'status' => ['required', Rule::in(['pending', 'approved', 'blocked', 'hold'])],
            'created_at' => ['required', 'date'],
        ]);

        $user = User::query()->where('is_admin', false)->findOrFail($data['user_id']);

        $accountId = $this->normalizeAccountId($data['account_id'] ?? null);

        if ($accountId !== null) {
            $account = Account::query()
                ->where('user_id', $user->id)
                ->find($accountId);

            if (! $account) {
                throw ValidationException::withMessages([
                    'account_id' => 'Выбранный счёт не найден у этого пользователя.',
                ])->errorBag('transaction');
            }

            $accountId = $account->id;
        }

        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->account_id = $accountId;
        $transaction->from = $data['from'];
        $transaction->to = $data['to'];
        $transaction->type = $data['type'];
        $transaction->amount = $data['amount'];
        $transaction->status = $data['status'];
        $transaction->currency = $user->currency ?? (config('currencies.default') ?? 'EUR');
        $transaction->created_at = Carbon::parse($data['created_at']);
        $transaction->save();

        return redirect()
            ->route('admin.dashboard', ['user' => $user->id])
            ->with('status', 'Transaction created.');
    }

    public function updateWithdrawal(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $user = $withdrawal->user;

        if (! $user || $user->is_admin) {
            abort(404);
        }


        $allowedStatus = ['pending', 'approved', 'rejected'];
        $allowedMethods = ['card', 'bank', 'crypto'];

        $data = $request->validateWithBag('withdrawal_edit', [
            'method' => ['required', Rule::in($allowedMethods)],
            'from_account_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($user) {
                    if ($value === null || $value === '' || $value === 'main') {
                        return;
                    }

                    if (! is_numeric($value)) {
                        $fail(__('Selected account is invalid.'));
                        return;
                    }

                    $accountId = (int) $value;
                    if (! $user->accounts()->whereKey($accountId)->exists()) {
                        $fail(__('Selected account is invalid.'));
                    }
                },
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'status' => ['required', Rule::in($allowedStatus)],
            'comment' => ['nullable', 'string', 'max:1000'],
            'requisites' => ['nullable', 'array'],
            'requisites.*' => ['nullable', 'string', 'max:255'],
        ]);

        $withdrawal->fill([
            'method' => $data['method'],
            'amount' => $data['amount'],
            'status' => $data['status'],
        ]);

        $fromAccountId = $data['from_account_id'] ?? null;
        if ($fromAccountId === 'main' || $fromAccountId === '') {
            $fromAccountId = null;
        } elseif ($fromAccountId !== null) {
            $fromAccountId = (int) $fromAccountId;
        }

        $withdrawal->from_account_id = $fromAccountId;

        if (Schema::hasColumn($withdrawal->getTable(), 'comment')) {
            $withdrawal->comment = $data['comment'];
        }

        if (array_key_exists('requisites', $data)) {
            $cleaned = collect($data['requisites'] ?? [])
                ->filter(fn ($value) => filled($value))
                ->all();

            $withdrawal->requisites = $cleaned ? json_encode($cleaned) : null;
        }

        $withdrawal->save();

        return redirect()
            ->route('admin.dashboard', ['user' => $user->id])
            ->with('status', 'Withdrawal updated.');
    }

    public function storeDocument(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('document_create', [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_admin', false)),
            ],
            'files' => ['nullable', 'array', 'min:1', 'max:5'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,gif,svg,webp,pdf,doc,docx', 'max:20480'],
            'document_type' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $user = User::query()->where('is_admin', false)->findOrFail($data['user_id']);

        $files = collect($request->file('files', []))
            ->flatten()
            ->filter(static fn ($file) => $file instanceof UploadedFile);

        if ($files->isEmpty()) {
            throw ValidationException::withMessages([
                'files' => [__('Прикрепите хотя бы один файл.')],
            ])->errorBag('document_create');
        }

        $documentType = $data['document_type'] ? trim($data['document_type']) : 'other';
        $status = $data['status'] ? Str::lower($data['status']) : 'pending';
        $documentDisk = Document::storageDisk();

        $files->each(function (UploadedFile $file) use ($user, $documentType, $status, $documentDisk) {
            $path = $file->store('documents', $documentDisk);

            Document::create([
                'user_id' => $user->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'document_type' => $documentType,
                'status' => $status,
            ]);
        });

        return redirect()
            ->route('admin.dashboard', ['user' => $user->id])
            ->with('status', 'Document uploaded.');
    }

    public function updateDocument(Request $request, Document $document): RedirectResponse
    {
        $user = $document->user;

        if (! $user || $user->is_admin) {
            abort(404);
        }

        $data = $request->validateWithBag('document', [
            'file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,svg,webp,pdf', 'max:20480'],
            'document_type' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $documentDisk = Document::storageDisk();
            $path = $file->store('documents', $documentDisk);

            if ($document->path) {
                Storage::disk($documentDisk)->delete($document->path);
            }

            $document->path = $path;
            $document->original_name = $file->getClientOriginalName();
        }

        if (array_key_exists('document_type', $data)) {
            $document->document_type = $data['document_type'] ? trim($data['document_type']) : ($document->document_type ?: 'other');
        }

        if (array_key_exists('status', $data)) {
            $document->status = $data['status'] ? Str::lower($data['status']) : $document->status;
        }

        $document->save();

        return redirect()
            ->route('admin.dashboard', ['user' => $user->id])
            ->with('status', 'Document updated.');
    }

    public function previewDocument(Document $document)
    {
        if (! $document->path) {
            abort(404);
        }

        $diskName = Document::storageDisk();
        $disk = Storage::disk($diskName);

        if (! $disk->exists($document->path)) {
            abort(404);
        }

        $fullPath = $disk->path($document->path);
        $mimeType = $disk->mimeType($document->path) ?: 'application/octet-stream';
        $filename = $document->original_name ?: basename($document->path);

        try {
            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=604800',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $exception) {
            return response()->stream(function () use ($disk, $document) {
                echo $disk->get($document->path);
            }, 200, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'no-cache',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        }
    }

    public function updateFraudClaim(Request $request, FraudClaim $fraudClaim): RedirectResponse
    {
        $user = $fraudClaim->user;

        if (! $user || $user->is_admin) {
            abort(404);
        }

        $data = $request->validateWithBag('fraud_edit', [
            'details' => ['required', 'string'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'В рассмотрении', 'Одобрено', 'Отклонено'])],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:20480', 'mimes:jpg,jpeg,png,pdf,webp,gif,doc,docx'],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => [
                'integer',
                Rule::exists('fraud_claim_attachments', 'id')->where(fn ($query) => $query->where('fraud_claim_id', $fraudClaim->id)),
            ],
        ]);

        $fraudClaim->fill([
            'details' => $data['details'],
            'status' => $data['status'],
        ]);

        $fraudClaim->save();

        $maxAttachments = 5;
        $removeIds = collect($data['remove_attachments'] ?? [])->map(fn ($id) => (int) $id)->filter();
        $newFiles = collect($request->file('attachments', []))
            ->filter(fn ($file) => $file instanceof UploadedFile);

        $existingCount = $fraudClaim->attachments()->count();
        $removeCount = $removeIds->isEmpty()
            ? 0
            : $fraudClaim->attachments()->whereIn('id', $removeIds)->count();

        if ($existingCount - $removeCount + $newFiles->count() > $maxAttachments) {
            throw ValidationException::withMessages([
                'attachments' => __('You can attach up to :count files.', ['count' => $maxAttachments]),
            ])->errorBag('fraud_edit');
        }

        if ($removeIds->isNotEmpty()) {
            $fraudClaim->removeAttachments($removeIds);
        }

        if ($newFiles->isNotEmpty()) {
            $fraudClaim->addAttachments($newFiles->all());
        }

        return redirect()
            ->route('admin.dashboard', ['user' => $user->id])
            ->with('status', 'Fraud claim updated.');
    }

    public function storeSupport(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('support', [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'min:5'],
        ]);

        $user = User::query()->where('is_admin', false)->findOrFail($data['user_id']);

        SupportMessage::create([
            'user_id' => $user->id,
            'direction' => 'inbound',
            'message' => $data['message'],
        ]);

        return redirect()
            ->route('admin.dashboard', [
                'user' => $user->id,
                'support_user' => $user->id,
            ])
            ->with('status', 'Support message sent.');
    }

    public function storeFraudClaim(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('fraud', [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'details' => ['required', 'string', 'min:10'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:20480', 'mimes:jpg,jpeg,png,pdf,webp,gif,doc,docx'],
        ]);

        $user = User::query()->where('is_admin', false)->findOrFail($data['user_id']);

        $fraudClaim = FraudClaim::create([
            'user_id' => $user->id,
            'details' => $data['details'],
            'status' => 'В рассмотрении',
        ]);

        $fraudClaim->addAttachments($request->file('attachments', []));

        return redirect()
            ->route('admin.dashboard', ['user' => $user->id])
            ->with('status', 'Fraud claim created.');
    }

    public function downloadFraudClaimAttachment(FraudClaim $fraudClaim, FraudClaimAttachment $attachment)
    {
        $user = $fraudClaim->user;

        if (! $user || $user->is_admin || $attachment->fraud_claim_id !== $fraudClaim->id) {
            abort(404);
        }

        $disk = $attachment->disk ?: 'public';

        if (! $attachment->path || ! Storage::disk($disk)->exists($attachment->path)) {
            abort(404);
        }

        $downloadName = $attachment->original_name ?: basename($attachment->path);

        return Storage::disk($disk)->download($attachment->path, $downloadName);
    }

    public function previewFraudClaimAttachment(FraudClaim $fraudClaim, FraudClaimAttachment $attachment)
    {
        $user = $fraudClaim->user;

        if (! $user || $user->is_admin || $attachment->fraud_claim_id !== $fraudClaim->id) {
            abort(404);
        }

        $disk = $attachment->disk ?: 'public';

        if (! $attachment->path || ! Storage::disk($disk)->exists($attachment->path)) {
            abort(404);
        }

        $fullPath = Storage::disk($disk)->path($attachment->path);
        $mimeType = Storage::disk($disk)->mimeType($attachment->path) ?: 'application/octet-stream';
        $filename = $attachment->original_name ?: basename($attachment->path);

        $isImage = str_starts_with($mimeType, 'image/');

        if (! $isImage) {
            return $this->downloadFraudClaimAttachment($fraudClaim, $attachment);
        }

        try {
            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=604800',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $exception) {
            return response()->stream(function () use ($disk, $attachment) {
                echo $disk->get($attachment->path);
            }, 200, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'no-cache',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        }
    }

    public function storeWithdrawal(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('withdrawal', [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'method' => ['required', Rule::in(['card', 'bank', 'crypto'])],
            'from_account_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value === null || $value === '' || $value === 'main') {
                        return;
                    }

                    if (! is_numeric($value)) {
                        $fail(__('Selected account is invalid.'));
                        return;
                    }

                    $userId = (int) $request->input('user_id');
                    $accountId = (int) $value;
                    $exists = Account::where('user_id', $userId)->whereKey($accountId)->exists();

                    if (! $exists) {
                        $fail(__('Selected account is invalid.'));
                    }
                },
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'details' => ['nullable', 'array'],
            'details.*' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->where('is_admin', false)->findOrFail($data['user_id']);

        $details = collect($data['details'] ?? [])
            ->filter(fn ($value) => filled($value))
            ->all();

        $fromAccountId = $data['from_account_id'] ?? null;
        if ($fromAccountId === 'main' || $fromAccountId === '') {
            $fromAccountId = null;
        } elseif ($fromAccountId !== null) {
            $fromAccountId = (int) $fromAccountId;
        }

        Withdrawal::create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'from_account_id' => $fromAccountId,
            'requisites' => $details ? json_encode($details) : null,
            'status' => 'pending',
        ]);
        
        return redirect()
            ->route('admin.dashboard', ['user' => $user->id])
            ->with('status', 'Withdrawal request created.');
    }

    private function normalizeAccountId($value): ?int
    {
        if ($value === null || $value === '' || $value === 'main') {
            return null;
        }

        return (int) $value;
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('account', [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'number' => ['required', 'string', 'max:255', 'unique:accounts,number'],
            'type' => ['required', Rule::in(array_keys(config('accounts.types', [])))],
            'balance' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', Rule::in(config('currencies.allowed', []))],
            'organization' => ['required', 'string', 'max:255'],
            'bank' => ['nullable', 'string', 'max:255'],
            'client_initials' => ['required', 'string', 'max:255'],
            'broker_initials' => ['required', 'string', 'max:255'],
            'term' => ['required', 'date'],
            'status' => ['required', Rule::in(['Active', 'Hold', 'Blocked'])],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $user = User::query()->where('is_admin', false)->findOrFail($data['user_id']);

        $account = Account::create([
            'user_id' => $user->id,
            'number' => $data['number'],
            'type' => $data['type'],
            'balance' => $data['balance'],
            'currency' => $data['currency'] ?: $user->currency,
            'organization' => $data['organization'],
            'bank' => $data['bank'],
            'client_initials' => $data['client_initials'],
            'broker_initials' => $data['broker_initials'],
            'term' => $data['term'],
            'status' => $data['status'],
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        if ($account->is_default) {
            Account::query()
                ->where('user_id', $user->id)
                ->where('id', '!=', $account->id)
                ->update(['is_default' => false]);
        }

        return redirect()
            ->route('admin.dashboard', ['user' => $user->id, 'account' => $account->id])
            ->with('status', 'Account created.');
    }
}
