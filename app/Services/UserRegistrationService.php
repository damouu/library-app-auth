<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use OpenTelemetry\API\Trace\SpanInterface;
use Ramsey\Uuid\Uuid;

class UserRegistrationService
{
    public function __construct(
        protected TracingService $tracingService,
        protected User           $userModel
    )
    {
    }

    public function create($validator)
    {
        return $this->tracingService->trace(
            'mongodb-insert-user',
            function (SpanInterface $span) use ($validator) {
                $emailValid = $validator["email"];
                $emailCrop = strpos($emailValid, "@");
                $user = $this->userModel->create([
                    'user_name' => $validator["user_name"],
                    'email' => $validator["email"],
                    'avatar_img_url' => "https://avatar.iran.liara.run/username?username=" . $validator["user_name"] . "+" . (substr($emailValid, 0, $emailCrop)),
                    'card_uuid' => Uuid::uuid4()->toString(),
                    'password' => Hash::make($validator["password"]),
                ]);
                $span->setAttribute('user.email', $user->email);
                return $user;
            }
        );
    }
}
