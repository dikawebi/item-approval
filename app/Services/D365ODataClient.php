<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;

class D365ODataClient
{
    protected Client $http;
    protected string $resourceUrl;

    public function __construct()
    {
        $this->resourceUrl = rtrim(config('services.d365.resource_url'), '/');

        $this->http = new Client([
            'base_uri' => "{$this->resourceUrl}/data/",
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function getEntitySet(string $entitySet, array $params = []): array
    {
        try {
            $response = $this->http->get($entitySet, [
                'headers' => ['Authorization' => "Bearer {$this->getAccessToken()}"],
                'query' => $params,
            ]);
        } catch (RequestException $e) {
            throw new \RuntimeException($this->fullErrorMessage($entitySet, $e), previous: $e);
        }

        $body = json_decode($response->getBody()->getContents(), true);

        return $body['value'] ?? [];
    }

    public function createEntity(string $entitySet, array $payload): array
    {
        try {
            $response = $this->http->post($entitySet, [
                'headers' => [
                    'Authorization' => "Bearer {$this->getAccessToken()}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (RequestException $e) {
            throw new \RuntimeException($this->fullErrorMessage($entitySet, $e), previous: $e);
        }

        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    /**
     * Build a full, untruncated error message from a failed request —
     * Guzzle's own exception message truncates the response body to ~120
     * characters, which usually cuts off exactly the useful part of a D365
     * OData error (the innererror.message with the real reason).
     */
    protected function fullErrorMessage(string $entitySet, RequestException $e): string
    {
        if (! $e->hasResponse()) {
            return "D365 request to '{$entitySet}' failed: {$e->getMessage()}";
        }

        $status = $e->getResponse()->getStatusCode();
        $body = $e->getResponse()->getBody()->getContents();

        $decoded = json_decode($body, true);
        $innerMessage = $decoded['error']['innererror']['message'] ?? null;

        $message = "D365 request to '{$entitySet}' failed with HTTP {$status}.";

        if ($innerMessage) {
            $message .= " Inner error: {$innerMessage}";
        }

        $message .= " Full response: {$body}";

        return $message;
    }

    protected function getAccessToken(): string
    {
        return Cache::remember('d365_odata_access_token', now()->addMinutes(50), function () {
            $tenantId = config('services.d365.tenant_id');

            $authClient = new Client();
            $response = $authClient->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => config('services.d365.client_id'),
                    'client_secret' => config('services.d365.client_secret'),
                    'scope' => "{$this->resourceUrl}/.default",
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return $body['access_token'];
        });
    }
}
