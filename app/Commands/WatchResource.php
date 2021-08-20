<?php

namespace App\Commands;

use App\Concerns\ExposesPrometheusStats;
use App\Concerns\RunsHttpChecks;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class WatchResource extends Command
{
    use ExposesPrometheusStats;
    use RunsHttpChecks;

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
        {--headers= : JSON-formatted list of key-value strings to set as heaers.}
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
    ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Run the operator to watch a specific connection.';

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
     * Handle whenever a response succeeded.
     *
     * @return void
     */
    protected function responseSucceeded(): void
    {
        $this->markUptime();
    }

    /**
     * Handle whenever the response failed.
     *
     * @return void
     */
    protected function responseFailed(): void
    {
        $this->markDowntime();
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
