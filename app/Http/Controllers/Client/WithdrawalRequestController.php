<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class WithdrawalRequestController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        abort_if(! $user || $user->is_admin, 403);

        $status = strtolower((string) $user->verification_status);
        if (! in_array($status, ['approved'], true)) {
            return redirect()->route('user.verify');
        }

        $accounts = $user->accounts()
            ->orderByDesc('is_default')
            ->orderBy('number')
            ->get();

        return view('user.withdraw', [
            'user' => $user,
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if(! $user || $user->is_admin, 403);

        $status = strtolower((string) $user->verification_status);
        if (! in_array($status, ['approved'], true)) {
            return redirect()->route('user.verify');
        }

        $method = (string) $request->input('method', 'card');
        $allowedMethods = ['card', 'iban', 'crypto'];
        if (! in_array($method, $allowedMethods, true)) {
            $method = 'card';
        }

        $bag = 'withdraw_' . $method;

        $baseRules = [
            'method' => ['required', Rule::in($allowedMethods)],
            'amount' => ['required', 'numeric', 'min:1'],
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
                    $exists = $user->accounts()->whereKey($accountId)->exists();

                    if (! $exists) {
                        $fail(__('Selected account is invalid.'));
                    }
                },
            ],
        ];

        $specificRules = [];

        switch ($method) {
            case 'card':
                $specificRules = [
                    'card.number' => ['required', 'string', 'max:32'],
                    'card.holder' => ['required', 'string', 'max:255'],
                ];
                break;
            case 'iban':
                $specificRules = [
                    'iban.iban' => ['required', 'string', 'max:64'],
                    'iban.bic' => ['required', 'string', 'max:32'],
                    'iban.holder' => ['required', 'string', 'max:255'],
                    'iban.country' => ['required', 'string', 'max:150'],
                    'iban.bank' => ['required', 'string', 'max:255'],
                ];
                break;
            case 'crypto':
                $specificRules = [
                    'crypto.address' => ['required', 'string', 'max:255'],
                    'crypto.network' => ['required', 'string', 'max:120'],
                    'crypto.coin' => ['required', 'string', 'max:120'],
                ];
                break;
        }

        $validated = $request->validateWithBag($bag, array_merge($baseRules, $specificRules));

        $requisites = [];

        switch ($method) {
            case 'card':
                $requisites = [
                    'card_number' => preg_replace('/[^0-9]/', '', (string) Arr::get($validated, 'card.number', '')),
                    'card_holder' => Arr::get($validated, 'card.holder'),
                ];
                break;
            case 'iban':
                $requisites = [
                    'iban' => strtoupper(trim((string) Arr::get($validated, 'iban.iban', ''))),
                    'bic' => strtoupper(trim((string) Arr::get($validated, 'iban.bic', ''))),
                    'holder' => Arr::get($validated, 'iban.holder'),
                    'country' => Arr::get($validated, 'iban.country'),
                    'bank_name' => Arr::get($validated, 'iban.bank'),
                ];
                break;
            case 'crypto':
                $requisites = [
                    'address' => Arr::get($validated, 'crypto.address'),
                    'network' => Arr::get($validated, 'crypto.network'),
                    'coin' => Arr::get($validated, 'crypto.coin'),
                ];
                break;
        }

        $fromAccountId = $validated['from_account_id'] ?? null;
        if ($fromAccountId === 'main' || $fromAccountId === '') {
            $fromAccountId = null;
        } elseif ($fromAccountId !== null) {
            $fromAccountId = (int) $fromAccountId;
        }

        Withdrawal::create([
            'user_id' => $user->id,
            'amount' => $validated['amount'],
            'method' => $method,
            'from_account_id' => $fromAccountId,
            'requisites' => $requisites ? json_encode($requisites) : null,
            'status' => 'pending',
        ]);

        $previous = url()->previous();
        if (! $previous || $previous === $request->fullUrl()) {
            $previous = route('user.withdraw');
        }

        return redirect($previous)
            ->with('status', __('Withdrawal request has been submitted and will be reviewed shortly.'))
            ->with('last_withdraw_method', $method);
    }
}
