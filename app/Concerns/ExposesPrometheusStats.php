<?php

namespace App\Concerns;

use App\Prometheus\Pushgateway;
use Illuminate\Support\Str;
use Prometheus\CollectorRegistry;
use Prometheus\Gauge;
use Prometheus\Storage\InMemory;

trait ExposesPrometheusStats
{
    /**
     * The Prometheus instance.
     *
     * @var \Prometheus\CollectorRegistry
     */
    protected $prometheus;

    /**
     * The Pushgateway instance.
     *
     * @var \App\Prometheus\Pushgateway
     */
    protected $pushgateway;

    /**
     * Initialize Prometheus and get the registry.
     *
     * @return \Prometheus\CollectorRegistry
     */
    protected function getPrometheus(): CollectorRegistry
    {
        if (! $this->prometheus) {
            $this->prometheus = new CollectorRegistry(new InMemory);
        }

        return $this->prometheus;
    }

    /**
     * Get the Prometheus gauge for the uptime.
     *
     * @return \Prometheus\Gauge
     */
    protected function getPrometheusGauge(): Gauge
    {
        return $this->getPrometheus()->getOrRegisterGauge(
            namespace: $this->getPrometheusNamespace(),
            name: 'uptime',
            help: 'The service uptime, either 1 or 0.',
            labels: $this->getPrometheusLabels(),
        );
    }

    /**
     * Ping the Pushgateway metrics.
     *
     * @return void
     */
    protected function pingPushgateway(): void
    {
        /** @var \App\Commands\WatchResource $this */
        if (! $url = $this->option('pushgateway-url')) {
            return;
        }

        $this->getPushgatewayClient($url)->push(
            $this->getPrometheus(),
            $this->getPrometheusNamespace(),
            $this->getPrometheusLabelsWithValues(),
        );
    }

    /**
     * Get the Pushgateway client.
     *
     * @param  string  $url
     * @return \App\Prometheus\Pushgateway
     */
    protected function getPushgatewayClient(string $url)
    {
        if (! $this->pushgateway) {
            $this->pushgateway = new PushGateway($url);
        }

        return $this->pushgateway;
    }

    /**
     * Get the Prometheus namespace for this CLI.
     *
     * @return string
     */
    protected function getPrometheusNamespace(): string
    {
        $namespace = $this->option('prometheus-identifier');

        if (! $namespace) {
            $namespace = Str::slug($this->option('http-url'), '_');
        }

        return $namespace;
    }

    /**
     * Get the Prometheus labels.
     *
     * @return array
     */
    protected function getPrometheusLabels(): array
    {
        return array_keys($this->getPrometheusLabelsWithValues());
    }

    /**
     * Get the Prometheus labels with their values.
     *
     * @return array
     */
    protected function getPrometheusLabelsWithValues(): array
    {
        return collect($this->option('prometheus-label'))
            ->mapWithKeys(function ($pair) {
                [$key, $value] = explode('=', $pair);

                return [$key => $value];
            })
            ->toArray();
    }
}
