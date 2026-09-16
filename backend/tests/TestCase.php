<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Monolog\Handler\NullHandler;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Keep test runs from writing real security log files.
        config(['logging.channels.security' => ['driver' => 'monolog', 'handler' => NullHandler::class]]);
    }
}
