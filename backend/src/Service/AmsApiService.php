<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

class AmsApiService
{
    private ?string $token = null;
    private string $baseUrl;
    private string $apiDb;
    private string $apiDbPassword;
    private string $apiVersion;
    private string $apiUser;
    private string $apiPassword;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $amsApiUrl,
        string $amsApiDb,
        string $amsApiUser,
        string $amsApiPassword,
        string $amsApiDbPassword = '',
        string $amsApiVersion = 'v1',
    ) {
        $this->baseUrl = rtrim($amsApiUrl, '/');
        $this->apiDb = $amsApiDb;
        $this->apiDbPassword = $amsApiDbPassword;
        $this->apiVersion = $amsApiVersion;
        $this->apiUser = $amsApiUser;
        $this->apiPassword = $amsApiPassword;
    }

    /**
     * Call the AMS /Login endpoint and return the raw response.
     *
     * Follows the contract documented in CLAUDE/api/docs: POST, Basic auth, and
     * the `version` / `serverdb` / `serverdbpass` headers — all three are
     * mandatory, AMS answers 403 when any is missing. 401 means bad
     * credentials, 403 an unknown serverdb or insufficient rights.
     *
     * @return array{statusCode: int, body: string}
     */
    private function loginRequest(
        string $username,
        string $password,
        ?string $serverDb = null,
        ?string $serverDbPass = null,
    ): array {
        $response = $this->httpClient->request(
            'POST',
            $this->baseUrl . '/Login',
            [
                'auth_basic' => [$username, $password],
                'headers' => [
                    'version' => $this->apiVersion,
                    'serverdb' => $serverDb ?? $this->apiDb,
                    'serverdbpass' => $serverDbPass ?? $this->apiDbPassword,
                    'Accept' => 'application/json',
                ],
            ]
        );

        return [
            'statusCode' => $response->getStatusCode(),
            // Deliberately not toArray(): AMS returns the token either as a
            // bare string or wrapped in JSON, and toArray() throws on the
            // former. extractToken() handles both shapes.
            'body' => $response->getContent(false),
        ];
    }

    /**
     * Pull the token out of an AMS /Login response body.
     *
     * AMS is inconsistent here: the token comes back as raw text on some
     * builds and as JSON on others, under `token`, `Token` or `bearerToken`.
     */
    private function extractToken(string $body): ?string
    {
        $body = trim($body);

        if ($body === '') {
            return null;
        }

        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            foreach (['token', 'Token', 'bearerToken'] as $key) {
                if (!empty($decoded[$key]) && is_string($decoded[$key])) {
                    return $decoded[$key];
                }
            }

            return null;
        }

        // Raw token: a JWT, so three dot-separated segments and no whitespace.
        return substr_count($body, '.') === 2 && !preg_match('/\s/', $body)
            ? $body
            : null;
    }

    /**
     * Authenticate an end user against AMS and return their token.
     *
     * This is the hub's sign-in path: the credentials are the user's own, not
     * the service account, so no AMS_API_USER needs to be configured for it to
     * work. Returns null on any failure — invalid credentials, locked account,
     * AMS unreachable — deliberately without distinguishing them to the caller,
     * which must not leak which usernames exist.
     */
    public function login(
        string $username,
        string $password,
        ?string $serverDb = null,
        ?string $serverDbPass = null,
    ): ?string {
        try {
            $response = $this->loginRequest($username, $password, $serverDb, $serverDbPass);

            if ($response['statusCode'] !== 200) {
                $this->logger->warning('AMS login rejected', [
                    'username' => $username,
                    'serverdb' => $serverDb ?? $this->apiDb,
                    'status_code' => $response['statusCode'],
                ]);

                return null;
            }

            $token = $this->extractToken($response['body']);

            if ($token === null) {
                $this->logger->error('AMS login returned 200 without a usable token', [
                    'username' => $username,
                ]);
            }

            return $token;
        } catch (\Exception $e) {
            $this->logger->error('AMS login error', [
                'username' => $username,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Authenticate with AMS API and retrieve token
     *
     * @return bool True if authentication was successful
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function authenticate(): bool
    {
        if ($this->apiUser === '') {
            $this->logger->error('AMS service account is not configured (AMS_API_USER is empty)');

            return false;
        }

        $this->token = $this->login($this->apiUser, $this->apiPassword);

        if ($this->token === null) {
            return false;
        }

        $this->logger->info('Successfully authenticated with AMS API');

        return true;
    }

    /**
     * Headers every authenticated /v1/* call must carry.
     *
     * `version` and `serverdb` are mandatory on each request, not just on
     * /Login — AMS answers 403 when either is missing.
     *
     * @return array<string, string>
     */
    private function authenticatedHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'version' => $this->apiVersion,
            'serverdb' => $this->apiDb,
            'serverdbpass' => $this->apiDbPassword,
            'Accept' => 'application/json',
        ];
    }

    /**
     * Parse API response handling both array and paginated responses
     *
     * @param array $data Response data from API
     * @return array Parsed data
     */
    private function parseApiResponse(array $data): array
    {
        // Handle both array and paginated responses
        if (isset($data['data'])) {
            $companies = $data['data'];
            // Handle nested paginated response: {data: {data: [...], meta: {...}}}
            if (is_array($companies) && isset($companies['data'])) {
                return is_array($companies['data']) ? $companies['data'] : [];
            }
            return is_array($companies) ? $companies : [];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Get list of companies from AMS API
     *
     * @return array<int, array{compid: string, compfullname: string}> List of companies
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function getCompanies(): array
    {
        if (!$this->token) {
            $this->logger->warning('No token available for AMS API request');
            return [];
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                $this->baseUrl . '/v1/ttiercomp',
                [
                    'headers' => $this->authenticatedHeaders(),
                ]
            );

            $data = $response->toArray();
            return $this->parseApiResponse($data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch companies from AMS API', [
                'exception' => $e->getMessage(),
                'url' => $this->baseUrl . '/v1/ttiercomp',
            ]);
            return [];
        }
    }

    /**
     * Get list of users from AMS API
     *
     * @return array<int, array{uemail: string, name: string, namefull: string, compid: string}> List of users
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function getUsers(): array
    {
        if (!$this->token) {
            $this->logger->warning('No token available for AMS API request');
            return [];
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                $this->baseUrl . '/v1/user',
                [
                    'headers' => $this->authenticatedHeaders(),
                ]
            );

            $data = $response->toArray();
            return $this->parseApiResponse($data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch users from AMS API', [
                'exception' => $e->getMessage(),
                'url' => $this->baseUrl . '/v1/user',
            ]);
            return [];
        }
    }

    /**
     * The database used when a caller supplies none.
     */
    public function getDefaultServerDb(): string
    {
        return $this->apiDb;
    }

    /**
     * Check if token is available
     */
    public function hasToken(): bool
    {
        return $this->token !== null;
    }

    /**
     * Reset token
     */
    public function clearToken(): void
    {
        $this->token = null;
    }

    /**
     * Validate user credentials against AMS.
     *
     * Thin boolean wrapper over login() for callers that do not need the token.
     */
    public function validateUserCredentials(string $username, string $password): bool
    {
        return $this->login($username, $password) !== null;
    }
}