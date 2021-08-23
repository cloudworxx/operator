<?php

namespace App\Commands;

use App\Concerns\ExposesPrometheusStats;
use App\Concerns\RunsHttpChecks;
use App\Concerns\SendsWebhooks;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class WatchResource extends Command
{
    use ExposesPrometheusStats;
    use RunsHttpChecks;
    use SendsWebhooks;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'watch:resource
        {--http-url= : The HTTP url to call. Setting this will use the HTTP request method.}
        {--method=GET : The method for the HTTP call.}
        {--body= : JSON-formatted string with the body to call.}
        {--post-as-form : Send the request as form, with the application/x-www-form-urlencoded header.}
        {--header=* : Array list of key-value strings to set as headers.}
        {--accept-header=application/json : The Accept header value.}
        {--timeout=10 : The timeout of the request, in seconds.}
        {--interval=10 : The interval between checks, in seconds.}
        {--username= : The HTTP basic auth username. Enabling this will overwrite the --token value.}
        {--password= : The HTTP basic auth password. }
        {--digest-auth : Wether to use digest auth instead of plain auth.}
        {--bearer-token= : The Bearer token to authorize the request. Gets overwritten if --username is set.}
        {--once : Perform only one check, without monitoring the resource.}
        {--prometheus-identifier= : The identifier for Prometheus exports.}
        {--prometheus-label=* : Array list of value strings to set as Prometheus labels.}
        {--pushgateway-url= : The URL for Pushgateway metrics collection.}
        {--webhook-url=* : Array list of webhook URLs.}
        {--webhook-secret=* : Array list of secrets to sign the webhook URLs with.}
        {--identifier= : An unique identifier for the current running process.}
    ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Run the operator to watch a specific connection.';

    /**
     * Wether the service is down, to avoid duplicate webhooks.
     *
     * @var bool
     */
    protected bool $isDown = false;

    /**
     * Whether the initial check was done.
     *
     * @var bool
     */
    protected $initialCheck = false;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('http-url') ?: env('HTTP_URL')) {
            $this->line('Setting the HTTP checks protocol...');
            $this->runHttpChecks();
        }
    }

    /**
     * Mark the status as up.
     *
     * @param  array  $payload
     * @return void
     */
    protected function markUptime(array $payload)
    {
        if ($this->initialCheck && ! $this->isDown) {
            return $this->line(
                string: 'Website is up, but the notifications were already sent.',
                verbosity: 'v',
            );
        }

        $this->initialCheck = true;

        $this->line(
            string: 'Website is up.',
            verbosity: 'v',
        );

        $this->line(
            string: "[{$payload['time']}] HTTP Status: {$payload['status']}",
            verbosity: 'v',
        );

        $this->getPrometheusGauge()->set(1, $this->getPrometheusLabelsWithValues());
        $this->pingPushgateway();
        $this->sendWebhooks($payload);
    }

    /**
     * Mark the status as down.
     *
     * @param  array  $payload
     * @return void
     */
    protected function markDowntime(array $payload)
    {
        if ($this->initialCheck && $this->isDown) {
            return $this->line(
                string: 'Website is down, but the notifications were already sent.',
                verbosity: 'v',
            );
        }

        $this->initialCheck = true;

        $this->error(
            string: 'Website is down.',
            verbosity: 'v',
        );

        $this->error(
            string: "[{$payload['time']}] HTTP Status: {$payload['status']}",
            verbosity: 'v',
        );

        $this->getPrometheusGauge()->set(0, $this->getPrometheusLabelsWithValues());
        $this->pingPushgateway();
        $this->sendWebhooks($payload);
    }

    /**
     * Get the current CLI identifier.
     *
     * @return string
     */
    protected function getIdentifier(): string
    {
        if ($id = $this->option('identifier')) {
            return $id;
        }

        if ($id = env('IDENTIFIER')) {
            return $id;
        }

        return Str::uuid();
    }

    /**
     * Make sure the timeout does not exceeds the interval.
     *
     * @return float
     */
    protected function getTimeout(): float
    {
        $timeout = (float) $this->option('timeout');
        $interval = (float) $this->option('interval');

        return $timeout > $interval ? $interval : $timeout;
    }

    /**
     * Transform key=value pairs from the array option
     * into key-value array.
     *
     * @param  string  $option
     * @return array
     */
    protected function parseOptionAsKeyValue(string $option): array
    {
        return collect($this->option($option))->mapWithKeys(function ($pair) {
            [$key, $value] = explode('=', $pair);

            return [$key => $value];
        })->toArray();
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
