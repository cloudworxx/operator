<?php

namespace Tests\Feature;

use App\Notifiable;
use App\Notifications\StateNotification;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\Messages\NexmoMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;
use SnoerenDevelopment\DiscordWebhook\DiscordMessage;
use SnoerenDevelopment\DiscordWebhook\DiscordWebhookChannel;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class HttpWebhookTest extends TestCase
{
    public function test_do_not_send_notifications_if_website_is_up_at_initial_check()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        Queue::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--webhook-url' => ['https://webhook1.test', 'https://webhook2.test'],
            '--webhook-secret' => ['secret1', 'secret2'],
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertNotSent(function (Request $request) {
            return in_array($request->url(), ['https://webhook1.test', 'https://webhook2.test']);
        });

        Queue::assertNothingPushed();
    }

    public function test_do_not_send_notifications_if_website_is_down_at_initial_check()
    {
        Http::fake([
            'google.test' => Http::response('Server Error', 500),
        ]);

        Queue::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--webhook-url' => ['https://webhook1.test', 'https://webhook2.test'],
            '--webhook-secret' => ['secret1', 'secret2'],
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertNotSent(function (Request $request) {
            return in_array($request->url(), ['https://webhook1.test', 'https://webhook2.test']);
        });

        Queue::assertNothingPushed();
    }

    public function test_webhooks_via_flags()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
            'webhook1.test' => Http::response('OK', 200),
            'webhook2.test' => Http::response('OK', 200),
        ]);

        Queue::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--webhook-url' => ['https://webhook1.test', 'https://webhook2.test'],
            '--webhook-secret' => ['secret1', 'secret2'],
            '--once' => true,
        ]);

        Http::assertSentInOrder([
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
        ]);

        Queue::assertPushed(CallWebhookJob::class, function ($job) {
            $this->assertEquals('post', $job->httpVerb);
            $this->assertEquals(1, $job->tries);
            $this->assertEquals('Opsiebot/1.0', $job->headers['User-Agent']);
            $this->assertEquals(200, $job->payload['status']);
            $this->assertEquals(true, $job->payload['up']);
            $this->assertNotNull($job->payload['time']);
            $this->assertNotNull($job->payload['instance_id']);
            $this->assertArrayHasKey('region', $job->payload['metadata']);
            $this->assertNotNull($job->payload['response_time_ms']);

            return in_array($job->webhookUrl, ['https://webhook1.test', 'https://webhook2.test']) &&
                $job->payload['url'] === 'https://google.test' &&
                in_array($job->headers['Signature'], [
                    hash_hmac('sha256', json_encode($job->payload), 'secret1'),
                    hash_hmac('sha256', json_encode($job->payload), 'secret2'),
                ]);
        });
    }

    public function test_webhooks_via_env()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
            'webhook1.test' => Http::response('OK', 200),
            'webhook2.test' => Http::response('OK', 200),
        ]);

        Queue::fake();

        $webhooks = json_encode([
            ['url' => 'https://webhook1.test', 'secret' => 'secret1'],
            ['url' => 'https://webhook2.test', 'secret' => 'secret2'],
        ]);

        putenv("HTTP_WEBHOOKS={$webhooks}");

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--once' => true,
        ]);

        Http::assertSentInOrder([
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
        ]);

        Queue::assertPushed(CallWebhookJob::class, function ($job) {
            $this->assertEquals('post', $job->httpVerb);
            $this->assertEquals(1, $job->tries);
            $this->assertEquals('Opsiebot/1.0', $job->headers['User-Agent']);
            $this->assertEquals(200, $job->payload['status']);
            $this->assertEquals(true, $job->payload['up']);
            $this->assertNotNull($job->payload['time']);
            $this->assertNotNull($job->payload['instance_id']);
            $this->assertArrayHasKey('region', $job->payload['metadata']);
            $this->assertNotNull($job->payload['response_time_ms']);

            return in_array($job->webhookUrl, ['https://webhook1.test', 'https://webhook2.test']) &&
                $job->payload['url'] === 'https://google.test' &&
                in_array($job->headers['Signature'], [
                    hash_hmac('sha256', json_encode($job->payload), 'secret1'),
                    hash_hmac('sha256', json_encode($job->payload), 'secret2'),
                ]);
        });
    }

    public function test_discord_webhooks_via_flags()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
            'discord.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--discord-webhook-url' => ['https://discord.test'],
            '--identifier' => 'test',
            '--once' => true,
        ]);

        Http::assertSentInOrder([
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
        ]);

        Notification::assertSentTo(new Notifiable(id: 'test'), StateNotification::class, function ($notification, $channels, $notifiable) {
            $this->assertEquals(200, $notification->payload['status']);
            $this->assertEquals(true, $notification->payload['up']);
            $this->assertNotNull($notification->payload['time']);
            $this->assertNotNull($notification->payload['instance_id']);
            $this->assertArrayHasKey('region', $notification->payload['metadata']);
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals([DiscordWebhookChannel::class], $channels);
            $this->assertEquals('https://discord.test', $notifiable->routeNotificationForDiscord());

            $this->assertInstanceOf(DiscordMessage::class, $notification->toDiscord($notifiable));

            return true;
        });
    }

    public function test_discord_webhooks_via_env()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
            'discord.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $webhooks = json_encode([
            ['url' => 'https://discord.test'],
        ]);

        putenv("DISCORD_WEBHOOKS={$webhooks}");

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--identifier' => 'test',
            '--once' => true,
        ]);

        Http::assertSentInOrder([
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
        ]);

        Notification::assertSentTo(new Notifiable(id: 'test'), StateNotification::class, function ($notification, $channels, $notifiable) {
            $this->assertEquals(200, $notification->payload['status']);
            $this->assertEquals(true, $notification->payload['up']);
            $this->assertNotNull($notification->payload['time']);
            $this->assertNotNull($notification->payload['instance_id']);
            $this->assertArrayHasKey('region', $notification->payload['metadata']);
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals([DiscordWebhookChannel::class], $channels);
            $this->assertEquals('https://discord.test', $notifiable->routeNotificationForDiscord());

            return true;
        });
    }

    public function test_slack_webhooks_via_flags()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
            'slack.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--slack-webhook-url' => ['https://slack.test'],
            '--slack-webhook-channel' => ['#test'],
            '--identifier' => 'test',
            '--once' => true,
        ]);

        Http::assertSentInOrder([
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
        ]);

        Notification::assertSentTo(new Notifiable(id: 'test'), StateNotification::class, function ($notification, $channels, $notifiable) {
            $this->assertEquals(200, $notification->payload['status']);
            $this->assertEquals(true, $notification->payload['up']);
            $this->assertNotNull($notification->payload['time']);
            $this->assertNotNull($notification->payload['instance_id']);
            $this->assertArrayHasKey('region', $notification->payload['metadata']);
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals(['slack'], $channels);
            $this->assertEquals('https://slack.test', $notifiable->routeNotificationForSlack($notification));

            $message = $notification->toSlack($notifiable);

            $this->assertInstanceOf(SlackMessage::class, $message);
            $this->assertEquals('#test', $message->channel);
            $this->assertEquals('Opsie Operator', $message->username);

            return true;
        });
    }

    public function test_slack_webhooks_via_env()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
            'slack.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $webhooks = json_encode([
            ['url' => 'https://slack.test', 'slack_channel' => '#test'],
        ]);

        putenv("SLACK_WEBHOOKS={$webhooks}");

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--identifier' => 'test',
            '--once' => true,
        ]);

        Http::assertSentInOrder([
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
        ]);

        Notification::assertSentTo(new Notifiable(id: 'test'), StateNotification::class, function ($notification, $channels, $notifiable) {
            $this->assertEquals(200, $notification->payload['status']);
            $this->assertEquals(true, $notification->payload['up']);
            $this->assertNotNull($notification->payload['time']);
            $this->assertNotNull($notification->payload['instance_id']);
            $this->assertArrayHasKey('region', $notification->payload['metadata']);
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals(['slack'], $channels);
            $this->assertEquals('https://slack.test', $notifiable->routeNotificationForSlack($notification));

            return true;
        });
    }

    public function test_nexmo_numbers_via_flags()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $numbers = json_encode([
            ['number' => '5555555555'],
        ]);

        putenv("NEXMO_NUMBERS={$numbers}");

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--identifier' => 'test',
            '--once' => true,
        ]);

        Http::assertSentInOrder([
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
        ]);

        Notification::assertSentTo(new Notifiable(id: 'test'), StateNotification::class, function ($notification, $channels, $notifiable) {
            $this->assertEquals(200, $notification->payload['status']);
            $this->assertEquals(true, $notification->payload['up']);
            $this->assertNotNull($notification->payload['time']);
            $this->assertNotNull($notification->payload['instance_id']);
            $this->assertArrayHasKey('region', $notification->payload['metadata']);
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals(['nexmo'], $channels);
            $this->assertEquals('5555555555', $notifiable->routeNotificationForNexmo($notification));

            $message = $notification->toNexmo($notifiable);

            $this->assertInstanceOf(NexmoMessage::class, $message);

            return true;
        });
    }

    public function test_nexmo_numbers_via_env()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--nexmo-sms-number' => ['5555555555'],
            '--identifier' => 'test',
            '--once' => true,
        ]);

        Http::assertSentInOrder([
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
        ]);

        Notification::assertSentTo(new Notifiable(id: 'test'), StateNotification::class, function ($notification, $channels, $notifiable) {
            $this->assertEquals(200, $notification->payload['status']);
            $this->assertEquals(true, $notification->payload['up']);
            $this->assertNotNull($notification->payload['time']);
            $this->assertNotNull($notification->payload['instance_id']);
            $this->assertArrayHasKey('region', $notification->payload['metadata']);
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals(['nexmo'], $channels);
            $this->assertEquals('5555555555', $notifiable->routeNotificationForNexmo($notification));

            return true;
        });
    }

    public function test_twilio_numbers_via_flags()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--twilio-sms-number' => ['5555555555'],
            '--identifier' => 'test',
            '--once' => true,
        ]);

        Http::assertSentInOrder([
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
        ]);

        Notification::assertSentTo(new Notifiable(id: 'test'), StateNotification::class, function ($notification, $channels, $notifiable) {
            $this->assertEquals(200, $notification->payload['status']);
            $this->assertEquals(true, $notification->payload['up']);
            $this->assertNotNull($notification->payload['time']);
            $this->assertNotNull($notification->payload['instance_id']);
            $this->assertArrayHasKey('region', $notification->payload['metadata']);
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals([TwilioChannel::class], $channels);
            $this->assertEquals('5555555555', $notifiable->routeNotificationForTwilio());

            $message = $notification->toTwilio($notifiable);

            $this->assertInstanceOf(TwilioSmsMessage::class, $message);

            return true;
        });
    }

    public function test_twilio_numbers_via_env()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $numbers = json_encode([
            ['number' => '5555555555'],
        ]);

        putenv("TWILIO_NUMBERS={$numbers}");

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--identifier' => 'test',
            '--once' => true,
        ]);

        Http::assertSentInOrder([
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
        ]);

        Notification::assertSentTo(new Notifiable(id: 'test'), StateNotification::class, function ($notification, $channels, $notifiable) {
            $this->assertEquals(200, $notification->payload['status']);
            $this->assertEquals(true, $notification->payload['up']);
            $this->assertNotNull($notification->payload['time']);
            $this->assertNotNull($notification->payload['instance_id']);
            $this->assertArrayHasKey('region', $notification->payload['metadata']);
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals([TwilioChannel::class], $channels);
            $this->assertEquals('5555555555', $notifiable->routeNotificationForTwilio());

            return true;
        });
    }

    public function test_fcm_tokens_via_flags()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--fcm-token' => ['5555555555'],
            '--identifier' => 'test',
            '--once' => true,
        ]);

        Http::assertSentInOrder([
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
        ]);

        Notification::assertSentTo(new Notifiable(id: 'test'), StateNotification::class, function ($notification, $channels, $notifiable) {
            $this->assertEquals(200, $notification->payload['status']);
            $this->assertEquals(true, $notification->payload['up']);
            $this->assertNotNull($notification->payload['time']);
            $this->assertNotNull($notification->payload['instance_id']);
            $this->assertArrayHasKey('region', $notification->payload['metadata']);
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals([FcmChannel::class], $channels);
            $this->assertEquals('5555555555', $notifiable->routeNotificationForFcm());

            $message = $notification->toFcm($notifiable);

            $this->assertInstanceOf(FcmMessage::class, $message);

            return true;
        });
    }

    public function test_fcm_tokens_via_env()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $tokens = json_encode([
            ['token' => '5555555555'],
        ]);

        putenv("FCM_TOKENS={$tokens}");

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--identifier' => 'test',
            '--once' => true,
        ]);

        Http::assertSentInOrder([
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
        ]);

        Notification::assertSentTo(new Notifiable(id: 'test'), StateNotification::class, function ($notification, $channels, $notifiable) {
            $this->assertEquals(200, $notification->payload['status']);
            $this->assertEquals(true, $notification->payload['up']);
            $this->assertNotNull($notification->payload['time']);
            $this->assertNotNull($notification->payload['instance_id']);
            $this->assertArrayHasKey('region', $notification->payload['metadata']);
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals([FcmChannel::class], $channels);
            $this->assertEquals('5555555555', $notifiable->routeNotificationForFcm());

            return true;
        });
    }
}
