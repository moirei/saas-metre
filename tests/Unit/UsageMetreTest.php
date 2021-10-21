<?php

namespace Tests\Unit;

use MOIREI\Metre\Exceptions\MeasureExhaustedException;
use MOIREI\Metre\Objects\Measure;
use MOIREI\Metre\Objects\MeasureType;
use MOIREI\Metre\Objects\MetreInput;
use MOIREI\Metre\Metre;

beforeEach(function () {
    $this->metre = new Metre();
    $this->metre->addMeasure('user_accounts');
});

it('expects measure "user_accounts" to exist', function () {
    $this->assertTrue($this->metre->hasMeasure('user_accounts'));
    $measure = $this->metre->getMeasure('user_accounts');
    expect($measure->name)->toEqual('user_accounts');
});

it('expects usage to be 0', function () {
    $usage = $this->metre->usage('user_accounts');
    expect($usage->count)->toEqual(0);
});

it('expects usage to be 3', function () {
    $this->metre->increment('user_accounts');
    $this->metre->increment('user_accounts', count: 2);
    $usage = $this->metre->usage('user_accounts');
    expect($usage->count)->toEqual(3);
});

it('expects cleared measure to be 0', function () {
    $this->metre->increment('user_accounts');
    $this->metre->clear('user_accounts');
    $usage = $this->metre->usage('user_accounts');
    expect($usage->count)->toEqual(0);
});

it('expects future/past usage to not count', function () {
    $this->metre->clear('user_accounts');
    $this->metre->increment('user_accounts');
    $this->metre->increment('user_accounts', ts: now()->subDays(2));
    $this->metre->newPeriod(now()); // start now new period
    $this->metre->increment('user_accounts', ts: now()->addDays(2));
    $usage = $this->metre->usage('user_accounts');
    expect($usage->count)->toEqual(1);
});

it('expects past usage to count', function () {
    $this->metre->clear('user_accounts');
    $this->metre->increment('user_accounts'); // +1
    $this->metre->setMeasureType('user_accounts', MeasureType::fromNative('VOLUME'));
    $this->metre->increment('user_accounts', ts: now()->addDays(2)); // future
    $this->metre->newPeriod();
    $this->metre->increment('user_accounts'); // +1
    $this->metre->increment('user_accounts', ts: now()->subDays(2)); // // +1, in the past
    $usage = $this->metre->usage('user_accounts');
    expect($usage->count)->toEqual(3);
});

it('expects period usage to be metreed', function () {
    $this->metre->clear('user_accounts');
    $this->metre->increment('user_accounts');
    expect($this->metre->usage('user_accounts')->count)->toEqual(1);
    sleep(1);
    $this->metre->newPeriod();
    $this->metre->increment('user_accounts');
    $this->metre->increment('user_accounts');
    expect($this->metre->usage('user_accounts')->count)->toEqual(2);
    sleep(1);
    $this->metre->newPeriod();
    $this->metre->increment('user_accounts', count: 3);
    expect($this->metre->usage('user_accounts')->count)->toEqual(3);
});

it('expects METERED overuse to throw exception', function () {
    $this->metre->clear('user_accounts');
    $this->metre->setMeasureLimit('user_accounts', 5);
    $this->metre->increment('user_accounts', count: 5);
    expect($this->metre->usage('user_accounts')->count)->toEqual(5);
    sleep(1);
    $this->metre->newPeriod();
    $this->metre->increment('user_accounts', count: 2);
    expect($this->metre->usage('user_accounts')->count)->toEqual(2);
    expect($this->metre->canUse('user_accounts', count: 3))->toBeFalse();
    $this->metre->increment('user_accounts', count: 3); // <--- exception
})->throws(MeasureExhaustedException::class);

it('expects VOLUME overuse to throw exception', function () {
    $this->metre->clear('user_accounts');
    $this->metre->setMeasureType('user_accounts', MeasureType::fromNative('VOLUME'));
    $this->metre->setMeasureLimit('user_accounts', 5);
    $this->metre->increment('user_accounts', count: 5);
    sleep(1);
    $this->metre->newPeriod(); // even in new period
    expect($this->metre->canUse('user_accounts', count: 2))->toBeFalse();
    $this->metre->increment('user_accounts', count: 2); // <--- exception
})->throws(MeasureExhaustedException::class);

it('expects removed measure to not exists', function () {
    $this->metre->removeMeasure('user_accounts');
    expect($this->metre->hasMeasure('user_accounts'))->toBeFalse();
});

it('expects tags to determine usage', function () {
    $this->metre->addMeasure(
        'orders',
        type: MeasureType::fromNative('METERED'),
    );
    $this->metre->setMeasureDefaultTags('orders', ['sales']);
    $this->metre->increment('orders', tags: ['product']);
    $this->metre->increment('orders', tags: ['service']);
    $this->metre->increment('orders', tags: ['event', 'service']);
    expect($this->metre->usage('orders')->count)->toEqual(3);
    expect($this->metre->usage('orders', tags: ['sales'])->count)->toEqual(3);
    expect($this->metre->usage('orders', tags: ['service'])->count)->toEqual(2);
    expect($this->metre->usage('orders', tags: ['event'])->count)->toEqual(1);
});

it('tests Metre::make(...)', function () {
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
    $metre->addMeasure(
        'orders',
        type: MeasureType::fromNative('METERED'),
        defaultTags: ['sales'],
    );

    $metre->increment('user_accounts');
    $metre->increment('user_accounts', count: 2);
    $metre->increment('orders', count: 2);
    expect($metre->usage('user_accounts')->count)->toEqual(3);
    expect($metre->usage('orders')->count)->toEqual(2);
    $metre->clear('user_accounts', 'orders');
    expect($metre->usage('user_accounts')->count)->toEqual(0);
    expect($metre->usage('orders')->count)->toEqual(0);
    $metre->increment('user_accounts', count: 101);
})->throws(MeasureExhaustedException::class);
