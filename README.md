# zinapse/laralocate
Get relationships between countries, states, and cities.

## Overview
This package grabs a JSON file containing data for locations around the world, then parses that data into a database. [That file is here.](https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/54939ff65f80369ba8b78e5277b5ed2ed503ef50/countries%2Bstates%2Bcities.json)

## Installation
1. Include it with composer:
`composer require zinapse/laralocate`

2. Make sure you run the migrations: `php artisan migrate`

    - You can see what changes will be made first using **--pretend**: `php artisan migrate --pretend`.

3. After that all you need to do is populate the database: `php artisan laralocate:populate`

    - You can use the `--cities` option to see verbose city output.
    - If you don't want the command to download the JSON file automatically, you can specify your own JSON file's path: `php artisan laralocate:populate --file=/my/path/to/file.json` (just make sure it follows the same structure)

# Models

## City
A City object has a name, and a state ID as a parent.

| Column Name  | Data |
| ------------ | ------------- |
| name         | string        |
| state_id     | foreign key   |

```php
<?php

use Zinapse\LaraLocate\Models\City;

$city = City::first();            // Get any City object
$state = $city->state;            // Get this city's State object
$country = $city->country;        // Get this city's Country object

$zipcodes = $city->getPostalCodes(6); // ['type' => 'findNearbyPostalCodes', 'lat' => $city->lat, 'lng' => $city->long, 'radius' => 6, 'maxRows' => 5]
```

## State
A State object has a name, a state code, and a country ID as a parent.

| Column Name  | Data |
| ------------ | ------------- |
| name         | string        |
| code         | string        |
| country_id   | foreign key   |

```php
<?php

use Zinapse\LaraLocate\Models\State;

// Get all states from the country passed
$state = State::fromCountry('United States');
$code = $state->code;             // The state code
$cities = $state->cities;         // A collection of City objects from this state
$country = $state->country;       // This state's Country object
```

## Country
A Country object has a name and a country code.

| Column Name  | Data |
| ------------ | ------------- |
| name         | string        |
| code         | string        |

```php
<?php

use Zinapse\LaraLocate\Models\Country;

$country = Country::where('name', 'United States')->first();
$states = $country->states;       // Get a collection of this country's State objects

```

## FeatureCode
A FeatureCode object has a code, descriptions, and a parent ID. Rows with a null `long_desc` and a null `parent_id` are toplevel codes. Those rows will have
a `short_desc` that could contain text separated by a `|` character, which are example areas that could be under that code.

| Column Name | Data     |
| ----------- | -------- |
| code        | string   |
| short_desc  | string   |
| long_desc   | string   |
| parent_id   | uint     |

```php
<?php

use Zinapse\LaraLocate\Models\FeatureCode;

$code = FeatureCode::where('code', 'A')->first();
$examples = explode('|', $code->short_desc); // ['country', 'state', 'region]

```

## GeoNames
The GeoNames model isn't associated with any tables, but it contains static functions for calling GeoName webhooks.

```php
<?php

use Zinapse\LaraLocate\Models\GeoNames;

$all_webhooks = GeoNames::GetWebhooks(); // returns an array: ['webhook name' => [required variables to pass], ...]

$named_webhook = GeoNames::Webhook(['type' => 'postalCodeSearch', 'postalcode' => 12345]); // runs the postalCodeSearch webhook

// Added helper functions so you don't have to remember all this, or pass arrays, etc
$search = GeoNames::GeoSearch('Alaska', 2); // ['type' => 'search', 'q' => 'Alaska', 'maxRows' => 2]
```
