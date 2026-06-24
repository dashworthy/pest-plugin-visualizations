<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations;

use Dashworthy\PestPluginVisualizations\Testers\ChartTester;
use Dashworthy\PestPluginVisualizations\Testers\DataGridTester;
use Dashworthy\PestPluginVisualizations\Testers\MetricTester;

function dataGrid(string $class): DataGridTester
{
    return new DataGridTester($class);
}

function chart(string $class): ChartTester
{
    return new ChartTester($class);
}

function metric(string $class): MetricTester
{
    return new MetricTester($class);
}
