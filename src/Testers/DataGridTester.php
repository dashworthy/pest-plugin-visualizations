<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Testers;

use Closure;
use Dashworthy\PestPluginVisualizations\Concerns\ResolvesVisualizableFields;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;

final class DataGridTester
{
    use ResolvesVisualizableFields;

    private DataGrid $dataGrid;

    private VisualizationData $visualizationData;

    public function __construct(string $class)
    {
        if (! class_exists($class)) {
            test()->fail("Class [{$class}] does not exist.");
        }

        if (! is_subclass_of($class, DataGrid::class)) {
            test()->fail("Class [{$class}] is not a DataGrid.");
        }

        $this->dataGrid = new $class;
        $this->visualizationData = new VisualizationData;
    }

    public function assertHasColumn(string $field): static
    {
        expect($this->findColumn($field))->toBeArray();

        return $this;
    }

    public function assertMissingColumn(string $field): static
    {
        $column = $this->findByField($this->dataGrid->getColumns(), $field);

        expect($column)->toBeNull("Column [{$field}] was found but should not exist in the DataGrid schema.");

        return $this;
    }

    public function assertColumnCount(int $count): static
    {
        expect($this->dataGrid->getColumns())->toHaveCount($count);

        return $this;
    }

    public function assertColumnIsSortable(string $field): static
    {
        expect($this->findColumn($field)['is_sortable'])->toBeTrue("Column [{$field}] is not sortable.");

        return $this;
    }

    public function assertColumnIsNotSortable(string $field): static
    {
        expect($this->findColumn($field)['is_sortable'])->toBeFalse("Column [{$field}] is sortable but should not be.");

        return $this;
    }

    public function assertColumnIsFilterable(string $field): static
    {
        expect($this->findColumn($field)['is_filterable'])->toBeTrue("Column [{$field}] is not filterable.");

        return $this;
    }

    public function assertColumnIsNotFilterable(string $field): static
    {
        expect($this->findColumn($field)['is_filterable'])->toBeFalse("Column [{$field}] is filterable but should not be.");

        return $this;
    }

    public function assertColumnIsHidden(string $field): static
    {
        expect($this->findColumn($field)['is_hidden'])->toBeTrue("Column [{$field}] is not hidden.");

        return $this;
    }

    public function assertColumnIsVisible(string $field): static
    {
        expect($this->findColumn($field)['is_hidden'])->toBeFalse("Column [{$field}] is hidden.");

        return $this;
    }

    public function assertColumnIsRowKey(string $field): static
    {
        expect($this->findColumn($field)['is_row_key'])->toBeTrue("Column [{$field}] is not a row key.");

        return $this;
    }

    public function assertHasFloatingFilter(string $field): static
    {
        $filter = $this->findByField($this->dataGrid->getFloatingFilters(), $field);

        if ($filter === null) {
            test()->fail("Floating filter [{$field}] was not found in the DataGrid schema.");
        }

        expect($filter)->not->toBeNull();

        return $this;
    }

    public function assertMissingFloatingFilter(string $field): static
    {
        $filter = $this->findByField($this->dataGrid->getFloatingFilters(), $field);

        expect($filter)->toBeNull("Floating filter [{$field}] was found but should not exist.");

        return $this;
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

    public function assertRowCount(int $expected): static
    {
        $count = DB::table($this->buildQuery(), 'sub')->count();
        expect($count)->toBe($expected);

        return $this;
    }

    public function assertNoResults(): static
    {
        $count = DB::table($this->buildQuery(), 'sub')->count();
        expect($count)->toBe(0);

        return $this;
    }

    public function assertRowMatches(array $expected): static
    {
        $results = $this->runQuery();
        $expected = $this->resolveExpectedRow($expected, $this->dataGrid->getVisualizables());
        $filtered = $results;

        foreach ($expected as $key => $value) {
            $filtered = $filtered->filter(
                fn ($row) => array_key_exists($key, $row) && $row[$key] == $value
            );
        }

        if ($filtered->isEmpty()) {
            test()->fail(
                'No row matching '.json_encode($expected)." was found.\nResults:\n".
                json_encode($results->values()->toArray(), JSON_PRETTY_PRINT)
            );
        }

        expect($filtered->isNotEmpty())->toBeTrue();

        return $this;
    }

    public function assertRowMissing(array $expected): static
    {
        $results = $this->runQuery();
        $expected = $this->resolveExpectedRow($expected, $this->dataGrid->getVisualizables());
        $filtered = $results;

        foreach ($expected as $key => $value) {
            $filtered = $filtered->filter(
                fn ($row) => array_key_exists($key, $row) && $row[$key] == $value
            );
        }

        if ($filtered->isNotEmpty()) {
            test()->fail(
                'A row matching '.json_encode($expected)." was found but should not exist.\nResults:\n".
                json_encode($results->values()->toArray(), JSON_PRETTY_PRINT)
            );
        }

        expect($filtered->isEmpty())->toBeTrue();

        return $this;
    }

    private function buildQuery(): Builder
    {
        $visualizables = $this->dataGrid->getVisualizables();
        $this->resolveVisualizationData($this->visualizationData, $visualizables);

        return GenerateVisualizationQuery::make()->handle(
            $this->dataGrid->getQuery(),
            $visualizables,
            $this->visualizationData
        );
    }

    private function runQuery(): Collection
    {
        return $this->buildQuery()
            ->get()
            ->map(fn ($row): array => (array) $row);
    }

    private function findColumn(string $field): array
    {
        $column = $this->findByField($this->dataGrid->getColumns(), $field);

        if ($column === null) {
            test()->fail("Column [{$field}] was not found in the DataGrid schema.");
        }

        return $column->toArray();
    }
}
