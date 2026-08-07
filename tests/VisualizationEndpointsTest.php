<?php

declare(strict_types=1);

use Dashworthy\PestPluginVisualizations\Tests\Fixtures\DataGrids\UserDataGrid;
use Dashworthy\PestPluginVisualizations\Tests\Fixtures\Middleware\RefusesWithStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\ExpectationFailedException;

use function Dashworthy\PestPluginVisualizations\dataGrid;

/**
 * Register the fixture grid's endpoints.
 *
 * refreshNameLookups() is required: the router builds its name lookup table
 * once during boot, so a route registered inside a test is unreachable by
 * route() without it.
 */
function registerUserDataGridRoutes(?string $middleware = null): void
{
    $register = fn () => Route::dataGrid(UserDataGrid::class);

    $middleware === null
        ? $register()
        : Route::middleware($middleware)->group($register);

    Route::getRoutes()->refreshNameLookups();
}

beforeEach(function (): void {
    DB::table('users')->insert([
        ['name' => 'Ada Lovelace', 'email' => 'ada@example.test', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Grace Hopper', 'email' => 'grace@example.test', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

it('derives both endpoint names from the visualization rather than being told them', function (): void {
    $tester = dataGrid(UserDataGrid::class);

    expect($tester->schemaRouteName())->toBe('grids.users.schema')
        ->and($tester->dataRouteName())->toBe('grids.users.data');
});

it('asserts the schema endpoint answers for this visualization', function (): void {
    registerUserDataGridRoutes();

    dataGrid(UserDataGrid::class)->assertSchemaServed();
});

it('asserts the data endpoint answers with a paginated payload', function (): void {
    registerUserDataGridRoutes();

    dataGrid(UserDataGrid::class)->assertDataServed();
});

it('asserts both endpoints in one call', function (): void {
    registerUserDataGridRoutes();

    dataGrid(UserDataGrid::class)->assertEndpointsServed();
});

it('asserts both endpoints are forbidden when the route stack refuses', function (): void {
    registerUserDataGridRoutes(RefusesWithStatus::class.':403');

    dataGrid(UserDataGrid::class)->assertEndpointsForbidden();
});

/*
 | The guest assertion is "not 200" rather than a redirect to a named login,
 | because where an unauthenticated caller is sent belongs to the application.
 | A route stack that refuses satisfies it however it refuses.
 */
it('asserts both endpoints refuse a guest', function (): void {
    registerUserDataGridRoutes(RefusesWithStatus::class.':401');

    dataGrid(UserDataGrid::class)->assertEndpointsRefuseGuests();
});

it('fails the permission assertion for a visualization that declares none', function (): void {
    expect(fn () => dataGrid(UserDataGrid::class)->assertDeclaresPermission())
        ->toThrow(ExpectationFailedException::class);
});
