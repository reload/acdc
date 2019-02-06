<?php

namespace App;

use GuzzleHttp\Client;
use RuntimeException;

class ActiveCampaign
{
    protected $client;
    protected $account;
    protected $token;

    public function __construct(Client $client, string $account = null, string $token = null)
    {
        $this->client = $client;
        $this->account = (string) $account;
        $this->token = (string) $token;
    }

    protected function getUrl(string $path = '')
    {
        return 'https://' . $this->account . '.api-us1.com/api/3/' . $path;
    }

    protected function call(string $method, string $path = '')
    {
        if (empty($this->account) || empty($this->token)) {
            throw new RuntimeException('Empty account/token');
        }
        return $this->client->request($method, $this->getUrl($path), ['headers' => [
            'Api-Token' => $this->token,
        ]]);
    }

    public function ping()
    {
        // Try to get information about the current user to check if the
        // credentials are valid.
        $this->call('GET', 'users/me');
        return true;
    }

    public function withCreds($account, $token)
    {
        return new self($this->client, $account, $token);
    }

    public function get($dealId)
    {
        $response = $this->call('GET', 'deals/' . $dealId);
        $data =json_decode($response->getBody(), true);
        if (!isset($data['deal'])) {
            throw new RuntimeException('Could not get deal data');
        }
        return $data['deal'];
    }
}
