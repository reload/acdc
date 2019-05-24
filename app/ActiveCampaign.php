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

    protected function getDealCustomFields($dealId) : array
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

    public function getDeal($dealId)
    {
        $data = $this->call('GET', 'deals/' . $dealId);
        if (!isset($data['deal'])) {
            throw new RuntimeException('Could not get deal data for ' . $dealId);
        }
        $deal = $data['deal'];

        // Fetch custom fields.
        $data = $this->getDealCustomFields($dealId);
        foreach ($data as $customField) {
            if (!isset($customField['customFieldId']) ||
                !array_key_exists('fieldValue', $customField)) {
                throw new RuntimeException('Malformed custom field response on deal ' . $dealId);
            }
            $deal['custom_field_' . $customField['customFieldId']] = $customField['fieldValue'];
        }

        return $deal;
    }

    public function updateDealCustomField($dealId, $fieldName, $value)
    {
        if (!preg_match('/^custom_field_(\d+)$/', $fieldName, $matches)) {
            throw new RuntimeException('Bad custom field id: ' . $id);
        }
        $fieldId = $matches[1];

        // Find field id for the field instance on the deal.
        $fieldInstanceId = null;
        $currentValue = null;
        $data = $this->getDealCustomFields($dealId);
        foreach ($data as $customField) {
            if (!isset($customField['customFieldId'])|| !isset($customField['id'])) {
                throw new RuntimeException('Malformed custom field response on deal ' . $dealId);
            }
            if ($customField['customFieldId'] == $fieldId) {
                $fieldInstanceId = $customField['id'];
                if (isset($customField['fieldValue'])) {
                    $currentValue = $customField['fieldValue'];
                }
                break;
            }
        }

        if ($fieldInstanceId) {
            if ($currentValue != $value) {
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
            }
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

    public function getContact($contactId)
    {
        $data = $this->call('GET', 'contacts/' . $contactId);
        if (!isset($data['contact'])) {
            throw new RuntimeException('Could not get contact data for ' . $contactId);
        }

        $contact = $data['contact'];

        // Add in field data.
        if (isset($data['fieldValues'])) {
            $fields = $this->getContactFields();

            foreach ($data['fieldValues'] as $fieldValue) {
                if (!isset($fieldValue['field']) || !isset($fieldValue['value'])) {
                    throw new RuntimeException('Malformed contact field data on ' . $contactId);
                }

                if (isset($fields[$fieldValue['field']])) {
                    $fieldName = $fields[$fieldValue['field']];
                    $contact[$fieldName] = $fieldValue['value'];
                }
            }

            // Add in any undefined fields.
            foreach ($fields as $fieldName) {
                if (!isset($contact[$fieldName])) {
                    $contact[$fieldName] = '';
                }
            }
        }

        // Add contact tags.
        $contact['tags'] = implode(', ', $this->getContactTagNames($this->getContactTags($contactId)));

        // Add lead score.
        $contact['lead_score'] = $this->getContactLeadScore($contactId);

        return $contact;
    }

    protected function getContactFields()
    {
        $fieldData = $this->call('GET', 'fields');

        if (!isset($fieldData['fields'])) {
            throw new RuntimeException('Could not get contact field definitions on ' . $contactId);
        }

        $fields = [];
        foreach ($fieldData['fields'] as $field) {
            if (!isset($field['id']) || !isset($field['perstag'])) {
                throw new RuntimeException('Malformed contact field definition reply on ' . $contactId);
            }
            $fields[$field['id']] = 'field.' . strtolower($field['perstag']);
        }

        return $fields;
    }

    protected function getContactTags(string $contactId)
    {
        $tagData = $this->call('GET', 'contacts/' . $contactId . '/contactTags');

        if (!isset($tagData['contactTags'])) {
            throw new RuntimeException('Could not get contact tags on ' . $contactId);
        }

        $tags = [];
        foreach ($tagData['contactTags'] as $tag) {
            if (!isset($tag['id'])) {
                throw new RuntimeException('Malformed contact tag reply on ' . $contactId);
            }
            $tags[] = $tag['id'];
        }

        return $tags;
    }

    protected function getContactTagNames(array $tagIds)
    {
        $names = [];
        foreach ($tagIds as $tagId) {
            $tagData = $this->call('GET', 'contactTags/' . $tagId . '/tag');

            if (!isset($tagData['tag']['tag'])) {
                throw new RuntimeException('Could not get name for contact tag id ' . $tagId);
            }

            $names[] = $tagData['tag']['tag'];
        }

        return $names;
    }

    protected function getContactLeadScore(string $contactId)
    {
        $scoreValues = $this->call('GET', 'contacts/' . $contactId . '/scoreValues');

        if (!isset($scoreValues['scoreValues']['scoreValue'])) {
            throw new RuntimeException('Could not get contact lead_score on ' . $contactId);
        }

        return (int) $scoreValues['scoreValues']['scoreValue'];
    }
}
