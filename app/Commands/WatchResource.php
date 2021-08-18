<?php

namespace App\Commands;

use App\Concerns\ExposesPrometheusStats;
use App\Concerns\RunsHttpChecks;
use App\Concerns\RunsSocketChecks;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Prometheus\RenderTextFormat;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

class WatchResource extends Command
{
    use ExposesPrometheusStats;
    use RunsHttpChecks;
    use RunsSocketChecks;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'watch:resource
        {--tcp-url= : The TCP url to call. Setting this will use the TCP URL method.}
        {--http-url= : The HTTP url to call. Setting this will use the HTTP request method.}
        {--tcp-port= : The port to call on TCP checking.}
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
        $this->initializeHttpServer();

        if ($this->option('http-url') ?: env('HTTP_URL')) {
            $this->line('Setting the HTTP checks protocol...');
            $this->runHttpChecks();
        } else if ($this->option('tcp-url') ?: env('TCP_URL')) {
            $this->line('Setting the TCP checks protocol...');
            $this->runTcpChecks();
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
     * Create a new HTTP server.
     *
     * @return void
     */
    protected function initializeHttpServer(): void
    {
        $http = new HttpServer(function (ServerRequestInterface $request) {
            return new Response(
                status: 200,
                headers: [
                    'Content-Type' => RenderTextFormat::MIME_TYPE,
                ],
                body: (new RenderTextFormat)->render($this->prometheus->getMetricFamilySamples()),
            );
        });

        $http->listen(
            $socket = new SocketServer('0.0.0.0:80')
        );
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
