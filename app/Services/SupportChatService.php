<?php

namespace App\Services;

use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupportChatService
{
    /**
     * Retrieve chat threads ordered by the latest message.
     */
    public function getThreads(?callable $nameResolver = null): Collection
    {
        $rows = SupportMessage::query()
            ->select('user_id', DB::raw('MAX(created_at) as last_at'))
            ->groupBy('user_id')
            ->orderByDesc('last_at')
            ->get();

        $userIds = $rows->pluck('user_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $users = $userIds->isEmpty()
            ? collect()
            : User::query()
                ->whereIn('id', $userIds)
                ->get()
                ->keyBy('id');

        $resolver = $nameResolver ?? function (?User $user, int $userId) {
            return $user?->name ?: ('User #' . $userId);
        };

        return $rows->map(function ($row) use ($users, $resolver) {
            $userId = (int) $row->user_id;
            $user = $users->get($userId);
            $formatted = null;

            if (! empty($row->last_at)) {
                try {
                    $formatted = Carbon::parse($row->last_at)->format('d.m.Y H:i');
                } catch (\Throwable $e) {
                    $formatted = (string) $row->last_at;
                }
            }

            return [
                'user_id' => $userId,
                'name' => $resolver($user, $userId),
                'last_at' => $formatted,
            ];
        });
    }

    /**
     * Retrieve formatted messages for the specified user.
     */
    public function getMessagesForUser(int $userId): array
    {
        return SupportMessage::query()
            ->with('user')
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (SupportMessage $message) => $this->formatMessage($message))
            ->toArray();
    }

    public function formatMessage(SupportMessage $message): array
    {
        return [
            'id' => $message->id,
            'direction' => $message->direction,
            'message' => $message->message,
            'created_at' => optional($message->created_at)->format('H:i d.m.Y'),
            'user_name' => optional($message->user)->name,
            'user_id' => $message->user_id,
        ];
    }

    public function getUser(int $userId): ?User
    {
        return User::query()
            ->where('is_admin', false)
            ->find($userId);
    }
}
