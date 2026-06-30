<?php

namespace App\Services;

use App\Contracts\Clock;
use App\Models\User;
use App\Repository\UserRepository;
use Throwable;

class UserActivityService
{
    public function __construct(
        private TracingService $tracingService,
        private UserRepository $userRepository,
        private Clock          $clock
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function markLogin(User $user): void
    {
        $this->tracingService->trace(
            'update-last-login',
            function () use ($user) {
                $user->last_logged_in_at = $this->clock->now();
                $this->userRepository->save($user);
            }, [
                'user.card_uuid' => $user->card_uuid
            ]
        );
    }
}
