<?php

namespace App\Concerns;

use React\EventLoop\Loop;

trait RunsSocketChecks
{
    /**
     * Run the TCP checks for the current command.
     *
     * @return void
     */
    protected function runTcpChecks(): void
    {
        /** @var \App\Commands\WatchResource $this */

        $loop = Loop::get();

        $loop->addPeriodicTimer($this->option('interval'), function () use ($loop) {
            $fp = fsockopen(
                hostname: $this->option('tcp-url') ?: env('TCP_URL'),
                port: $this->option('tcp-port') ?: -1,
                timeout: $this->option('timeout'),
                error_message: $errorMessage = null,
            );

            if (! $fp) {
                $this->error(
                    string: 'Response failed.',
                    verbosity: 'v',
                );

                $this->responseFailed();
            } else {
                $this->responseSucceeded();
            }

            fclose($fp);

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
