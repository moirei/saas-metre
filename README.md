# SaaS Metre

This package is a minimal and straight forward solution for tracking services usage on SaaS applications.

```php
$metre = new Metre();

$metre->addMeasure('user_accounts');

$metre->increment('user_accounts');
$metre->increment('user_accounts', count: 2);
$usage = $metre->usage('user_accounts');
expect($usage->count)->toEqual(3);
```

## 💚 Features
* Track `METERED` and `VOLUME` measures (service offerings). E.g. tracking user monthly downloads and user allowed storage (for all time).
* Simple period usage accumulator
* Tags to further description usage entries. Can also measure usage per tags.
* Eloquent compatible.

## Installation

```bash
composer require moirei/saas-metre
```

## Concepts

**Metre**: A metre is container of measurements for your SaaS offering usage.

**Period**: Usage periods indicating your weekly, monthly, etc., SaaS subscription or billing cycle.

**Measure**: A measure fauture on the metre for your SaaS offering. E.g. `"user_accounts"`, `"auio_downloads"`.

**Measure type**: The types of measuremeants. Currently `METERED` and `VOLUME`. Usage of metreed measurements are calculated per period. E.g. if you provide your users 10 MP3 downloads per week. Volume measurements on the other hand are calculated for all time. E.g. you user may be allowed only a maximum of 2 collaborators at all times.

**Measure limits [`int`]**: Used to limit usage of measures.

**Measure tags**: Tags make it possible to categorise and group usage on a single measure.

## Usage

### Create a metre

#### With array
```php
use MOIREI\Metre\Objects\MeasureType;
...

$metre = new Metre([
    'startOfPeriods' => now()->unix(),
    'measures' => [
      'user_accounts' => [
        'type' => MeasureType::METERED,
        'limit' => 100,
        'defaultTags' => ['publisher'],
      ]
    ]
]);
```

#### Make from object value
```php
use MOIREI\Metre\Objects\MetreInput;
use MOIREI\Metre\Objects\Measure;
...

$metre = Metre::make(
    new MetreInput(
        startOfPeriods: now()->unix(),
        measures: [
            new Measure(
                name: 'user_accounts',
                type: MeasureType::fromNative('VOLUME'),
                limit: 100,
                defaultTags: ['publisher'],
            ),
        ]
    ),
);
```

#### Build progressively
```php
$metre = new Metre();

// create a measure to limit the amount of "user accounts"
$metre->addMeasure('user_accounts');
$metre->setMeasureType('user_accounts', MeasureType::fromNative('VOLUME')); // set volume since it defaults to "METERED"
$metre->setMeasureLimit('user_accounts', 5);

$metre->addMeasure('orders'
    type: MeasureType::fromNative('METERED'),
    defaultTags: ['sales'],
);
```

### Increment usage
```php
$metre->increment('user_accounts'); // increment usage
$metre->increment('user_accounts', count: 2); // increment usage by 2
```

### Check usability
Use the `canUse` method to determine whether a measure is usable. Returns `bool`.
```php
$metre->canUse('user_accounts');
$metre->canUse('user_accounts', count: 2); // If usable by 2 counts
$metre->canUse('user_accounts', tags: [...], period: $period); // Check against tags and period
```

### Get usage

Depending of the measure type (`METERED` or `VOLUME`), calculates usage for current period if metreed. Calculates usage for all time if volumed.

```php
$usage = $metre->usage('user_accounts');
$usage->count; // amount used
$usage->limit; // measure/usage limit
$usage->entries; // amount of entries
$usage->percentage(); // get percentage used. Returns null if no limit is set
```

To get usage within a set time, provide a period.
```php
$usage = $metre->usage('user_accounts',
    period: now()->subHours(12)->unix(),
);
$usage = $metre->usage('user_accounts',
    period: [
      now()->subDays(2)->unix(), // get usage from this time and ignore measure type
      now()->unix(),
    ],
);
```


### Clear usage
```php
$metre->clear(); // clear all
$metre->clear('user_accounts'); // clear "user_accounts"
$metre->clear('user_accounts', 'orders'); // clear "user_accounts" and "orders"
$metre->clear(['user_accounts', 'orders']); // clear "user_accounts" and "orders"
$metre->clear('unknown_measure'); // fails, "unknown_measure" doesnt exist
```

### Periods

Start a new period
```php
$metre->newPeriod();

// or start a new period for 2 hours from now
$metre->newPeriod(now()->addHours(2));
```

### Tags
Tags make it possible to categorise and group usage on a single measure. E.g. a *product* purchase, and *ticket* purchase may both be considered orders. However your users' subscription may be billed depending or the type of *sale*.

```php
$metre->setMeasureDefaultTags('orders', ['sales']);
$metre->increment('orders', tags: ['product']);
$metre->increment('orders', tags: ['booking']);
$metre->increment('orders', tags: ['ticket', 'booking']);
```

Now usage can be checked per measure tags:
```php
expect($metre->usage('orders')->count)->toEqual(3);
expect($metre->usage('orders', tags: ['sales'])->count)->toEqual(3);
expect($metre->usage('orders', tags: ['booking'])->count)->toEqual(2);
expect($metre->usage('orders', tags: ['ticket'])->count)->toEqual(1);
```

### With eloquent attributes
To use directly with Eloquent, use the provided Caster.

```php
...
use MOIREI\Metre\MetreCaster;

class Subscription extends Model
{
    ...

    /**
     * The attributes that should be casted.
     *
     * @var array
     */
    protected $casts = [
        ...
        'usage' => MetreCaster::class,
    ];
    ...
}
```

Now, perform user action according to usage status
```php
if($user->subscription->usage->canUse('monthly_npm3_downloads')){
  $user->download($file);
  $user->subscription->usage->increment('monthly_npm3_downloads');
}
```

## Tests
```bash
./vendor/bin/pest
```