<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\FraudClaim;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ViolationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $claims = collect();

        if ($user && ! $user->is_admin) {
            $claims = $user->fraudClaims()
                ->with(['attachments' => fn ($query) => $query->latest()])
                ->latest()
                ->limit(10)
                ->get();
        }

        return view('user.violation', [
            'violationClaims' => $claims,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if(! $user, 403);
        abort_if($user->is_admin, 403);

        $data = $request->validateWithBag('violation', [
            'details' => ['required', 'string', 'min:10', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:20480', 'mimes:jpg,jpeg,png,pdf,webp,gif,doc,docx'],
        ]);

        $fraudClaim = FraudClaim::create([
            'user_id' => $user->id,
            'details' => $data['details'],
            'status' => 'В рассмотрении',
        ]);

        $fraudClaim->addAttachments($request->file('attachments', []));

        return back()->with('violation_status', __('Your message has been sent.'));
    }
}
