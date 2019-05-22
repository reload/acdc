# ActiveCampaign Data Cruncher

## Env variable Configuration

`ACTIVECAMPAIGN_ACCOUNT`: Account number at ActiveCampaign.

`ACTIVECAMPAIGN_TOKEN`: API token for ActiveCampaign.

`GOOGLE_SERVICE_ACCOUNT`: JSON for connecting to Google sheets.

`DEAL_SHEETS`: YAML defining sheets to sync deals with. An array of
hashes, where each hash has `sheet` (sheet id), `tab` (name of tab to
import too) and optionally `localeTranslate`, a boolean indicating
whether to use danish formatting.

## Artisan commands

``` shell
./artisan  ac:get:contact       Get a contact from ActiveCampaign and dump to stdout
./artisan  ac:get:deal          Get a deal from ActiveCampaign and dump to stdout
./artisan  ac:getsheet          Get data from Sheets and dump to stdout
```

[![](https://img.shields.io/codecov/c/github/reload/acdc.svg?style=for-the-badge)](https://codecov.io/gh/reload/acdc)
