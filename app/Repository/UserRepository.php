<?php

namespace App\Repository;

use App\Models\User;
use App\Services\TracingService;
use Throwable;


class UserRepository
{
    public function __construct(
        private readonly TracingService $tracingService,
    )
    {
    }

    /**
     * @param string $email
     * @return User
     * @throws Throwable
     */
    public function findByEmail(string $email): User
    {
        return $this->tracingService->trace(
            'repository.user.findByEmail',
            function () use ($email) {
                return User::where('email', $email)->firstOrFail();
            }, [
                'db.collection' => 'users',
            ]
        );
    }


    /**
     * @throws Throwable
     */
    public function save(User $user): User
    {
        return $this->tracingService->trace(
            'repository.user.create',
            function () use ($user) {
                $user->save();
                return $user;
            }, [
                'db.collection' => 'users',
            ]
        );
    }

    public function create(array $attributes): User
    {
        return $this->tracingService->trace(
            'repository.user.create',
            function () use ($attributes) {
                return User::create($attributes);
            }, [
                'db.collection' => 'users',
            ]
        );
    }

    /**
     * @throws Throwable
     */
    public function delete(User $user): bool
    {
        return $this->tracingService->trace(
            'repository.user.delete',
            function () use ($user) {
                return $user->delete();
            }, [
                'db.collection' => 'users',
                'user.id' => $user->card_uuid,
            ]
        );
    }

}
