<?php

use MOIREI\Metre\Metre;
use MOIREI\Metre\MetreCaster;
use MOIREI\Metre\Objects\MeasureType;
use Illuminate\Database\Eloquent\Model;

beforeEach(function () {
    $attributes = [
        'usage' => [
            'startOfPeriods' => now()->unix(),
            'measures' => [
                'user_accounts' => [
                    'type' => MeasureType::METERED,
                    'limit' => 100,
                    'defaultTags' => ['publisher'],
                ]
            ]
        ]
    ];

    $this->model = new class($attributes) extends Model
    {
        protected $fillable = [
            'usage',
        ];
        protected $casts = [
            'usage' => MetreCaster::class,
        ];
    };
});

it('expects attribute to be instance Metre::class', function () {
    expect($this->model->usage)->toBeInstanceOf(Metre::class);
});

it('expects measure "user_accounts" to exist', function () {
    $this->model->usage->addMeasure('user_accounts');
    $this->assertTrue($this->model->usage->hasMeasure('user_accounts'));
    $measure = $this->model->usage->getMeasure('user_accounts');
    expect($measure->name)->toEqual('user_accounts');
});
