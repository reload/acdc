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

    protected function call(string $method, string $path = '', $options = [])
    {
        if (empty($this->account) || empty($this->token)) {
            throw new RuntimeException('Empty account/token');
        }
        $response = $this->client->request($method, $this->getUrl($path), $options + ['headers' => [
            'Api-Token' => $this->token,
        ]]);
        return json_decode($response->getBody(), true);
    }

    protected function getCustomFields($dealId) : array
    {
        $data = $this->call('GET', 'deals/' . $dealId . '/dealCustomFieldData');
        if (!isset($data['dealCustomFieldData'])) {
            throw new RuntimeException('Could not get deal custom fields on ' . $dealId);
        }

        return is_array($data['dealCustomFieldData']) ? $data['dealCustomFieldData'] : [];
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
        $data = $this->getCustomFields($dealId);
        foreach ($data as $customField) {
            if (!isset($customField['customFieldId']) ||
                !array_key_exists('fieldValue', $customField)) {
                throw new RuntimeException('Malformed custom field response on deal ' . $dealId);
            }
            $deal['custom_field_' . $customField['customFieldId']] = $customField['fieldValue'];
        }

        return $deal;
    }

    public function updateCustomField($dealId, $fieldName, $value)
    {
        if (!preg_match('/^custom_field_(\d+)$/', $fieldName, $matches)) {
            throw new RuntimeException('Bad custom field id: ' . $id);
        }
        $fieldId = $matches[1];

        // Find field id for the field instance on the deal.
        $fieldInstanceId = null;
        $data = $this->getCustomFields($dealId);
        foreach ($data as $customField) {
            if (!isset($customField['customFieldId'])|| !isset($customField['id'])) {
                throw new RuntimeException('Malformed custom field response on deal ' . $dealId);
            }
            if ($customField['customFieldId'] == $fieldId) {
                $fieldInstanceId = $customField['id'];
                break;
            }
        }

        if ($fieldInstanceId) {
            // If we have a field instance id, we're updating an existing
            // value.
            $data = [
                'dealCustomFieldDatum' => [
                    'fieldValue' => $value,
                ],
            ];
            $this->call(
                'PUT',
                'dealCustomFieldData/' . $fieldInstanceId,
                ['json' => $data]
            );
        } else {
            // Else POST the new value.
            $data = [
                'dealCustomFieldDatum' => [
                    'dealId' => $dealId,
                    'fieldValue' => $value,
                    'customFieldId' => $fieldId,
                ],
            ];
            $this->call(
                'POST',
                'dealCustomFieldData',
                ['json' => $data]
            );
        }
    }
}
