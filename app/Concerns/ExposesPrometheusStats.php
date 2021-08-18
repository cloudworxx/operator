<?php

namespace App\Concerns;

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
     * Mark the status as up.
     *
     * @return void
     */
    protected function markUptime(): void
    {
        /** @var \App\Commands\WatchResource $this */
        $this->getPrometheusGauge()->set(1, $this->getPrometheusLabelsWithValues());
    }

    /**
     * Mark the status as down.
     *
     * @return void
     */
    protected function markDowntime(): void
    {
        /** @var \App\Commands\WatchResource $this */
        $this->getPrometheusGauge()->set(0, $this->getPrometheusLabelsWithValues());
    }

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

    protected function getPrometheusLabels(): array
    {
        return array_keys($this->getPrometheusLabelsWithValues());
    }

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
