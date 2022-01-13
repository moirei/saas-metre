<?php

namespace Tests\Unit;

use MOIREI\Metre\Objects\MeasureType;
use MOIREI\Metre\Metre;

it('expects FEATURE measure "has_accounts" to exist', function () {
    $metre = new Metre();
    $metre->addMeasure('has_accounts', MeasureType::FEATURE());
    $this->assertTrue($metre->hasMeasure('has_accounts'));
    $measure = $metre->getMeasure('has_accounts');
    expect($measure->name)->toEqual('has_accounts');
});

it('expects FEATURE measure to allow usage', function () {
    $metre = new Metre();
    $metre->addMeasure('has_accounts', MeasureType::FEATURE(), true);
    expect($metre->canUse('has_accounts'))->toBeTrue();
});

it('expects FEATURE measure to restrict usage', function () {
    $metre = new Metre();
    $metre->addMeasure('has_accounts', MeasureType::FEATURE(), false);
    expect($metre->canUse('has_accounts'))->toBeFalse();
});
