<?php

declare(strict_types=1);

use Dashworthy\PestPluginVisualizations\Tests\Fixtures\DataGrids\HydratedUserDataGrid;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\AssertionFailedError;

use function Dashworthy\PestPluginVisualizations\dataGrid;

beforeEach(function () {
    DB::table('users')->insert([
        ['id' => 1, 'name' => 'Ada', 'email' => 'ada@example.com'],
        ['id' => 2, 'name' => 'Grace', 'email' => 'grace@example.com'],
    ]);
    DB::table('nicknames')->insert(['user_id' => 1, 'nickname' => 'Countess']);
});

/*
 | A hydrated column holds no value until the page is fetched, so the row
 | assertions must run the grid's hydration step after the statement, as the
 | data endpoint does.
 */
it('matches a row on its hydrated value', function () {
    dataGrid(HydratedUserDataGrid::class)
        ->assertRowMatches(['Name' => 'Ada', 'Nickname' => 'Countess']);
});

it('fills a row the hydrator has no value for with null', function () {
    dataGrid(HydratedUserDataGrid::class)
        ->assertRowMatches(['Name' => 'Grace', 'Nickname' => null]);
});

it('asserts a row with a hydrated value is missing', function () {
    dataGrid(HydratedUserDataGrid::class)
        ->assertRowMissing(['Name' => 'Grace', 'Nickname' => 'Countess']);
});

it('fails a row match on a wrong hydrated value', function () {
    $tester = dataGrid(HydratedUserDataGrid::class);

    expect(fn () => $tester->assertRowMatches(['Name' => 'Ada', 'Nickname' => 'Admiral']))
        ->toThrow(AssertionFailedError::class);
});

it('counts rows without hydrating', function () {
    dataGrid(HydratedUserDataGrid::class)->assertRowCount(2);
});

it('asserts a column is hydrated', function () {
    dataGrid(HydratedUserDataGrid::class)->assertColumnIsHydrated('Nickname');
});

it('fails the hydrated assertion for a column the statement selects', function () {
    $tester = dataGrid(HydratedUserDataGrid::class);

    expect(fn () => $tester->assertColumnIsHydrated('Name'))
        ->toThrow(AssertionFailedError::class);
});
