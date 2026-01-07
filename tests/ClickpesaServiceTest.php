<?php

namespace Dawilly\Dawilly\Tests;

use Orchestra\Testbench\TestCase;
use Dawilly\Dawilly\ClickpesaServiceProvider;

class ClickpesaServiceTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ClickpesaServiceProvider::class];
    }

    public function test_service_can_be_instantiated()
    {
        $service = app('clickpesa');
        $this->assertInstanceOf(\Dawilly\Dawilly\Services\ClickpesaService::class, $service);
    }
}
