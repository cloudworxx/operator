<?php

namespace Tests;

use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        putenv('HTTP_WEBHOOKS');
        putenv('DISCORD_WEBHOOKS');
        putenv('SLACK_WEBHOOKS');
        putenv('NEXMO_NUMBERS');
        putenv('TWILIO_NUMBERS');
        putenv('FCM_TOKENS');
    }
}
