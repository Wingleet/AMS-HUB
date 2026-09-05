<?php

namespace App\Exception;

/**
 * Raised when another AMS sign-in attempt would lock the account upstream.
 *
 * Distinct from "invalid credentials" on purpose: the user needs to be told to
 * wait rather than to retype their password, which is what would actually get
 * them locked out of every AMS application.
 */
class LoginThrottledException extends \RuntimeException
{
    public function __construct(private int $retryAfterSeconds)
    {
        parent::__construct('Too many failed sign-in attempts.');
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
