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
            'google.test' => Http::response('OK', 200),
            'pushgateway.test/*' => Http::response('OK', 200),
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
                return $request->url() === 'https://google.test';
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
            }
        ]);
    }
}
