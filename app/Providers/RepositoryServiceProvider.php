<?php

namespace App\Providers;

use App\Contracts\Repositories\ConversationRepositoryInterface;
use App\Contracts\Repositories\MessageRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Repositories\Eloquent\ConversationRepository;
use App\Repositories\Eloquent\MessageRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Map repository interfaces to their Eloquent implementations.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        ConversationRepositoryInterface::class => ConversationRepository::class,
        MessageRepositoryInterface::class => MessageRepository::class,
    ];
}
