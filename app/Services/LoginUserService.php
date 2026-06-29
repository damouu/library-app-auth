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
        protected readonly JWTService          $jwtService,
        protected readonly JwtPayloadFactory   $jwtPayloadFactory,
        protected readonly PasswordVerifier    $passwordVerifier,
        protected readonly TracingService      $tracingService,
        protected readonly UserRepository      $userRepository,
        protected readonly UserActivityService $userActivityService,
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
            'user-login',
            function () use ($loginRequestDTO) {
                $user = $this->userRepository->findByEmail($loginRequestDTO->email);
                $this->passwordVerifier->verify($loginRequestDTO->password, $user->password);
                $this->userActivityService->markLogin($user);
                $payload = $this->jwtPayloadFactory->fromUser($user);
                $token = $this->jwtService->createToken($payload);
                return RegisterResponseDTO::fromToken(
                    token: $token,
                    expiresIn: 3600,
                );
            });
    }

}
