<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Monolog\Handler\NullHandler;
use Monolog\Handler\TestHandler;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Keep test runs from writing real log files or uploads.
        foreach (['security', 'integrations'] as $channel) {
            config(["logging.channels.{$channel}" => ['driver' => 'monolog', 'handler' => NullHandler::class]]);
        }
        Storage::fake('public');
    }

    /** Routes a log channel to an in-memory handler and returns it for assertions. */
    protected function captureLog(string $channel): TestHandler
    {
        config(["logging.channels.{$channel}" => ['driver' => 'monolog', 'handler' => TestHandler::class]]);
        Log::forgetChannel($channel);

        return Log::channel($channel)->getLogger()->getHandlers()[0];
    }
}
