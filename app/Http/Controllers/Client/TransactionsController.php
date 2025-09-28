<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class TransactionsController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        abort_if(! $user, 403);
        abort_if($user->is_admin, 403);

        $status = strtolower((string) $user->verification_status);
        $isVerified = in_array($status, ['approved'], true);

        if (! $isVerified) {
            return redirect()->route('user.verify');
        }

        $user->load([
            'accounts' => fn ($query) => $query
                ->orderByDesc('is_default')
                ->orderBy('status')
                ->orderByDesc('created_at'),
        ]);

        $primaryAccount = $user->accounts->firstWhere('is_default', true) ?? $user->accounts->first();

        $displayCurrency = $primaryAccount?->currency
            ?? $user->currency
            ?? Config::get('currencies.default', 'EUR');

        $transactions = $user->transactions()
            ->with('account')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('user.transactions.index', [
            'user' => $user,
            'transactions' => $transactions,
            'displayCurrency' => $displayCurrency,
        ]);
    }
}
