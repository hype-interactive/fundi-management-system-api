<?php

namespace App\Services\Payment\Selcom;

use Exception;

/**
 * HTTP client wrapper for making API requests
 */
class HttpClient
{
    private bool $debug;
    private bool $showRequestJson;

    public function __construct(bool $debug = false, bool $showRequestJson = false)
    {
        $this->debug = $debug;
        $this->showRequestJson = $showRequestJson;
    }

    /**
     * Make HTTP request
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return array
     * @throws Exception
     */
    public function request(string $method, string $url, array $data = [], array $headers = []): array
    {
        $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES);

        if ($this->showRequestJson) {
            throw new Exception("Request JSON: {$jsonData}");
        }

        if ($this->debug) {
            error_log("URL: {$url}\nRequest: {$jsonData}");
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($this->debug) {
            error_log($httpStatus === 200 ? "Response: {$response}" : "Error: {$error}");
        }

        return [
            'status' => $httpStatus,
            'data' => $httpStatus === 200 ? json_decode($response, true) : null,
            'error' => $httpStatus !== 200 ? $this->formatError($httpStatus, $error, $response) : null,
        ];
    }

    private function formatError(int $httpStatus, string $error, string $response): string
    {
        return sprintf('HTTP Status: %s, Error: %s, Response: %s', $httpStatus, $error, $response);
    }
}
