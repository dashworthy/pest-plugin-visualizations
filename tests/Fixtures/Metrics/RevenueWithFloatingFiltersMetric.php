<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Tests\Fixtures\Metrics;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Dashworthy\Visualizations\FloatingFilters\DateRange;
use Dashworthy\Visualizations\Metrics\Abstracts\Metric;
use Dashworthy\Visualizations\Metrics\Value;

class RevenueWithFloatingFiltersMetric extends Metric
{
    public function getValue(): Value
    {
        return Value::make('sum(orders.total)', 'revenue')->header('Total Revenue');
    }

    public function getFloatingFilters(): Collection
    {
        return collect([
            DateRange::make('order_date', 'date_range')->header('Date Range'),
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('orders');
    }
}
