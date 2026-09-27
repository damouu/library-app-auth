<?php

namespace App\DTO;

/**
 *
 */
readonly class JwtPayloadDTO
{
    /**
     * @param string $issuer
     * @param string $audience
     * @param string|int $subject
     * @param string $memberCardUuid
     */
    public function __construct(
        public string $issuer,
        public string $audience,
        public string $memberCardUuid
    )
    {
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'member_card_uuid' => $this->memberCardUuid
        ];
    }
}
