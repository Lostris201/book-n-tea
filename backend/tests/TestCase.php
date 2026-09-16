<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;
use Monolog\Handler\NullHandler;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Keep test runs from writing real security log files or uploads.
        config(['logging.channels.security' => ['driver' => 'monolog', 'handler' => NullHandler::class]]);
        Storage::fake('public');
    }
}
