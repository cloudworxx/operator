<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Http;
use React\EventLoop\Loop;

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

        $loop = Loop::get();

        $loop->addPeriodicTimer($this->option('interval'), function () use ($loop) {
            $client = Http::asJson()
                ->accept($this->option('accept-header'))
                ->timeout($this->option('timeout'));

            if ($this->option('post-as-form')) {
                $client->asForm();
            }

            if ($body = $this->option('body') ?: env('HTTP_BODY')) {
                $client->withBody($body, 'application/json');
            }

            if ($headers = $this->option('headers') ?: env('HTTP_HEADERS')) {
                $client->withHeaders(json_decode($headers, true));
            }

            // Authentication
            if ($username = $this->option('username') ?: env('HTTP_USERNAME')) {
                $password = $this->option('password') ?: env('HTTP_PASSWORD');

                if ($this->option('digest-auth') ?: env('HTTP_DIGEST_AUTH')) {
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
            } else if ($token = $this->option('bearer-token') ?: env('HTTP_BEARER_TOKEN')) {
                $this->line(
                    string: 'Setting bearer token...',
                    verbosity: 'v',
                );

                $client->withToken($token);
            }

            $response = $client->{$this->option('method')}(
                $this->option('http-url') ?: env('HTTP_URL')
            );

            if ($response->failed()) {
                $this->error(
                    string: 'Response failed.',
                    verbosity: 'v',
                );

                $this->responseFailed();
            } else {
                $this->responseSucceeded();
            }

            if ($this->option('once')) {
                $loop->stop();
            }

            $this->line(
                string: 'Waiting between requests...',
                verbosity: 'v',
            );
        });
    }
}
