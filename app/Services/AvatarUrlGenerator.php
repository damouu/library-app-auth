<?php

namespace App\Services;
class AvatarUrlGenerator
{
    public function generate(string $username): string
    {
        return sprintf(
            'https://api.dicebear.com/9.x/adventurer/svg?seed=%s',
            urlencode($username)
        );
    }
}
