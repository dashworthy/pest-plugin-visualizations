<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Dashworthy\PestPluginVisualizations\Tests\Fixtures\DataGrids\UserDataGrid;
use Dashworthy\Visualizations\Builders\FilterBuilder;
use Dashworthy\Visualizations\Data\VisualizationData;

use function Dashworthy\PestPluginVisualizations\dataGrid;

it('constructs successfully with a valid DataGrid class', function () {
    $tester = dataGrid(UserDataGrid::class);
    expect($tester)->toBeObject();
});

it('fails when the class does not exist', function () {
    expect(fn () => dataGrid('NonExistentClass'))
        ->toThrow(AssertionFailedError::class, 'Class [NonExistentClass] does not exist.');
});

it('fails when the class is not a DataGrid', function () {
    expect(fn () => dataGrid(stdClass::class))
        ->toThrow(AssertionFailedError::class, 'is not a DataGrid');
});

// --- Schema: columns ---

it('asserts a column exists by field name', function () {
    dataGrid(UserDataGrid::class)->assertHasColumn('Name');
});

it('asserts a column is missing by field name', function () {
    dataGrid(UserDataGrid::class)->assertMissingColumn('Password');
});

it('asserts the column count', function () {
    dataGrid(UserDataGrid::class)->assertColumnCount(3);
});

it('asserts a column is sortable', function () {
    dataGrid(UserDataGrid::class)->assertColumnIsSortable('Name');
});

it('asserts a column is not sortable', function () {
    $tester = dataGrid(UserDataGrid::class);
    expect(fn () => $tester->assertColumnIsNotSortable('Name'))
        ->toThrow(AssertionFailedError::class);
});

it('asserts a column is filterable', function () {
    dataGrid(UserDataGrid::class)->assertColumnIsFilterable('Name');
});

it('asserts a column is not filterable', function () {
    $tester = dataGrid(UserDataGrid::class);
    expect(fn () => $tester->assertColumnIsNotFilterable('Name'))
        ->toThrow(AssertionFailedError::class);
});

it('asserts a column is visible', function () {
    dataGrid(UserDataGrid::class)->assertColumnIsVisible('Name');
});

it('asserts a column is a row key', function () {
    dataGrid(UserDataGrid::class)->assertColumnIsRowKey('ID');
});

it('fails assertHasColumn when column is absent', function () {
    expect(fn () => dataGrid(UserDataGrid::class)->assertHasColumn('Password'))
        ->toThrow(AssertionFailedError::class, 'Column [Password] was not found');
});

it('fails assertMissingColumn when column exists', function () {
    expect(fn () => dataGrid(UserDataGrid::class)->assertMissingColumn('Name'))
        ->toThrow(AssertionFailedError::class);
});

it('fails assertColumnCount when count is wrong', function () {
    expect(fn () => dataGrid(UserDataGrid::class)->assertColumnCount(99))
        ->toThrow(AssertionFailedError::class);
});

// --- Schema: floating filters ---

it('asserts a floating filter exists by field name', function () {
    dataGrid(UserDataGrid::class)->assertHasFloatingFilter('Joined On');
});

it('asserts a floating filter is missing', function () {
    dataGrid(UserDataGrid::class)->assertMissingFloatingFilter('Updated At');
});

it('fails assertHasFloatingFilter when filter is absent', function () {
    expect(fn () => dataGrid(UserDataGrid::class)->assertHasFloatingFilter('Updated At'))
        ->toThrow(AssertionFailedError::class, 'Floating filter [Updated At] was not found');
});

// --- Chaining ---

it('schema assertions are chainable', function () {
    dataGrid(UserDataGrid::class)
        ->assertColumnCount(3)
        ->assertHasColumn('ID')
        ->assertHasColumn('Name')
        ->assertHasColumn('Email')
        ->assertMissingColumn('Password')
        ->assertColumnIsRowKey('ID')
        ->assertColumnIsSortable('Name')
        ->assertColumnIsFilterable('Email')
        ->assertColumnIsVisible('Name')
        ->assertHasFloatingFilter('Joined On')
        ->assertMissingFloatingFilter('Updated At');
});

// --- Data assertions ---

it('asserts row count with no filters returns all rows', function () {
    DB::table('users')->insert([
        ['name' => 'Andrew', 'email' => 'andrew@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'John', 'email' => 'john@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    dataGrid(UserDataGrid::class)->assertRowCount(2);
});

it('asserts row count with filter applied', function () {
    DB::table('users')->insert([
        ['name' => 'Andrew', 'email' => 'andrew@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'John', 'email' => 'john@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    dataGrid(UserDataGrid::class)
        ->usingFilterSets(fn (VisualizationData $data) => $data->addAndFilterSet(
            fn (FilterBuilder $b) => $b->contains('Name', 'Andrew')
        ))
        ->assertRowCount(1);
});

it('fails assertRowCount when count is wrong', function () {
    DB::table('users')->insert([
        ['name' => 'Andrew', 'email' => 'andrew@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    expect(fn () => dataGrid(UserDataGrid::class)->assertRowCount(99))
        ->toThrow(AssertionFailedError::class);
});

it('asserts no results when table is empty', function () {
    dataGrid(UserDataGrid::class)->assertNoResults();
});

it('asserts no results when filter matches nothing', function () {
    DB::table('users')->insert([
        ['name' => 'Andrew', 'email' => 'andrew@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    dataGrid(UserDataGrid::class)
        ->usingFilterSets(fn (VisualizationData $data) => $data->addAndFilterSet(
            fn (FilterBuilder $b) => $b->equals('Name', 'Nobody')
        ))
        ->assertNoResults();
});

it('fails assertNoResults when rows exist', function () {
    DB::table('users')->insert([
        ['name' => 'Andrew', 'email' => 'andrew@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    expect(fn () => dataGrid(UserDataGrid::class)->assertNoResults())
        ->toThrow(AssertionFailedError::class);
});

it('asserts a matching row exists', function () {
    DB::table('users')->insert([
        ['name' => 'Andrew', 'email' => 'andrew@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'John', 'email' => 'john@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    dataGrid(UserDataGrid::class)
        ->usingFilterSets(fn (VisualizationData $data) => $data->addAndFilterSet(
            fn (FilterBuilder $b) => $b->contains('Name', 'Andrew')
        ))
        ->assertRowMatches(['Name' => 'Andrew']);
});

it('fails assertRowMatches when no row matches', function () {
    DB::table('users')->insert([
        ['name' => 'Andrew', 'email' => 'andrew@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    expect(fn () => dataGrid(UserDataGrid::class)->assertRowMatches(['Name' => 'Nobody']))
        ->toThrow(AssertionFailedError::class, 'No row matching');
});

it('asserts a row is missing', function () {
    DB::table('users')->insert([
        ['name' => 'Andrew', 'email' => 'andrew@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    dataGrid(UserDataGrid::class)->assertRowMissing(['Name' => 'Nobody']);
});

it('fails assertRowMissing when a matching row exists', function () {
    DB::table('users')->insert([
        ['name' => 'Andrew', 'email' => 'andrew@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    expect(fn () => dataGrid(UserDataGrid::class)->assertRowMissing(['Name' => 'Andrew']))
        ->toThrow(AssertionFailedError::class, 'was found but should not exist');
});

it('switches filter context mid-chain', function () {
    DB::table('users')->insert([
        ['name' => 'Andrew', 'email' => 'andrew@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'John', 'email' => 'john@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    dataGrid(UserDataGrid::class)
        ->usingFilterSets(fn (VisualizationData $data) => $data->addAndFilterSet(
            fn (FilterBuilder $b) => $b->contains('Name', 'Andrew')
        ))
        ->assertRowCount(1)
        ->assertRowMatches(['Name' => 'Andrew'])
        ->usingFilterSets(fn (VisualizationData $data) => $data->addAndFilterSet(
            fn (FilterBuilder $b) => $b->contains('Name', 'John')
        ))
        ->assertRowCount(1)
        ->assertRowMatches(['Name' => 'John']);
});

it('applies sorts via usingSorts', function () {
    DB::table('users')->insert([
        ['name' => 'Zara', 'email' => 'zara@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Andrew', 'email' => 'andrew@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    dataGrid(UserDataGrid::class)
        ->usingSorts(fn (VisualizationData $data) => $data->addSortAsc('Name'))
        ->assertRowMatches(['Name' => 'Andrew']);
});
