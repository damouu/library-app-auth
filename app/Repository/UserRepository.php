<?php

namespace App\Repository;

use App\Models\User;
use App\Services\TracingService;
use OpenTelemetry\API\Trace\SpanInterface;
use Throwable;


class UserRepository
{
    public function __construct(
        protected TracingService $tracingService,
    )
    {
    }

    public function exist(string $userUuid): bool
    {
        return User::where('member_card_uuid', $userUuid)->exists();
    }

    /**
     * @param string $email
     * @return User
     * @throws Throwable
     */
    public function findByEmail(string $email): User
    {
        return $this->tracingService->trace(
            'user-repository-find-by-email',
            function (SpanInterface $span) use ($email) {
                $span->setAttribute('user.email', $email);
                $user = User::where('email', $email)->firstOrFail();
                $span->setAttribute('user.id', (string)$user->id);
                return $user;
            }
        );
    }


    public function findProfileById(string|int $id): User
    {
        return $this->tracingService->trace(
            'user-repository-find-by-id',
            function (SpanInterface $span) use ($id) {
                $span->setAttribute('user.id', $id);
                return User::query()
                    ->select([
                        'id',
                        'user_name',
                        'avatar_img_url',
                        'email',
                        'card_uuid',
                        'last_logged_in_at'
                    ])
                    ->where('id', $id)
                    ->firstOrFail();
            });
    }


    public function save(User $user): User
    {
        return $this->tracingService->trace(
            'user-repository-save',
            function (SpanInterface $span) use ($user) {
                $span->setAttribute('user.id', (string)$user->getKey());
                $span->setAttribute('user.email', $user->email);
                $user->save();
                return $user;
            }
        );
    }

    /**
     * @throws Throwable
     */
    public function delete(User $user): bool
    {
        return $this->tracingService->trace(
            'user-repository-delete',
            function (SpanInterface $span) use ($user) {
                $span->setAttribute('user.id', (string)$user->getKey());
                $span->setAttribute('user.email', $user->email);
                return $user->delete();
            });
    }

}
