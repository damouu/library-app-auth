<?php

namespace App\Services;

use App\Contracts\Clock;
use App\Models\User;
use App\Repository\UserRepository;

class UserActivityService
{
    public function __construct(
        protected TracingService $tracingService,
        protected UserRepository $userRepository,
        protected Clock          $clock
    )
    {
    }

    public function markLogin(User $user): void
    {
        $this->tracingService->trace(
            'update-last-login',
            function () use ($user) {
                $user->last_logged_in_at = $this->clock->now();
                $this->userRepository->save($user);
            }
        );
    }
}
