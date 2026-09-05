<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Stops the hub from locking its own users out of AMS.
 *
 * AMS locks an account for 15 minutes after 3 failed /Login attempts — and that
 * lock applies to the real AMS account, not just to the hub, so a few typos here
 * would shut the user out of every AMS application. The framework's
 * `login_limiter` does not help: it counts requests per IP, so a whole office
 * behind one address shares a budget while a single user roaming across
 * addresses gets a fresh one each time.
 *
 * So we count failures per username and refuse the attempt that would be the
 * third, keeping one attempt in reserve. The counter resets on success and
 * expires on its own, which is why a bare cache pool is enough — no schema, and
 * nothing to clean up.
 */
class AmsLoginThrottle
{
    /**
     * AMS locks on the 3rd failure. Refusing at 2 leaves the user one attempt
     * once our window expires, instead of handing them a locked account.
     */
    private const MAX_FAILURES = 2;

    /** Matches the AMS lockout window, so our counter never outlives theirs. */
    private const WINDOW_SECONDS = 900;

    public function __construct(
        #[Autowire(service: 'cache.rate_limiter')]
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Whether this username may be sent to AMS right now.
     */
    public function isBlocked(string $username): bool
    {
        return $this->failureCount($username) >= self::MAX_FAILURES;
    }

    public function registerFailure(string $username): void
    {
        $item = $this->cache->getItem($this->key($username));
        $count = ((int) $item->get()) + 1;

        $item->set($count);
        $item->expiresAfter(self::WINDOW_SECONDS);
        $this->cache->save($item);

        if ($count >= self::MAX_FAILURES) {
            $this->logger->warning('AMS login throttled to avoid an account lockout', [
                'username' => $username,
                'failures' => $count,
            ]);
        }
    }

    public function registerSuccess(string $username): void
    {
        $this->cache->deleteItem($this->key($username));
    }

    /**
     * Seconds a blocked user must wait. Approximate: the cache pool does not
     * expose the remaining TTL, so this reports the full window.
     */
    public function retryAfterSeconds(): int
    {
        return self::WINDOW_SECONDS;
    }

    private function failureCount(string $username): int
    {
        return (int) $this->cache->getItem($this->key($username))->get();
    }

    private function key(string $username): string
    {
        // Hashed because usernames may contain characters PSR-6 reserves, and
        // it keeps credentials out of cache filenames.
        return 'ams_login_failures_' . sha1(mb_strtolower($username));
    }
}
