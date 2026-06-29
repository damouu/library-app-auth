<?php

namespace App\Contracts;

interface PasswordVerifier
{
    public function verify(string $plainPassword, string $hashedPassword): void;
}
