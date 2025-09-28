<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\ClientDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ClientDashboardService $service): View|RedirectResponse
    {
        $user = $request->user();

        abort_if(! $user, 403);
        abort_if($user->is_admin, 403);

        $status = strtolower((string) $user->verification_status);
        $isVerified = in_array($status, ['approved'], true);

        if (! $isVerified) {
            return redirect()->route('user.verify');
        }

        return view('user.dashboard', $service->build($user));
    }
}
