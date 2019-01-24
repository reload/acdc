<?php

namespace App;

use GuzzleHttp\Client;
use Throwable;

class ActiveCampaign
{
    protected $client;
    protected $account;
    protected $token;

    public function __construct(Client $client, string $account, string $token)
    {
        $this->client = $client;
        $this->account = $account;
        $this->token = $token;
    }

    protected function getUrl($method = '') {
        return 'https://' . $this->account . '.api-us1.com/api/3/' . $method;
    }

    public function ping()
    {
        try {
            $this->client->get($this->getUrl(), ['headers' => [
                'Api-Token' => $this->token,
            ]]);
        } catch (Throwable $e) {
            print($e->getMessage());
            return false;
        }
        return true;
    }
}
