<?php

namespace App\Contracts\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * Find a user by primary key.
     */
    public function findById(int $id): ?User;

    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by their Google account id.
     */
    public function findByGoogleId(string $googleId): ?User;

    /**
     * Persist a new user.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User;

    /**
     * Update an existing user.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User;

    /**
     * Whether any users exist yet (used to bootstrap the first Owner).
     */
    public function isEmpty(): bool;
}
