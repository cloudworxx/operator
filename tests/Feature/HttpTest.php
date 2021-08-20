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
            '*' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://google.test';
        });
    }

    public function test_http_post()
    {
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--method' => 'POST',
            '--body' => json_encode($body = [
                'name' => 'test',
                'some_value' => 1,
            ]),
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
            '*' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--method' => 'POST',
            '--body' => json_encode($body = [
                'name' => 'test',
                'some_value' => 1,
            ]),
            '--post-as-form' => true,
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
            '*' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--bearer-token' => 'testing',
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
            '*' => Http::response('OK', 200),
        ]);

        $this->artisan('watch:resource', [
            '--http-url' => 'https://google.test',
            '--username' => 'testing',
            '--password' => 'secret',
            '--digest-auth' => true,
            '--once' => true,
        ]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://google.test';
        });
    }
}
