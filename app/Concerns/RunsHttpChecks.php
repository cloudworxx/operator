<?php

namespace App\Concerns;

use Exception;
use Illuminate\Support\Facades\Http;

trait RunsHttpChecks
{
    /**
     * Run the HTTP checks for the current command.
     *
     * @return void
     */
    protected function runHttpChecks(): void
    {
        /** @var \App\Commands\WatchResource $this */
        $this->line('The operator started.');

        while (true) {
            $client = $this->option('post-as-form')
                ? Http::asForm()
                : Http::asJson();

            $client->accept($this->option('accept-header'))
                ->timeout($this->getTimeout());

            if ($this->option('header')) {
                $client->withHeaders(
                    $this->parseOptionAsKeyValue('header'),
                );
            }

            // Authentication
            if ($username = $this->option('username') ?: env('HTTP_USERNAME')) {
                $password = $this->option('password') ?: env('HTTP_PASSWORD');

                if ($this->option('digest-auth')) {
                    $this->line(
                        string: 'Setting digest auth...',
                        verbosity: 'v',
                    );

                    $client->withDigestAuth($username, $password);
                } else {
                    $this->line(
                        string: 'Setting basic auth...',
                        verbosity: 'v',
                    );

                    $client->withBasicAuth($username, $password);
                }
            } elseif ($token = $this->option('bearer-token') ?: env('HTTP_BEARER_TOKEN')) {
                $this->line(
                    string: 'Setting bearer token...',
                    verbosity: 'v',
                );

                $client->withToken($token);
            }

            $url = $this->option('http-url') ?: env('HTTP_URL');

            $payload = [
                'url' => $url,
                'time' => now()->toIso8601String(),
                'instance_id' => $this->getIdentifier(),
                'metadata' => $this->getMetadata(),
                'responseTime' => $responseTime = 0,
            ];

            /** @var \Illuminate\Http\Client\Response $response */
            try {
                $response = $client->{$this->option('method')}(
                    $url,
                    json_decode($this->option('body') ?: env('HTTP_BODY'), true),
                );

                $responseTime = ($response->transferStats->getTransferTime() ?: 0) * 1000;

                $payload = array_merge($payload, [
                    'status' => $response->status(),
                    'up' => $response->successful(),
                    'headers' => $response->headers(),
                    'response_time_ms' => $responseTime,
                ]);
            } catch (Exception $e) {
                $payload = array_merge($payload, [
                    'status' => 0,
                    'up' => false,
                    'headers' => [],
                ]);
            }

            if ($payload['up']) {
                $this->markUptime($payload, $responseTime);
            } else {
                $this->markDowntime($payload, $responseTime);
            }

            if ($this->option('once')) {
                break;
            }

            $this->line(
                string: 'Waiting between requests...',
                verbosity: 'v',
            );

            sleep($this->option('interval'));
        }
    }
}
