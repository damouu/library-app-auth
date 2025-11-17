<?php

namespace App\Services\Traits;

use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait HttpClientTrait
{
    /**
     * Executes a robust, token-authenticated API call with retries and exception handling.
     *
     * @param string $method The HTTP method ('get', 'post', 'put', etc.).
     * @param string $fullUrl The full URL to call.
     * @param string $token The access token for the external API.
     * @param string $userId The internal user ID (for logging context).
     * @return array|int|null The JSON decoded response data, or null on critical failure.
     */
    protected function makeSafeApiCall(
        string $method,
        string $fullUrl,
        string $token,
        string $userId,
    ): array|int|null
    {
        try {
            $client = Http::withToken($token)
                ->acceptJson()
                ->retry(3, 100)
                ->throw();

            $response = $client->{$method}($fullUrl);

            return $response->json();

        } catch (RequestException $e) {
            Log::error("External API call failed with status: " . $e->response->status(), [
                'user_id' => $userId,
                'endpoint' => $fullUrl,
                'error' => $e->getMessage(),
            ]);

            return $e->response->status();

        } catch (Exception $e) {
            Log::critical("External API connection failed: " . $e->getMessage(), [
                'user_id' => $userId,
                'endpoint' => $fullUrl,
            ]);

            return 503;
        }
    }
}
