<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Services\SupportChatService;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupportController extends Controller
{
    public function index(Request $request, SupportChatService $supportChatService): JsonResponse
    {
        $user = $request->user();

        abort_if(! $user || $user->is_admin, 403);

        return response()->json([
            'messages' => $supportChatService->getMessagesForUser($user->id),
        ]);
    }

    public function store(Request $request, TelegramService $telegram, SupportChatService $supportChatService): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        abort_if(! $user || $user->is_admin, 403);

        $data = $request->validateWithBag('support', [
            'message' => ['required', 'string', 'min:2'],
        ]);

        $messageText = trim($data['message']);

        $message = SupportMessage::create([
            'user_id' => $user->id,
            'direction' => 'outbound',
            'message' => $messageText,
        ]);

        $telegram->sendMessage('[Client #' . $user->id . '] ' . $messageText);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $supportChatService->formatMessage($message),
                'messages' => $supportChatService->getMessagesForUser($user->id),
            ]);
        }

        return redirect()
            ->route('user.dashboard')
            ->with([
                'status' => __('Message sent to support.'),
                'support_open' => true,
            ]);
    }
}
