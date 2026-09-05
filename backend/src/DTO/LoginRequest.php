<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest
{
    #[Assert\NotBlank(message: 'Username is required')]
    public string $username;

    #[Assert\NotBlank(message: 'Password is required')]
    public string $password;

    public bool $rememberMe = false;

    /**
     * AMS database to authenticate against — the `serverdb` header.
     *
     * Null falls back to AMS_API_DB. The sign-in form sends it so a user can
     * reach a database the server was not configured for, exactly as iDeck does.
     */
    public ?string $serverDb = null;

    /** Optional password for that database — the `serverdbpass` header. */
    public ?string $serverDbPass = null;
}
