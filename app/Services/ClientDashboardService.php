<?php

namespace App\Services;

use App\Models\User;
use App\Services\SupportChatService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class ClientDashboardService
{
    public function build(User $user): array
    {
        $user->load([
            'accounts' => fn ($query) => $query
                ->orderByDesc('is_default')
                ->orderBy('status')
                ->orderByDesc('created_at'),
            'transactions' => fn ($query) => $query->latest()->limit(50),
            'withdrawals' => fn ($query) => $query->with('fromAccount')->latest()->limit(50),
        ]);

        $accounts = $user->accounts;
        $transactions = $user->transactions;
        $withdrawals = $user->withdrawals;

        $primaryAccount = $accounts->firstWhere('is_default', true) ?? $accounts->first();
        $totalAccountBalance = (float) $accounts->sum('balance');

        $displayCurrency = optional($primaryAccount)->currency
            ?? $user->currency
            ?? Config::get('currencies.default', 'EUR');

        $country = $user->getAttribute('country');

        $dateOfBirth = null;
        $rawDate = $user->getAttribute('date_of_birth');
        if ($rawDate) {
            if ($rawDate instanceof \DateTimeInterface) {
                $dateOfBirth = Carbon::instance($rawDate);
            } else {
                try {
                    $dateOfBirth = Carbon::parse((string) $rawDate);
                } catch (\Throwable) {
                    $dateOfBirth = null;
                }
            }
        }

        $supportMessages = app(SupportChatService::class)->getMessagesForUser($user->id);

        return [
            'user' => $user,
            'accounts' => $accounts,
            'transactions' => $transactions,
            'withdrawals' => $withdrawals,
            'primaryAccount' => $primaryAccount,
            'totalAccountBalance' => $totalAccountBalance,
            'displayCurrency' => $displayCurrency,
            'country' => $country,
            'dateOfBirth' => $dateOfBirth,
            'mainBalance' => (float) $user->getAttribute('main_balance'),
            'supportMessages' => $supportMessages,
        ];
    }
}
