<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Tests\Fixtures\Hydrators;

use Dashworthy\Visualizations\Contracts\HydratorContract;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NicknameHydrator implements HydratorContract
{
    public function keyedBy(): string
    {
        return 'ID';
    }

    public function columnType(): ColumnType
    {
        return ColumnType::Text;
    }

    public function resolve(Collection $keys): array
    {
        return DB::table('nicknames')
            ->whereIn('user_id', $keys->all())
            ->pluck('nickname', 'user_id')
            ->all();
    }
}
