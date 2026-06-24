<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Testers;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Dashworthy\Visualizations\Charts\Abstracts\Chart;
use Dashworthy\Visualizations\Charts\Labels\NullLabel;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;

final class ChartTester
{
    private Chart $chart;

    private VisualizationData $visualizationData;

    public function __construct(string $class)
    {
        if (! class_exists($class)) {
            test()->fail("Class [{$class}] does not exist.");
        }

        if (! is_subclass_of($class, Chart::class)) {
            test()->fail("Class [{$class}] is not a Chart.");
        }

        $this->chart = new $class;
        $this->visualizationData = new VisualizationData;
    }

    public function usingFilterSets(Closure $configure): static
    {
        $this->visualizationData = new VisualizationData;
        $configure($this->visualizationData);

        return $this;
    }

    public function usingSorts(Closure $configure): static
    {
        $this->visualizationData = new VisualizationData;
        $configure($this->visualizationData);

        return $this;
    }

    public function assertHasLabel(string $field): static
    {
        $label = $this->chart->getLabel();

        if ($label instanceof NullLabel) {
            test()->fail("Chart has no label (uses NullLabel). Label [{$field}] was not found.");
        }

        if ($label->getField() !== $field) {
            test()->fail("Label [{$field}] was not found. Found: [{$label->getField()}].");
        }

        expect($label)->toBeObject();

        return $this;
    }

    public function assertHasNoLabel(): static
    {
        expect($this->chart->getLabel())->toBeInstanceOf(NullLabel::class);

        return $this;
    }

    public function assertHasDataset(string $field): static
    {
        $dataset = $this->chart->getDatasets()
            ->first(fn ($d) => $d->getField() === $field);

        if ($dataset === null) {
            test()->fail("Dataset [{$field}] was not found in the Chart schema.");
        }

        expect($dataset)->toBeObject();

        return $this;
    }

    public function assertMissingDataset(string $field): static
    {
        $dataset = $this->chart->getDatasets()
            ->first(fn ($d) => $d->getField() === $field);

        expect($dataset)->toBeNull("Dataset [{$field}] was found but should not exist.");

        return $this;
    }

    public function assertDatasetCount(int $count): static
    {
        expect($this->chart->getDatasets())->toHaveCount($count);

        return $this;
    }

    public function assertHasFloatingFilter(string $field): static
    {
        $filter = $this->chart->getFloatingFilters()
            ->first(fn ($f) => $f->getField() === $field);

        if ($filter === null) {
            test()->fail("Floating filter [{$field}] was not found in the Chart schema.");
        }

        expect($filter)->toBeObject();

        return $this;
    }

    public function assertMissingFloatingFilter(string $field): static
    {
        $filter = $this->chart->getFloatingFilters()
            ->first(fn ($f) => $f->getField() === $field);

        expect($filter)->toBeNull("Floating filter [{$field}] was found but should not exist.");

        return $this;
    }

    public function assertResultCount(int $count): static
    {
        $actual = DB::table($this->buildQuery(), 'sub')->count();
        expect($actual)->toBe($count);

        return $this;
    }

    public function assertNoResults(): static
    {
        $count = DB::table($this->buildQuery(), 'sub')->count();
        expect($count)->toBe(0);

        return $this;
    }

    public function assertResultContains(array $expected): static
    {
        $results = $this->runQuery();
        $filtered = $results;

        foreach ($expected as $key => $value) {
            $filtered = $filtered->filter(
                fn ($row) => array_key_exists($key, $row) && $row[$key] == $value
            );
        }

        if ($filtered->isEmpty()) {
            test()->fail(
                'No result matching '.json_encode($expected)." was found.\nResults:\n".
                json_encode($results->values()->toArray(), JSON_PRETTY_PRINT)
            );
        }

        expect($filtered->isNotEmpty())->toBeTrue();

        return $this;
    }

    public function assertResultMissing(array $expected): static
    {
        $results = $this->runQuery();
        $filtered = $results;

        foreach ($expected as $key => $value) {
            $filtered = $filtered->filter(
                fn ($row) => array_key_exists($key, $row) && $row[$key] == $value
            );
        }

        if ($filtered->isNotEmpty()) {
            test()->fail(
                'A result matching '.json_encode($expected)." was found but should not exist.\nResults:\n".
                json_encode($results->values()->toArray(), JSON_PRETTY_PRINT)
            );
        }

        expect($filtered->isEmpty())->toBeTrue();

        return $this;
    }

    private function buildQuery(): Builder
    {
        return GenerateVisualizationQuery::make()->handle(
            $this->chart->getQuery(),
            $this->chart->getVisualizables(),
            $this->visualizationData
        );
    }

    private function runQuery(): Collection
    {
        return $this->buildQuery()
            ->get()
            ->map(fn ($row): array => (array) $row);
    }
}
