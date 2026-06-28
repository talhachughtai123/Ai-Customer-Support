<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\ConversationRepositoryInterface;
use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConversationRepository implements ConversationRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Conversation::query()
            ->with('customer')
            ->withCount(['messages as unread_count' => fn ($query) => $query
                ->where('sender_type', 'customer')
                ->whereNull('read_at')])
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['assigned_to']), fn ($query) => $query->where('assigned_to', $filters['assigned_to']))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(fn ($q) => $q
                    ->where('subject', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")));
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Conversation
    {
        return Conversation::find($id);
    }

    public function findWithMessages(int $id): ?Conversation
    {
        return Conversation::query()
            ->with([
                'customer',
                'assignedAgent',
                'messages' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
                'messages.sender',
            ])
            ->find($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Conversation
    {
        return Conversation::create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Conversation $conversation, array $attributes): Conversation
    {
        $conversation->update($attributes);

        return $conversation->refresh();
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        return Conversation::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
