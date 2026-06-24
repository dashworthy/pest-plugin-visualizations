<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\AssertionFailedError;
use Dashworthy\PestPluginVisualizations\Tests\Fixtures\Metrics\RevenueMetric;
use Dashworthy\PestPluginVisualizations\Tests\Fixtures\Metrics\RevenueWithFloatingFiltersMetric;
use Dashworthy\Visualizations\Builders\FilterBuilder;
use Dashworthy\Visualizations\Data\VisualizationData;

use function Dashworthy\PestPluginVisualizations\metric;

// --- Construction ---

it('constructs successfully with a valid Metric class', function () {
    expect(metric(RevenueMetric::class))->toBeObject();
});

it('fails when the class does not exist', function () {
    expect(fn () => metric('NonExistentClass'))
        ->toThrow(AssertionFailedError::class, 'Class [NonExistentClass] does not exist.');
});

it('fails when the class is not a Metric', function () {
    expect(fn () => metric(stdClass::class))
        ->toThrow(AssertionFailedError::class, 'is not a Metric');
});

// --- Schema ---

it('asserts the value field', function () {
    metric(RevenueMetric::class)->assertHasValue('revenue');
});

it('fails assertHasValue when field does not match', function () {
    expect(fn () => metric(RevenueMetric::class)->assertHasValue('wrong'))
        ->toThrow(AssertionFailedError::class, 'Value [wrong] was not found');
});

it('asserts a floating filter exists', function () {
    metric(RevenueWithFloatingFiltersMetric::class)->assertHasFloatingFilter('date_range');
});

it('asserts a floating filter is missing', function () {
    metric(RevenueMetric::class)->assertMissingFloatingFilter('date_range');
});

it('fails assertHasFloatingFilter when filter is absent', function () {
    expect(fn () => metric(RevenueMetric::class)->assertHasFloatingFilter('date_range'))
        ->toThrow(AssertionFailedError::class, 'Floating filter [date_range] was not found');
});

// --- Data assertions ---

it('asserts the aggregate equals expected value', function () {
    DB::table('orders')->insert([
        ['total' => 100.00, 'refunds' => 0.00],
        ['total' => 200.00, 'refunds' => 0.00],
        ['total' => 50.00, 'refunds' => 0.00],
    ]);

    metric(RevenueMetric::class)->assertAggregateEquals(350.0);
});

it('asserts the aggregate is null when the table is empty', function () {
    // No rows inserted — SUM of an empty set returns null in SQLite.
    metric(RevenueMetric::class)->assertAggregateNull();
});

it('asserts aggregate with filter sets', function () {
    DB::table('orders')->insert([
        ['order_date' => '2024-01-01', 'total' => 100.00, 'refunds' => 0.00],
        ['order_date' => '2024-01-02', 'total' => 200.00, 'refunds' => 0.00],
        ['order_date' => '2024-01-03', 'total' => 50.00, 'refunds' => 0.00],
    ]);

    metric(RevenueWithFloatingFiltersMetric::class)
        ->usingFilterSets(fn (VisualizationData $data) => $data->addAndFilterSet(
            fn (FilterBuilder $b) => $b->equals('date_range', '2024-01-01')
        ))
        ->assertAggregateEquals(100.0);
});

it('fails assertAggregateEquals when value does not match', function () {
    DB::table('orders')->insert([
        ['total' => 100.00, 'refunds' => 0.00],
    ]);

    expect(fn () => metric(RevenueMetric::class)->assertAggregateEquals(999.0))
        ->toThrow(AssertionFailedError::class);
});

it('switches filter context mid-chain', function () {
    DB::table('orders')->insert([
        ['order_date' => '2024-01-01', 'total' => 100.00, 'refunds' => 0.00],
        ['order_date' => '2024-01-02', 'total' => 200.00, 'refunds' => 0.00],
    ]);

    metric(RevenueWithFloatingFiltersMetric::class)
        ->usingFilterSets(fn (VisualizationData $data) => $data->addAndFilterSet(
            fn (FilterBuilder $b) => $b->equals('date_range', '2024-01-01')
        ))
        ->assertAggregateEquals(100.0)
        ->usingFilterSets(fn (VisualizationData $data) => $data->addAndFilterSet(
            fn (FilterBuilder $b) => $b->equals('date_range', '2024-01-02')
        ))
        ->assertAggregateEquals(200.0);
});
