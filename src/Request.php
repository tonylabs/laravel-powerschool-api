<?php

namespace TONYLABS\PowerSchool\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use TONYLABS\PowerSchool\Api\Exception\MissingClientCredentialsException;
use Illuminate\Support\Facades\Response as LaravelResponse;

class Request
{
    protected Client $client;
    protected string $client_id;
    protected string $client_secret;
    protected string $token;
    protected string $token_type;
    protected string $expires;
    protected int $attempts = 0;

    /**
     * Creates a new Request object to interact with PS's api
     *
     * @param string $serverAddress The url of the server
     * @param string $client_id The client id obtained from installing a plugin with oauth enabled
     * @param string $client_secret The client secret obtained from installing a plugin with oauth enabled
     */
    public function __construct(string $serverAddress, string $client_id, string $client_secret, ?string $token)
    {
        $this->client = new Client(['base_uri' => $serverAddress]);
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        if (!empty($token)) $this->token = $token;
    }

    /**
     * Makes an api call to PowerSchool
     */
    public function makeRequest(string $method, string $endpoint, array $options, bool $returnResponse = false): JsonResponse|array
    {
        $this->authenticate();
        $this->attempts++;

        if (!isset($options['headers'])) $options['headers'] = [];
        $options['headers']['Accept'] = 'application/json';
        $options['headers']['Content-Type'] = 'application/json';
        $options['headers']['Authorization'] = 'Bearer ' . $this->token;
        $options['http_errors'] = true;

        try {
            $response = $this->getClient()->request($method, $endpoint, $options);
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            if ($response->getStatusCode() === 401 && $this->attempts < 3) {
                return $this->authenticate(true)->makeRequest($method, $endpoint, $options);
            }
            Debug::log(fn () => ray()->json($response->getBody()->getContents())->red()->label($response->getStatusCode()));
            throw $exception;
        }
        $this->attempts = 0;
        $objBody = json_decode($response->getBody()->getContents(), true);
        Debug::log($objBody)->color('green');
        if ($returnResponse) {
            return LaravelResponse::json($objBody, $response->getStatusCode());
        }
        return $objBody ?? [];
    }

    /**
     * Authenticates against the api and retrieves an auth token
     *
     * @param boolean $force Force authentication even if there is an existing token
     * @return $this
     * @throws MissingClientCredentialsException|\GuzzleHttp\Exception\GuzzleException
     */
    public function authenticate(bool $force = false): static
    {
        // Check if there is already a token and we're not doing a force-retrieval
        if ($force == false && $this->token) {
            return $this;
        }

        // Double check that there are client credentials
        if (!$this->client_id || !$this->client_secret) {
            throw new MissingClientCredentialsException('Client id or secret is missing. Please retrieve from PowerSchool.');
        }

        $token = base64_encode($this->client_id . ':' . $this->client_secret);

        $arrayHeaders = [
            'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . $token,
        ];
        $arrayParameters = ['headers' => $arrayHeaders, 'body' => 'grant_type=client_credentials'];
        $objRequest = $this->getClient()->post('/oauth/access_token', $arrayParameters);
        $objResponse = json_decode($objRequest->getBody()->getContents());
        $this->token = $objResponse->access_token;
        $this->token_type = $objResponse->token_type;
        $this->expires = $objResponse->expires_in;
        return $this;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getTokenType()
    {
        return $this->token_type;
    }

    public function getExpires()
    {
        return $this->expires;
    }
}
