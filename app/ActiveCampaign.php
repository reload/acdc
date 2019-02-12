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
        $response = $this->client->request($method, $this->getUrl($path), ['headers' => [
            'Api-Token' => $this->token,
        ]]);
        return json_decode($response->getBody(), true);
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
        $data = $this->call('GET', 'deals/' . $dealId);
        if (!isset($data['deal'])) {
            throw new RuntimeException('Could not get deal data for ' . $dealId);
        }
        $deal = $data['deal'];

        // Fetch custom fields.
        $data = $this->call('GET', 'deals/' . $dealId . '/dealCustomFieldData');
        if (!isset($data['dealCustomFieldData'])) {
            throw new RuntimeException('Could not get deal custom fields on ' . $dealId);
        }
        foreach ($data['dealCustomFieldData'] as $customField) {
            if (!isset($customField['customFieldId']) || !isset($customField['fieldValue'])) {
                throw new RuntimeException('Malformed custom field response on deal ' . $dealId);
            }
            $deal['custom_field_' . $customField['customFieldId']] = $customField['fieldValue'];
        }

        return $deal;
    }
}
