<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Testers;

use Closure;
use Dashworthy\PestPluginVisualizations\Concerns\AssertsVisualizationEndpoints;
use Dashworthy\PestPluginVisualizations\Concerns\ResolvesVisualizableFields;
use Dashworthy\Visualizations\Contracts\VisualizationContract;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\Metrics\Abstracts\Metric;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;
use Illuminate\Database\Query\Builder;

final class MetricTester
{
    use AssertsVisualizationEndpoints;
    use ResolvesVisualizableFields;

    private Metric $metric;

    private VisualizationData $visualizationData;

    public function __construct(string $class)
    {
        if (! class_exists($class)) {
            test()->fail("Class [{$class}] does not exist.");
        }

        if (! is_subclass_of($class, Metric::class)) {
            test()->fail("Class [{$class}] is not a Metric.");
        }

        $this->metric = new $class;
        $this->visualizationData = new VisualizationData;
    }

    public function usingFilterSets(Closure $configure): static
    {
        $this->visualizationData = new VisualizationData;
        $configure($this->visualizationData);

        return $this;
    }

    public function assertHasValue(string $field): static
    {
        $value = $this->metric->getValue();

        if (! $this->matchesField($value, $field)) {
            test()->fail("Value [{$field}] was not found in the Metric schema. Found: [{$value->getField()}].");
        }

        expect($this->matchesField($value, $field))->toBeTrue();

        return $this;
    }

    public function assertHasFloatingFilter(string $field): static
    {
        $filter = $this->findByField($this->metric->getFloatingFilters(), $field);

        if ($filter === null) {
            test()->fail("Floating filter [{$field}] was not found in the Metric schema.");
        }

        expect($filter)->not->toBeNull();

        return $this;
    }

    public function assertMissingFloatingFilter(string $field): static
    {
        $filter = $this->findByField($this->metric->getFloatingFilters(), $field);

        expect($filter)->toBeNull("Floating filter [{$field}] was found but should not exist.");

        return $this;
    }

    public function assertAggregateEquals(mixed $expected): static
    {
        $result = $this->buildQuery()->first();
        $field = $this->metric->getValue()->getField();
        $actual = $result->{$field} ?? null;

        expect($actual)->toEqual($expected);

        return $this;
    }

    public function assertAggregateNull(): static
    {
        $result = $this->buildQuery()->first();
        $field = $this->metric->getValue()->getField();
        $actual = $result->{$field} ?? null;

        expect($actual)->toBeNull();

        return $this;
    }

    private function buildQuery(): Builder
    {
        $visualizables = $this->metric->getVisualizables();
        $this->resolveVisualizationData($this->visualizationData, $visualizables);

        return GenerateVisualizationQuery::make()->handle(
            $this->metric->getQuery(),
            $visualizables,
            $this->visualizationData
        );
    }

    protected function visualization(): VisualizationContract
    {
        return $this->metric;
    }
}
