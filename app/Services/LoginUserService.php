<?php

namespace App\Services;

use App\Contracts\PasswordVerifier;
use App\DTO\LoginRequestDTO;
use App\DTO\RegisterResponseDTO;
use App\Factory\JwtPayloadFactory;
use App\Repository\UserRepository;
use Exception;
use Illuminate\Validation\ValidationException;
use Throwable;

class LoginUserService
{

    public function __construct(
        private JWTService          $jwtService,
        private JwtPayloadFactory   $jwtPayloadFactory,
        private PasswordVerifier    $passwordVerifier,
        private TracingService      $tracingService,
        private UserRepository      $userRepository,
        private UserActivityService $userActivityService,
    )
    {
    }

    /**
     * @throws ValidationException
     * @throws Exception|Throwable
     */
    public function login(LoginRequestDTO $loginRequestDTO): RegisterResponseDTO
    {
        return $this->tracingService->trace(
            'user.login',
            function ($span) use ($loginRequestDTO) {

                $user = $this->userRepository->findByEmail(
                    $loginRequestDTO->email
                );

                $span->setAttribute(
                    'user.member_card.uuid',
                    $user->card_uuid
                );

                $this->passwordVerifier->verify(
                    $loginRequestDTO->password,
                    $user->password
                );

                $this->userActivityService->markLogin($user);

                $payload = $this->jwtPayloadFactory->fromUser($user);

                $token = $this->jwtService->createToken($payload);

                return RegisterResponseDTO::fromToken(
                    token: $token,
                    expiresIn: 3600,
                );
            }
        );
    }

}
