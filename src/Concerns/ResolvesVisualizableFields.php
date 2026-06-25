<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Concerns;

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\VisualizationData;
use Illuminate\Support\Collection;

/**
 * Bridges the developer-facing field name (the second argument passed to a
 * visualizable's make(), e.g. 'date') and the prefixed identifier the
 * Visualizations package uses internally (e.g. 'label_date').
 *
 * The package prefixes every visualizable's field with a per-type identifier
 * (label_, dataset_, column_, floating_filter_, value_) to prevent collisions
 * between, say, a column and a floating filter on the same underlying field.
 * Tests assert against the friendly name, so the testers resolve friendly
 * names to prefixed identifiers wherever they touch the schema, the query, or
 * a result row.
 */
trait ResolvesVisualizableFields
{
    /**
     * Whether the given visualizable matches a developer-facing field name.
     *
     * Accepts both the friendly name ('date') and the already-prefixed
     * identifier ('label_date'), so resolution is idempotent and either form
     * works in a test.
     */
    protected function matchesField(Visualizable $visualizable, string $field): bool
    {
        return $visualizable->getField() === $field
            || $visualizable->getField() === $visualizable->getFieldPrefix().$field;
    }

    /**
     * Find the visualizable in the collection matching a friendly field name.
     *
     * @param  Collection<int, Visualizable>  $collection
     */
    protected function findByField(Collection $collection, string $field): ?Visualizable
    {
        return $collection->first(fn (Visualizable $v) => $this->matchesField($v, $field));
    }

    /**
     * Resolve a friendly field name to the prefixed identifier used in the
     * query and result schema. Falls back to the given field when nothing
     * matches, so unknown fields surface as a normal "not found" assertion.
     *
     * @param  Collection<int, Visualizable>  $visualizables
     */
    protected function resolveField(Collection $visualizables, string $field): string
    {
        return $this->findByField($visualizables, $field)?->getField() ?? $field;
    }

    /**
     * Rewrite the filter-set and sort fields on the VisualizationData from
     * friendly names to prefixed identifiers, in place. Idempotent: an
     * already-prefixed field resolves to itself.
     *
     * @param  Collection<int, Visualizable>  $visualizables
     */
    protected function resolveVisualizationData(VisualizationData $data, Collection $visualizables): void
    {
        foreach ($data->filterSets as $filterSet) {
            foreach ($filterSet->filters as $filter) {
                $filter->field = $this->resolveField($visualizables, $filter->field);
            }
        }

        foreach ($data->sorts as $sort) {
            $sort->field = $this->resolveField($visualizables, $sort->field);
        }
    }

    /**
     * Translate the keys of an expected result row from friendly names to the
     * prefixed identifiers used as column aliases in the generated query.
     *
     * @param  array<string, mixed>  $expected
     * @param  Collection<int, Visualizable>  $visualizables
     * @return array<string, mixed>
     */
    protected function resolveExpectedRow(array $expected, Collection $visualizables): array
    {
        $resolved = [];

        foreach ($expected as $field => $value) {
            $resolved[$this->resolveField($visualizables, $field)] = $value;
        }

        return $resolved;
    }
}
