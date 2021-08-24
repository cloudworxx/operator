<?php

namespace App\Concerns;

use App\Notifiable;
use App\Notifications\StateNotification;

trait SendsNotifications
{
    /**
     * Send notifications to Discord with the given payload.
     *
     * @param  array  $payload
     * @return void
     */
    public function sendNotifications(array $payload)
    {
        /** @var \App\Commands\WatchResource $this */
        $webhooks = collect()
            ->merge($this->getDiscordWebhooks())
            ->merge($this->getSlackWebhooks())
            ->merge($this->getNexmoNumbers())
            ->merge($this->getTwilioNumbers())
            ->merge($this->getFcmTokens())
            ->toArray();

        foreach ($webhooks as $webhook) {
            $notifiable = new Notifiable(
                discordWebhookUrl: $webhook['channel'] === 'discord' ? $webhook['url'] : null,
                slackWebhookUrl: $webhook['channel'] === 'slack' ? $webhook['url'] : null,
                nexmoNumber: $webhook['channel'] === 'nexmo' ? $webhook['number'] : null,
                twilioNumber: $webhook['channel'] === 'twilio' ? $webhook['number'] : null,
                fcmToken: $webhook['channel'] === 'fcm' ? $webhook['token'] : null,
                id: $this->getIdentifier(),
            );

            $notifiable->notify(
                new StateNotification($payload, $webhook),
            );

            $this->line(
                string: "[{$payload['time']}] Sent notification via {$webhook['channel']}.",
                verbosity: 'v',
            );
        }
    }

    /**
     * Get the list of Discord webhooks with their url.
     *
     * @return array
     */
    protected function getDiscordWebhooks(): array
    {
        /** @var \App\Commands\WatchResource $this */
        if ($webhooks = env('DISCORD_WEBHOOKS')) {
            return collect(json_decode($webhooks, true))->map(function ($webhook) {
                return array_merge($webhook, ['channel' => 'discord']);
            })->toArray();
        }

        return collect($this->option('discord-webhook-url'))->map(function ($url) {
            return [
                'channel' => 'discord',
                'url' => $url,
            ];
        })->toArray();
    }

    /**
     * Get the list of Slack webhooks with their url.
     *
     * @return array
     */
    protected function getSlackWebhooks(): array
    {
        /** @var \App\Commands\WatchResource $this */
        if ($webhooks = env('SLACK_WEBHOOKS')) {
            return collect(json_decode($webhooks, true))->map(function ($webhook) {
                return array_merge($webhook, ['channel' => 'slack']);
            })->toArray();
        }

        if (
            ($webhookUrls = $this->option('slack-webhook-url')) &&
            ($webhookChannels = $this->option('slack-webhook-channel'))
        ) {
            return collect($webhookUrls)
                ->zip($webhookChannels)
                ->mapSpread(function ($url, $slackChannel) {
                    return [
                        'url' => $url,
                        'channel' => 'slack',
                        'slack_channel' => $slackChannel,
                    ];
                })
                ->toArray();
        }

        return [];
    }

    /**
     * Get the list of Nexmo numbers.
     *
     * @return array
     */
    protected function getNexmoNumbers(): array
    {
        /** @var \App\Commands\WatchResource $this */
        if ($numbers = env('NEXMO_NUMBERS')) {
            return collect(json_decode($numbers, true))->map(function ($webhook) {
                return array_merge($webhook, ['channel' => 'nexmo']);
            })->toArray();
        }

        return collect($this->option('nexmo-sms-number'))->map(function ($number) {
            return [
                'channel' => 'nexmo',
                'number' => $number,
            ];
        })->toArray();
    }

    /**
     * Get the list of Twilio numbers.
     *
     * @return array
     */
    protected function getTwilioNumbers(): array
    {
        /** @var \App\Commands\WatchResource $this */
        if ($numbers = env('TWILIO_NUMBERS')) {
            return collect(json_decode($numbers, true))->map(function ($webhook) {
                return array_merge($webhook, ['channel' => 'twilio']);
            })->toArray();
        }

        return collect($this->option('twilio-sms-number'))->map(function ($number) {
            return [
                'channel' => 'twilio',
                'number' => $number,
            ];
        })->toArray();
    }

    /**
     * Get the list of FCM tokens.
     *
     * @return array
     */
    protected function getFcmTokens(): array
    {
        /** @var \App\Commands\WatchResource $this */
        if ($numbers = env('FCM_TOKENS')) {
            return collect(json_decode($numbers, true))->map(function ($webhook) {
                return array_merge($webhook, ['channel' => 'fcm']);
            })->toArray();
        }

        return collect($this->option('fcm-token'))->map(function ($token) {
            return [
                'channel' => 'fcm',
                'token' => $token,
            ];
        })->toArray();
    }
}
