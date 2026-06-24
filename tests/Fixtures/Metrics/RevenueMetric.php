<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Tests\Fixtures\Metrics;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Dashworthy\Visualizations\Metrics\Abstracts\Metric;
use Dashworthy\Visualizations\Metrics\Value;

class RevenueMetric extends Metric
{
    public function getValue(): Value
    {
        return Value::make('sum(orders.total)', 'revenue')->header('Total Revenue');
    }

    public function getQuery(): Builder
    {
        return DB::table('orders');
    }
}
