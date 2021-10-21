<?php

namespace MOIREI\Metre;

use MOIREI\Metre\Exceptions\MeasureExhaustedException;
use MOIREI\Metre\Exceptions\MeasureUnkownException;
use MOIREI\Metre\Objects\Measure;
use MOIREI\Metre\Objects\MeasureType;
use MOIREI\Metre\Objects\MetreInput;
use MOIREI\Metre\Objects\Usage;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidPeriodParametreException;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;

class Metre implements Arrayable, JsonSerializable
{
    /**
     * @var array
     */
    protected array $data;

    public function __construct(
        array $data = []
    ) {
        $this->data = $data;
    }

    /**
     * @param float $startOfPeriods
     * @param MetreMeasure[] $measures
     */
    public static function make(MetreInput $input)
    {
        return new static([
            'startOfPeriods' => $input->startOfPeriods,
            'periods' => $input->periods,
            'measures' => array_reduce($input->measures, function ($acc, Measure $measure) {
                $acc[$measure->name] = $measure->except('name')->toArray();
                return $acc;
            }, []),
        ]);
    }

    /**
     * Increment metre usage
     *
     * @param string $measure
     * @param int $count
     * @param string[]|string $tags
     * @throws \MOIREI\Metre\Exceptions\MeasureExhaustedException
     * @throws \MOIREI\Metre\Exceptions\MeasureUnkownException
     * @return \Carbon\Carbon
     */
    public function increment(
        string $measure,
        int $count = 1,
        array $tags = [],
        Carbon $ts = null,
    ) {
        if (!$this->canUse(
            measure: $measure,
            count: $count,
            tags: $tags,
        )) {
            throw new MeasureExhaustedException;
        }

        $this->assertHasMeasure($measure);

        $defaultTags = $this->getDataAttribute("measures.$measure.defaultTags");
        $tags = array_unique(array_merge($tags, $defaultTags));

        $entries = $this->getDataAttribute("measures.$measure.entries");
        $entries[] = [
            'ts' => optional($ts)->unix() ?? now()->unix(),
            'count' => $count,
            'tags' => $tags,
        ];

        $this->setDataAttribute("measures.$measure.entries", $entries);
    }

    /**
     * Clear measure entries
     *
     * @param array|string|null $name
     */
    public function clear(array|string $measure = null)
    {
        if (is_null($measure)) {
            $measures = array_keys($this->getDataAttribute('measures', []));
        } else {
            $measures = is_array($measure) ? $measure : func_get_args();
        }

        foreach ($measures as $measure) {
            $this->assertHasMeasure($measure);
            $this->setDataAttribute("measures.$measure.entries", []);
        }
    }

    /**
     * Start a new period.
     *
     * @param \Carbon\Carbon $ts
     * @throws \Carbon\Exceptions\InvalidPeriodParametreException
     */
    public function newPeriod(Carbon $ts = null)
    {
        $periods = $this->getDataAttribute('periods', []);
        if ($ts) {
            $ts = $ts->unix();
            if (count($periods) && $ts <= max($periods)) {
                throw new InvalidPeriodParametreException("New period must be greater than previous periods.");
            }
        } else {
            $ts = now()->unix();
        }
        $periods[] = $ts;
        $this->setDataAttribute('periods', $periods);
    }

    /**
     * Get measure usage.
     * Period is clear if not a metreed measure.
     *
     * @param string $measure
     * @param string[]|string $tags
     * @param float[]|float $period
     * @return \MOIREI\Metre\Objects\Usage
     */
    public function usage(
        string $measure,
        array $tags = [],
        array|float $period = null,
    ) {
        $type = MeasureType::fromValue($this->getDataAttribute("measures.$measure.type"));
        if (!is_array($period)) {
            $period = [
                $type->is(MeasureType::VOLUME) ? $this->startOfPeriods() : $this->lastPeriod(),
                $period ?? now()->unix(),
            ];
        }

        $entries = $this->getMeasureEntries($measure, tags: $tags, period: $period);
        $count = $entries->sum('count');
        $limit = $this->getDataAttribute("measures.$measure.limit");

        return new Usage([
            'count' => $count,
            'limit' => $limit,
            'entries' => $entries->count(),
        ]);
    }

    /**
     * Check if measure can be used
     *
     * @param string $measure
     * @param int $count
     * @param string[]|string $tags
     * @param float[]|float $period
     * @return bool
     */
    public function canUse(
        string $measure,
        int $count = 1,
        array $tags = [],
        array|float $period = null,
    ): bool {
        if (!is_numeric($this->getDataAttribute("measures.$measure.limit"))) {
            return true;
        }

        if (!is_array($period)) {
            $period = [
                $this->lastPeriod(),
                $period ?? now()->unix(),
            ];
        }

        $usage = $this->usage(
            $measure,
            tags: $tags,
            period: $period,
        );

        return ($usage->count + $count) < $usage->limit;
    }

    /**
     * Add a new measure to the metre
     *
     * @param string $name
     * @param \MOIREI\Metre\Objects\MeasureType $type
     * @param float $limit
     * @param string[] $defaultTags
     */
    public function addMeasure(
        string $name,
        MeasureType $type = null,
        float $limit = null,
        array $defaultTags = [],
    ) {
        $this->setDataAttribute("measures.$name", [
            'type' => (string)$type ?: MeasureType::METERED,
            'limit' => $limit,
            'defaultTags' => $defaultTags,
        ]);
    }

    /**
     * Get a measure
     *
     * @param string $name
     * @throws \MOIREI\Metre\Exceptions\MeasureUnkownException
     * @return \MOIREI\Metre\Objects\Measure
     */
    public function getMeasure(string $name): Measure
    {
        $this->assertHasMeasure($name);
        $data = $this->getDataAttribute("measures.$name");
        $data['name'] = $name;
        $data['type'] = MeasureType::fromValue($data['type']);
        return new Measure($data);
    }

    /**
     * Set measure type
     *
     * @param string $name
     * @param \MOIREI\Metre\Objects\MeasureType $type
     */
    public function setMeasureType(
        string $name,
        MeasureType $type,
    ) {
        $this->setDataAttribute("measures.$name.type", (string)$type);
    }

    /**
     * Set measure usage limit.
     *
     * @param string $name
     * @param float $limit
     */
    public function setMeasureLimit(
        string $name,
        float $limit,
    ) {
        $this->setDataAttribute("measures.$name.limit", $limit);
    }

    /**
     * Set measure default tags.
     *
     * @param string $name
     * @param array $tags
     */
    public function setMeasureDefaultTags(
        string $name,
        array $tags,
    ) {
        $this->setDataAttribute("measures.$name.defaultTags", $tags);
    }

    /**
     * Get the entry points of a measure
     *
     * @param string $name
     * @param array $tags
     * @param array|float $period
     * @return \Illuminate\Support\Collection
     */
    public function getMeasureEntries(
        string $name,
        array $tags = [],
        array|float $period = null,
    ): Collection {
        $this->assertHasMeasure($name);
        $entries = $this->getDataAttribute("measures.$name.entries", []);
        // $defaultTags = $this->getDataAttribute("measures.$name.defaultTags", []);
        // $tags = array_unique(array_merge($tags, $defaultTags));

        if (!is_array($period)) {
            $period = [
                $this->lastPeriod(),
                $period ?? now()->unix(),
            ];
        }

        return (new Collection($entries))
            ->sortBy('ts')
            ->values()
            ->whereBetween('ts', $period)
            ->when(!empty($tags), function (Collection $collection) use ($tags) {
                return $collection->filter(fn ($item) => !empty(array_intersect($item['tags'], $tags))); // if $tags contains any of $item['tags']
            });
    }

    /**
     * Get the last period of the metre (unix time)
     *
     * @return int
     */
    public function lastPeriod(): int
    {
        $lastPeriod = (new Collection($this->getDataAttribute('periods')))->last();
        if (!$lastPeriod) {
            $lastPeriod = $this->startOfPeriods();
        }
        return $lastPeriod;
    }

    /**
     * Get the last period of the metre (unix time)
     *
     * @return int
     */
    public function startOfPeriods(): int
    {
        return $this->getDataAttribute('startOfPeriods', 0);
    }

    /**
     * Check if a measure exists in the metre
     *
     * @param string $name
     */
    public function hasMeasure(string $name): bool
    {
        return Arr::has($this->data, "measures.$name");
    }

    /**
     * Remove a measure from the metre
     *
     * @param string $name
     */
    public function removeMeasure(string $name)
    {
        Arr::forget($this->data, "measures.$name");
    }

    /**
     * Get a data attribute
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getDataAttribute(string $key, $default = null)
    {
        return Arr::get($this->data, $key, $default);
    }

    /**
     * Set a data attribute
     *
     * @param string $key
     * @param mixed $value
     */
    protected function setDataAttribute(string $key, $value)
    {
        Arr::set($this->data, $key, $value);
    }

    /**
     * Assert that the measure exists
     *
     * @param string $name
     * @throws \MOIREI\Metre\Exceptions\MeasureUnkownException
     */
    protected function assertHasMeasure(string $name)
    {
        if (!$this->hasMeasure($name)) {
            throw new MeasureUnkownException($name);
        }
    }

    /**
     * Get the data array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Serialise
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
