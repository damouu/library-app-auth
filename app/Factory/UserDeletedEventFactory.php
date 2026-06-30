<?php

namespace App\Factory;

use App\DTO\EventMetadataDTO;
use App\DTO\UserCreatedDataDTO;
use App\DTO\UserCreatedEventDTO;
use App\Models\User;
use Illuminate\Support\Str;

class UserDeletedEventFactory
{
    public function __construct()
    {
    }

    public function fromUser(User $user): UserCreatedEventDTO
    {
        return new UserCreatedEventDTO(
            metadata: new EventMetadataDTO(
                timestamp: now()->toIso8601String(),
                sourceService: config('app.service_name'),
                eventType: 'USER_DELETED',
                eventUuid: Str::uuid()->toString(),
            ),
            data: new UserCreatedDataDTO(
                userName: $user->user_name,
                email: $user->email,
                avatarImgUrl: $user->avatar_img_url,
                memberCardUuid: $user->card_uuid,
            ),
        );
    }
}
