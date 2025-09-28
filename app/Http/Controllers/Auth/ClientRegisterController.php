<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class ClientRegisterController extends Controller
{
    public function create(): View
    {
        return view('user.auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:150'],
            'last_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:20480'],
        ]);

        $storedFiles = [];
        $documentsDisk = Document::storageDisk();

        try {
            $user = DB::transaction(function () use ($data, $request, &$storedFiles, $documentsDisk) {
                $user = User::create([
                    'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'verification_status' => 'pending',
                    'main_balance' => 0,
                    'currency' => config('currencies.default', 'EUR'),
                ]);

                foreach ($request->file('documents', []) as $uploadedFile) {
                    $path = $uploadedFile->store('documents', $documentsDisk);
                    $storedFiles[] = $path;

                    Document::create([
                        'user_id' => $user->id,
                        'path' => $path,
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'document_type' => 'verification',
                        'status' => 'pending',
                    ]);
                }

                return $user;
            });
        } catch (Throwable $exception) {
            foreach ($storedFiles as $path) {
                Storage::disk($documentsDisk)->delete($path);
            }

            throw $exception;
        }

        Auth::login($user);

        return redirect()
            ->route('user.verify')
            ->with('status', __('Документы отправлены на проверку. Пожалуйста, дождитесь подтверждения.'));
    }
}
