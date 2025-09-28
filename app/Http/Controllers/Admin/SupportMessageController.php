<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Services\SupportChatService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportMessageController extends Controller
{
    public function __construct(private SupportChatService $supportChatService)
    {
    }

    public function threads(): JsonResponse
    {
        $threads = $this->supportChatService->getThreads();

        return response()->json([
            'threads' => $threads->values()->toArray(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->query('user_id');

        abort_if($userId <= 0, 422, 'Не указан клиент.');

        $user = $this->supportChatService->getUser($userId);
        abort_if(! $user, 404, 'Клиент не найден.');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'label' => $user->name ?: ('User #' . $user->id),
            ],
            'messages' => $this->supportChatService->getMessagesForUser($user->id),
        ]);
    }

    public function store(Request $request, TelegramService $telegram): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_admin', false))],
            'message' => ['required', 'string', 'min:2'],
        ]);

        $userId = (int) $data['user_id'];
        $user = $this->supportChatService->getUser($userId);
        abort_if(! $user, 404, 'Клиент не найден.');

        $message = SupportMessage::create([
            'user_id' => $user->id,
            'direction' => 'inbound',
            'message' => trim($data['message']),
        ]);

        $telegram->sendMessage('[Admin → Client #' . $user->id . '] ' . $data['message']);

        return response()->json([
            'message' => $this->supportChatService->formatMessage($message),
            'messages' => $this->supportChatService->getMessagesForUser($user->id),
            'threads' => $this->supportChatService->getThreads()->values()->toArray(),
        ]);
    }
}
