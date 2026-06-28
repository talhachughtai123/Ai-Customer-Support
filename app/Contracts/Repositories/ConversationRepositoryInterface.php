<?php

namespace App\Contracts\Repositories;

use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ConversationRepositoryInterface
{
    /**
     * Paginated conversation list for the agent inbox.
     *
     * Supported filters: status (string), assigned_to (int), search (string).
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Find a conversation by primary key.
     */
    public function find(int $id): ?Conversation;

    /**
     * Find a conversation with its messages (and each message sender) eager loaded.
     */
    public function findWithMessages(int $id): ?Conversation;

    /**
     * Persist a new conversation.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Conversation;

    /**
     * Update an existing conversation.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Conversation $conversation, array $attributes): Conversation;

    /**
     * Count of conversations grouped by status, e.g. ['open' => 4, 'closed' => 2].
     *
     * @return array<string, int>
     */
    public function statusCounts(): array;
}
