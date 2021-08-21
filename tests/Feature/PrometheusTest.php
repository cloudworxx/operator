<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PrometheusTest extends TestCase
{
    public function test_pushgateway_push()
    {
        Http::fake([
            'google.test' => Http::sequence()
                ->push('OK', 200)
                ->push('500 Error', 500),
            'pushgateway.test/*' => Http::sequence()
                ->push('OK', 200)
                ->push('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--prometheus-identifier' => 'test',
            '--prometheus-label' => [
                'label1=value1',
                'label2=value2',
            ],
            '--pushgateway-url' => 'https://pushgateway.test',
            '--once' => true,
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--prometheus-identifier' => 'test',
            '--prometheus-label' => [
                'label1=value1',
                'label2=value2',
            ],
            '--pushgateway-url' => 'https://pushgateway.test',
            '--once' => true,
        ]);

        Http::assertSentInOrder([
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
            function (Request $request) {
                $this->assertEquals(
                    'https://pushgateway.test/metrics/job/test/label1/value1/label2/value2',
                    $request->url(),
                );

                $this->assertEquals('PUT', $request->method());

                $this->assertStringContainsString(
                    'test_uptime{label1="value1",label2="value2"} 1',
                    array_values($request->data())[0],
                );

                return true;
            },
            function (Request $request) {
                $this->assertEquals('https://google.test', $request->url());

                return true;
            },
            function (Request $request) {
                $this->assertEquals(
                    'https://pushgateway.test/metrics/job/test/label1/value1/label2/value2',
                    $request->url(),
                );

                $this->assertEquals('PUT', $request->method());

                $this->assertStringContainsString(
                    'test_uptime{label1="value1",label2="value2"} 0',
                    array_values($request->data())[0],
                );

                return true;
            },
        ]);
    }
}
