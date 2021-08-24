<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpTest extends TestCase
{
    public function test_http_get()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) {
            $this->assertEquals('https://google.test', $request->url());

            return true;
        });
    }

    public function test_http_post()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--method' => 'POST',
            '--body' => json_encode($body = [
                'name' => 'test',
                'some_value' => 1,
            ]),
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) use ($body) {
            $this->assertEquals('POST', $request->method());
            $this->assertEquals($body, $request->data());

            return true;
        });
    }

    public function test_http_post_as_form()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--method' => 'POST',
            '--body' => json_encode($body = [
                'name' => 'test',
                'some_value' => 1,
            ]),
            '--post-as-form' => true,
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) use ($body) {
            $this->assertEquals('POST', $request->method());
            $this->assertEquals($body, $request->data());

            return true;
        });
    }

    public function test_token_authentication()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--bearer-token' => 'testing',
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) {
            $this->assertEquals(
                'Bearer testing',
                $request->header('Authorization')[0],
            );

            return true;
        });
    }

    public function test_http_basic_authentication()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--username' => 'testing',
            '--password' => 'secret',
            '--digest-auth' => true,
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) {
            $this->assertEquals('https://google.test', $request->url());

            return true;
        });
    }

    public function test_headers()
    {
        Http::fake([
            'google.test' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--header' => [
                'X-Header-One=one',
                'X-Header-Two=two',
            ],
            '--skip-initial-check' => true,
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) {
            $this->assertEquals('https://google.test', $request->url());
            $this->assertEquals(['one'], $request->header('X-Header-One'));
            $this->assertEquals(['two'], $request->header('X-Header-Two'));

            return true;
        });
    }
}
