<?php

namespace App\Service;

use App\Entity\Organization;
use App\Entity\User;
use App\Enum\UserRole;
use App\Exception\LoginThrottledException;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private AmsApiService $amsApiService,
        private AmsLoginThrottle $loginThrottle,
        private OrganizationRepository $organizationRepository,
        private LoggerInterface $logger,
        private string $defaultOrganizationName,
        private string $adminUsernames,
    ) {
    }

    public function register(
        string $email,
        string $password,
        string $firstName,
        string $lastName
    ): User {
        // Check if user already exists
        if ($this->userRepository->findOneBy(['email' => $email])) {
            throw new \InvalidArgumentException('User with this email already exists');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Save the user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateLastLogin(User $user): void
    {
        $user->setLastLoginAt(new \DateTime());
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Sign in against AMS.
     *
     * AMS is the only authority on credentials — the hub stores no password for
     * these accounts. A user who authenticates successfully but has no local row
     * gets one created here, so the hub keeps its organization link, its
     * EasyAdmin listing and its subscription rows without anyone having to
     * pre-provision anything.
     *
     * @throws LoginThrottledException when a further attempt would lock the
     *                                 account on the AMS side
     */
    public function verifyCredentials(
        string $identifier,
        string $password,
        ?string $serverDb = null,
        ?string $serverDbPass = null,
    ): ?User {
        if ($this->loginThrottle->isBlocked($identifier)) {
            throw new LoginThrottledException($this->loginThrottle->retryAfterSeconds());
        }

        if ($this->amsApiService->login($identifier, $password, $serverDb, $serverDbPass) === null) {
            $this->loginThrottle->registerFailure($identifier);

            return null;
        }

        $this->loginThrottle->registerSuccess($identifier);

        $user = $this->userRepository->findByIdentifier($identifier);

        if (!$user) {
            $user = $this->provisionAmsUser($identifier);
        }

        // Record which database this sign-in actually reached — the one asked
        // for, or the server's default when the form left the field blank.
        $user->setAmsServerDb($serverDb ?? $this->amsApiService->getDefaultServerDb());
        $this->entityManager->flush();

        if (!$user->isActive()) {
            $this->logger->warning('AMS credentials accepted for a user disabled in the hub', [
                'username' => $identifier,
            ]);

            return null;
        }

        return $user;
    }

    /**
     * Create the local row for an AMS account on its first sign-in.
     *
     * Only the identifier is known at this point: AMS returns a token, not a
     * profile. Names are filled in later by app:sync-ams, or by hand in
     * EasyAdmin.
     */
    private function provisionAmsUser(string $identifier): User
    {
        $user = new User();
        $user->setUsername($identifier);
        // The column is not nullable and AMS identifiers are usually not
        // addresses; a synthetic one keeps the row valid until a sync supplies
        // the real address.
        $user->setEmail(filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? $identifier
            : $identifier . '@ams.local');
        $user->setIsAmsUser(true);
        $user->setIsActive(true);
        $user->setOrganization($this->defaultOrganization());

        if ($this->isConfiguredAdmin($identifier)) {
            $user->addRole(UserRole::ADMIN);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->logger->info('Provisioned a hub account from AMS on first sign-in', [
            'username' => $identifier,
            'admin' => $this->isConfiguredAdmin($identifier),
        ]);

        return $user;
    }

    /**
     * AMS has no notion of who administers the hub, so the admins are named in
     * HUB_ADMIN_USERNAMES. Without it nobody could reach EasyAdmin, since no
     * local password remains to fall back on.
     */
    private function isConfiguredAdmin(string $identifier): bool
    {
        foreach (explode(',', $this->adminUsernames) as $candidate) {
            $candidate = trim($candidate);

            if ($candidate !== '' && mb_strtolower($candidate) === mb_strtolower($identifier)) {
                return true;
            }
        }

        return false;
    }

    private function defaultOrganization(): Organization
    {
        $organization = $this->organizationRepository->findOneBy(['name' => $this->defaultOrganizationName]);

        if (!$organization) {
            $organization = new Organization();
            $organization->setName($this->defaultOrganizationName);
            $organization->setIsActive(true);
            $this->entityManager->persist($organization);
        }

        return $organization;
    }
}
