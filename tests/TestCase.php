<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //

    protected function setUp(): void
    {
        putenv('OTEL_SDK_DISABLED=true');
        putenv('OTEL_PHP_DISABLED=true');
        putenv('OTEL_TRACES_EXPORTER=none');

        parent::setUp();
    }
}
