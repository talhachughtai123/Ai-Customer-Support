<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Any authenticated team member may view the inbox.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return true;
    }

    /**
     * Viewers are read-only; everyone else may reply.
     */
    public function reply(User $user, Conversation $conversation): bool
    {
        return ! $user->hasRole(Role::Viewer->value);
    }

    /**
     * Viewers cannot change status or assignment.
     */
    public function update(User $user, Conversation $conversation): bool
    {
        return ! $user->hasRole(Role::Viewer->value);
    }
}
