<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\MessageRepositoryInterface;
use App\Enums\MessageSenderType;
use App\Enums\MessageType;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private readonly MessageRepositoryInterface $messages,
    ) {}

    /**
     * Agent posts a reply into a conversation.
     */
    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('reply', $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $this->messages->create($conversation, [
            'sender_type' => MessageSenderType::Agent,
            'sender_id' => $request->user()->id,
            'type' => MessageType::Text,
            'body' => $validated['body'],
            'read_at' => now(),
        ]);

        return back();
    }
}
