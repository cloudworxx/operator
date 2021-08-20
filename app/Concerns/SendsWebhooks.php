<?php

namespace App\Concerns;

use Spatie\WebhookServer\WebhookCall;

trait SendsWebhooks
{
    /**
     * Sending the payload to the webhooks
     *
     * @param  array  $payload
     * @return void
     */
    protected function sendWebhooks(array $payload): void
    {
        /** @var \App\Commands\WatchResource $this */

        foreach ($this->getWebhooksWithSecrets() as $webhook) {
            WebhookCall::create()
                ->url($webhook['url'])
                ->payload($payload)
                ->useSecret($webhook['secret'])
                ->maximumTries(1)
                ->timeoutInSeconds($this->getTimeout())
                ->withHeaders([
                    'User-Agent' => 'Opsiebot/1.0',
                ])
                ->dispatch();
        }
    }

    /**
     * Get the webhooks with their secrets.
     *
     * @return array
     */
    protected function getWebhooksWithSecrets(): array
    {
        if ($webhooks = env('WEBHOOKS')) {
            return json_decode($webhooks, true);
        }

        if ($webhookUrls = $this->option('webhook-url') && $webhookSecrets = $this->option('webhook-secret')) {
            return collect([$webhookUrls, $webhookSecrets])->mapSpread(function ($url, $secret) {
                return compact('url', 'secret');
            })->toArray();
        }

        return [];
    }
}
