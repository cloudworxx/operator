<?php

namespace Tests\Feature;

use App\Notifiable;
use App\Notifications\StateNotification;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Twilio\TwilioChannel;
use SnoerenDevelopment\DiscordWebhook\DiscordWebhookChannel;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class HttpTest extends TestCase
{
    public function test_http_get()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) {
            $this->assertEquals('https://google.test', $request->url());

            return true;
        });
    }

    public function test_http_post()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--method' => 'POST',
            '--body' => json_encode($body = [
                'name' => 'test',
                'some_value' => 1,
            ]),
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) use ($body) {
            $this->assertEquals('POST', $request->method());
            $this->assertEquals($body, $request->data());

            return true;
        });
    }

    public function test_http_post_as_form()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--method' => 'POST',
            '--body' => json_encode($body = [
                'name' => 'test',
                'some_value' => 1,
            ]),
            '--post-as-form' => true,
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) use ($body) {
            $this->assertEquals('POST', $request->method());
            $this->assertEquals($body, $request->data());

            return true;
        });
    }

    public function test_token_authentication()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--bearer-token' => 'testing',
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) {
            $this->assertEquals(
                'Bearer testing',
                $request->header('Authorization')[0],
            );

            return true;
        });
    }

    public function test_http_basic_authentication()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--username' => 'testing',
            '--password' => 'secret',
            '--digest-auth' => true,
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) {
            $this->assertEquals('https://google.test', $request->url());

            return true;
        });
    }

    public function test_headers()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--header' => [
                'X-Header-One=one',
                'X-Header-Two=two',
            ],
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) {
            $this->assertEquals('https://google.test', $request->url());
            $this->assertEquals(['one'], $request->header('X-Header-One'));
            $this->assertEquals(['two'], $request->header('X-Header-Two'));

            return true;
        });
    }

    public function test_webhooks()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
            'webhook1.test' => Http::response('OK', 200),
            'webhook2.test' => Http::response('OK', 200),
        ]);

        Queue::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
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
            $this->assertNotNull($job->payload['response_time_ms']);

            return in_array($job->webhookUrl, ['https://webhook1.test', 'https://webhook2.test']) &&
                $job->payload['url'] === 'https://google.test' &&
                in_array($job->headers['Signature'], [
                    hash_hmac('sha256', json_encode($job->payload), 'secret1'),
                    hash_hmac('sha256', json_encode($job->payload), 'secret2'),
                ]);
        });
    }

    public function test_discord_webhooks()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
            'discord.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
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
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals([DiscordWebhookChannel::class], $channels);
            $this->assertEquals('https://discord.test', $notifiable->discordWebhookUrl);

            return true;
        });
    }

    public function test_slack_webhooks()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
            'slack.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
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
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals(['slack'], $channels);
            $this->assertEquals('https://slack.test', $notifiable->slackWebhookUrl);

            return true;
        });
    }

    public function test_nexmo_numbers()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
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
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals(['nexmo'], $channels);
            $this->assertEquals('5555555555', $notifiable->nexmoNumber);

            return true;
        });
    }

    public function test_twilio_numbers()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
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
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals([TwilioChannel::class], $channels);
            $this->assertEquals('5555555555', $notifiable->twilioNumber);

            return true;
        });
    }

    public function test_fcm_tokens()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        Notification::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
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
            $this->assertNotNull($notification->payload['response_time_ms']);

            $this->assertEquals([FcmChannel::class], $channels);
            $this->assertEquals('5555555555', $notifiable->fcmToken);

            return true;
        });
    }

    public function test_do_not_send_notifications_if_website_is_up_at_initial_check()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        Queue::fake();

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
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
}
