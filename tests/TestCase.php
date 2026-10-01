<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Dashworthy\Visualizations\VisualizationsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_date')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('refunds', 10, 2)->default(0);
        });

        Schema::create('nicknames', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('nickname');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('nicknames');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            VisualizationsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        config()->set('database.default', 'testing');
    }
}
