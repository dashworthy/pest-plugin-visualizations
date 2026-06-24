<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\AssertionFailedError;
use Dashworthy\PestPluginVisualizations\Tests\Fixtures\Charts\NullLabelChart;
use Dashworthy\PestPluginVisualizations\Tests\Fixtures\Charts\RevenueChart;
use Dashworthy\PestPluginVisualizations\Tests\Fixtures\Charts\RevenueWithFloatingFiltersChart;
use Dashworthy\Visualizations\Builders\FilterBuilder;
use Dashworthy\Visualizations\Data\VisualizationData;

use function Dashworthy\PestPluginVisualizations\chart;

// --- Construction ---

it('constructs successfully with a valid Chart class', function () {
    expect(chart(RevenueChart::class))->toBeObject();
});

it('fails when the class does not exist', function () {
    expect(fn () => chart('NonExistentClass'))
        ->toThrow(AssertionFailedError::class, 'Class [NonExistentClass] does not exist.');
});

it('fails when the class is not a Chart', function () {
    expect(fn () => chart(stdClass::class))
        ->toThrow(AssertionFailedError::class, 'is not a Chart');
});

// --- Schema: label ---

it('asserts the label field', function () {
    chart(RevenueChart::class)->assertHasLabel('date');
});

it('asserts no label for NullLabel chart', function () {
    chart(NullLabelChart::class)->assertHasNoLabel();
});

it('fails assertHasLabel when field does not match', function () {
    expect(fn () => chart(RevenueChart::class)->assertHasLabel('wrong'))
        ->toThrow(AssertionFailedError::class, 'Label [wrong] was not found');
});

it('fails assertHasLabel when chart uses NullLabel', function () {
    expect(fn () => chart(NullLabelChart::class)->assertHasLabel('date'))
        ->toThrow(AssertionFailedError::class, 'Chart has no label');
});

it('fails assertHasNoLabel when chart has a label', function () {
    expect(fn () => chart(RevenueChart::class)->assertHasNoLabel())
        ->toThrow(AssertionFailedError::class);
});

// --- Schema: datasets ---

it('asserts a dataset exists by field', function () {
    chart(RevenueChart::class)->assertHasDataset('revenue');
});

it('asserts a dataset is missing', function () {
    chart(RevenueChart::class)->assertMissingDataset('costs');
});

it('asserts the dataset count', function () {
    chart(RevenueChart::class)->assertDatasetCount(2);
});

it('fails assertHasDataset when dataset is absent', function () {
    expect(fn () => chart(RevenueChart::class)->assertHasDataset('costs'))
        ->toThrow(AssertionFailedError::class, 'Dataset [costs] was not found');
});

it('fails assertMissingDataset when dataset exists', function () {
    expect(fn () => chart(RevenueChart::class)->assertMissingDataset('revenue'))
        ->toThrow(AssertionFailedError::class);
});

// --- Schema: floating filters ---

it('asserts a floating filter exists', function () {
    chart(RevenueWithFloatingFiltersChart::class)->assertHasFloatingFilter('date_range');
});

it('asserts a floating filter is missing', function () {
    chart(RevenueChart::class)->assertMissingFloatingFilter('date_range');
});

it('fails assertHasFloatingFilter when filter is absent', function () {
    expect(fn () => chart(RevenueChart::class)->assertHasFloatingFilter('date_range'))
        ->toThrow(AssertionFailedError::class, 'Floating filter [date_range] was not found');
});

// --- Schema: chaining ---

it('schema assertions are chainable', function () {
    chart(RevenueChart::class)
        ->assertHasLabel('date')
        ->assertHasDataset('revenue')
        ->assertHasDataset('refunds')
        ->assertDatasetCount(2)
        ->assertMissingDataset('costs')
        ->assertMissingFloatingFilter('date_range');
});

// --- Data assertions ---

it('asserts result count', function () {
    DB::table('orders')->insert([
        ['order_date' => '2024-01-01', 'total' => 100.00, 'refunds' => 0.00],
        ['order_date' => '2024-01-02', 'total' => 200.00, 'refunds' => 10.00],
    ]);

    chart(RevenueChart::class)->assertResultCount(2);
});

it('asserts result count with filter', function () {
    DB::table('orders')->insert([
        ['order_date' => '2024-01-01', 'total' => 100.00, 'refunds' => 0.00],
        ['order_date' => '2024-01-02', 'total' => 200.00, 'refunds' => 10.00],
    ]);

    chart(RevenueChart::class)
        ->usingFilterSets(fn (VisualizationData $data) => $data->addAndFilterSet(
            fn (FilterBuilder $b) => $b->equals('date', '2024-01-01')
        ))
        ->assertResultCount(1);
});

it('asserts no results', function () {
    chart(RevenueChart::class)->assertNoResults();
});

it('asserts a result row contains expected data', function () {
    DB::table('orders')->insert([
        ['order_date' => '2024-01-01', 'total' => 100.00, 'refunds' => 0.00],
    ]);

    chart(RevenueChart::class)
        ->assertResultContains(['date' => '2024-01-01']);
});

it('asserts a result row is missing', function () {
    DB::table('orders')->insert([
        ['order_date' => '2024-01-01', 'total' => 100.00, 'refunds' => 0.00],
    ]);

    chart(RevenueChart::class)
        ->assertResultMissing(['date' => '2099-01-01']);
});

it('fails assertResultContains when no row matches', function () {
    DB::table('orders')->insert([
        ['order_date' => '2024-01-01', 'total' => 100.00, 'refunds' => 0.00],
    ]);

    expect(fn () => chart(RevenueChart::class)->assertResultContains(['date' => '9999-01-01']))
        ->toThrow(AssertionFailedError::class, 'No result matching');
});

it('switches filter context mid-chain', function () {
    DB::table('orders')->insert([
        ['order_date' => '2024-01-01', 'total' => 100.00, 'refunds' => 0.00],
        ['order_date' => '2024-01-02', 'total' => 200.00, 'refunds' => 10.00],
    ]);

    chart(RevenueChart::class)
        ->usingFilterSets(fn (VisualizationData $data) => $data->addAndFilterSet(
            fn (FilterBuilder $b) => $b->equals('date', '2024-01-01')
        ))
        ->assertResultCount(1)
        ->assertResultContains(['date' => '2024-01-01'])
        ->usingFilterSets(fn (VisualizationData $data) => $data->addAndFilterSet(
            fn (FilterBuilder $b) => $b->equals('date', '2024-01-02')
        ))
        ->assertResultCount(1)
        ->assertResultContains(['date' => '2024-01-02']);
});
