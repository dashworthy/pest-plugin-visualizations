<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Tests\Fixtures\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\FloatingFilters\DateRange;

class UserDataGrid extends DataGrid
{
    public function getColumns(): Collection
    {
        return collect([
            Number::make('users.id', 'ID')->asRowKey(),
            Text::make('users.name', 'Name'),
            Text::make('users.email', 'Email'),
        ]);
    }

    public function getFloatingFilters(): Collection
    {
        return collect([
            DateRange::make('DATE(users.created_at)', 'Joined On'),
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('users');
    }
}
