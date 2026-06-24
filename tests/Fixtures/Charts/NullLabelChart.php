<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Tests\Fixtures\Charts;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Dashworthy\Visualizations\Charts\Abstracts\Chart;
use Dashworthy\Visualizations\Charts\Datasets\Pie;
use Dashworthy\Visualizations\Charts\Labels\Label;
use Dashworthy\Visualizations\Charts\Labels\NullLabel;

class NullLabelChart extends Chart
{
    public function getLabel(): Label
    {
        return NullLabel::create();
    }

    public function getDatasets(): Collection
    {
        return collect([
            Pie::make('SUM(total)', 'revenue')->header('Revenue'),
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('orders');
    }
}
