# ActiveCampaign Data Cruncher

Small service that listens to webhooks from ActiveCampaign and syncs
contact and deal data to specific Google Sheets. It also updates a
custom field in AC with the average of 5 other custom fields.

## Env variable Configuration

`ACTIVECAMPAIGN_ACCOUNT`: Account number at ActiveCampaign.

`ACTIVECAMPAIGN_TOKEN`: API token for ActiveCampaign.

`GOOGLE_SERVICE_ACCOUNT`: JSON for connecting to Google sheets.

`DEAL_SHEETS`: YAML defining sheets to sync deals with. An array of
hashes, where each hash has `sheet` (sheet id), `tab` (name of tab to
import too) and optionally `localeTranslate`, a boolean indicating
whether to use danish formatting.

`CONTACT_SHEETS`: Same as `DEAL_SHEETS`, for contacts.

## Artisan commands

``` shell
./artisan  ac:get:contact       Get a contact from ActiveCampaign and dump to stdout
./artisan  ac:get:deal          Get a deal from ActiveCampaign and dump to stdout
./artisan  ac:getsheet          Get data from Sheets and dump to stdout
```

[![](https://img.shields.io/codecov/c/github/reload/acdc.svg?style=for-the-badge)](https://codecov.io/gh/reload/acdc)
