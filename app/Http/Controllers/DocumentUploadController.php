<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\SupportMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentUploadController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()
                ->route('user.login')
                ->with('status', __('Please log in to upload documents.'));
        }

        $data = $request->validate([
            'document' => ['required', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:10240'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $data['document'];
        $documentsDisk = Document::storageDisk();
        $path = $file->store('documents', $documentsDisk);

        Document::create([
            'user_id' => Auth::id(),
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'document_type' => 'manual',
            'status' => 'pending',
        ]);

        if (! empty($data['comment'])) {
            SupportMessage::create([
                'user_id' => Auth::id(),
                'direction' => 'outbound',
                'message' => $data['comment'],
            ]);
        }

        return back()->with('status', __('Document uploaded successfully.'));
    }
}
