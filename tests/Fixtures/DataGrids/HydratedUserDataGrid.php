<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Tests\Fixtures\DataGrids;

use Dashworthy\PestPluginVisualizations\Tests\Fixtures\Hydrators\NicknameHydrator;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HydratedUserDataGrid extends DataGrid
{
    public function getColumns(): Collection
    {
        return collect([
            Number::make('users.id', 'ID')->asRowKey(),
            Text::make('users.name', 'Name'),
            Text::make(NicknameHydrator::class, 'Nickname'),
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('users');
    }
}
