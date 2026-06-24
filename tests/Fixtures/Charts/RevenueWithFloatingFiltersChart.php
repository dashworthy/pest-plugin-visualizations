<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Tests\Fixtures\Charts;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Dashworthy\Visualizations\Charts\Abstracts\Chart;
use Dashworthy\Visualizations\Charts\Datasets\Bar;
use Dashworthy\Visualizations\Charts\Labels\Label;
use Dashworthy\Visualizations\FloatingFilters\DateRange;

class RevenueWithFloatingFiltersChart extends Chart
{
    public function getLabel(): Label
    {
        return Label::make('order_date', 'date');
    }

    public function getDatasets(): Collection
    {
        return collect([
            Bar::make('SUM(total)', 'revenue')->header('Revenue'),
        ]);
    }

    public function getFloatingFilters(): Collection
    {
        return collect([
            DateRange::make('order_date', 'date_range')->header('Date Range'),
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('orders')->groupBy('order_date');
    }
}
