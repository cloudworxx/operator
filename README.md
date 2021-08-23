Soketi Network Watcher
=======================

![CI](https://github.com/opsie-app/operator/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/opsie-app/operator/branch/master/graph/badge.svg)](https://codecov.io/gh/opsie-app/operator)
[![StyleCI](https://github.styleci.io/repos/397491616/shield?branch=master)](https://github.styleci.io/repos/397491616)

Monitor any website. Run in Docker or any Kubernetes cluster. Send webhooks.

## 🤝 Supporting

If you are using one or more Renoki Co. open-source packages in your production apps, in presentation demos, hobby projects, school projects or so, spread some kind words about our work or sponsor our work via Patreon. 📦

You will sometimes get exclusive content on tips about Laravel, AWS or Kubernetes on Patreon and some early-access to projects or packages.

[<img src="https://c5.patreon.com/external/logo/become_a_patron_button.png" height="41" width="175" />](https://www.patreon.com/bePatron?u=10965171)

## 🚀 Installation

```bash
composer install --ignore-platform-reqs
```

## Running the operator

```bash
php artisan watch:resource \
    --http-url=https://google.com \
    --interval=5
```

## Compiling from source

The examples are based on the `php artisan watch:resource` command that is available in development, where you have the source code.

To compile from source and use directly the binary, you may pull the project, install dependencies and build the binary:

```bash
composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-dev
```

```bash
php artisan app:build opsie-status-operator --build-version=stable
```

Within the `builds/` folder, you will find a `opsie-status-operator` file. This is the executable that will serve the needed `watch:resource` command instead of carrying around the source each time you want to run the operator.

```bash
opsie-status-operator watch:resource --http-url="https://google.com"
```

## Increase timeout

The default timeout is `10` seconds, but it may be changed:

```bash
php artisan watch:resource \
    --http-url=https://google.com \
    --timeout=30
```

## Send headers

You may send headers with the `--header` flag:

```bash
php artisan watch:resource \
    --http-url=https://google.com \
    --header=X-My-Header=3600 \
    --header=X-Other-Header=abc \
    --header=X-Another-Header=xxxx
```

## Authenticate with Basic Auth or Auth Digest

```bash
php artisan watch:resource \
    --http-url=https://google.com \
    --username=admin \
    --password=secretpassword
```

To enable digest auth, use `--digest-auth` flag:

```bash
php artisan watch:resource \
    --http-url=https://google.com \
    --username=admin \
    --password=secretpassword \
    --digest-auth
```

Optionally you may pass the username & password via environment variables and omit the flags:

```bash
export HTTP_USERNAME=admin
export HTTP_PASSWORD=secretpassword

php artisan watch:resource --http-url=https://google.com
```

## Authenticate with Bearer token

Same as with the Basic Auth, you can pass the token via flag or environment variable:

```bash
php artisan watch:resource \
    --http-url=https://google.com \
    --bearer-token=xxxxxxx
```

```bash
export HTTP_BEARER_TOKEN=xxxxxxxx

php artisan watch:resource --http-url=https://google.com
```

## Pushgateway Export

Status Operator can push the uptime metrics to a Prometheus instance via Pushgateway. To enable it, a Pushgateway URL should be provided:

To attach Prometheus labels, pass multiple `key=value` pairs to the `--prometheus-label` flag:

```bash
php artisan watch:resource \
    --http-url=https://google.com \
    --pushgateway-url=http://pushgateway.default.svc.kubernetes.local \
    --prometheus-identifier=my_app \
    --prometheus-label=user_id=1 \
    --prometheus-label=app_id=1 \
    --verbose
```

## Webhooks

You can catch externally the downtime/uptime events via webhooks. You may define the links where you want a HTTP POST request to be sent to. The payload sent will be hashed using the HMAC of the POST body, using the defined secret.

The webhooks are being called using an `Opsiebot/1.0` User-Agent header, so make sure it gets whitelisted in case you are automatically block any `*bot` user agents.

```bash
php artisan watch:resource \
    --http-url=https://google.com \
    --webhook-url=https://mywebsite1.com \
    --webhook-secret=some-secret-1 \
    --webhook-url=https://mywebsite2.com \
    --webhook-secret=some-secret-2
```

**Make sure that the number of `--webhook-url` flags is the same as `--webhook-secret`.**

You may alternatively define it using a environment variable called `HTTP_WEBHOOKS` that contains a JSON-encoded string that looks like this:

```json
[
    {
        "url": "https://mywebsite1.com",
        "secret": "some-secret-1"
    },
    {
        "url": "https://mywebsite2.com",
        "secret": "some-secret-2"
    }
]
```

```bash
export HTTP_WEBHOOKS='[{"url": "https://mywebsite1.com", "secret": ...}]'

php artisan watch:resource --http-url=https://google.com
```

The webhooks are being sent using [spatie/laravel-webhook-server](https://github.com/spatie/laravel-webhook-secret), a package that does the job in the background of sending the webhooks. The webhooks are signed with a `Signature` header in the request which value is calculated using the [`hash_hmac` of the JSON-encoded sent body with the webhook secret](https://github.com/spatie/laravel-webhook-server#how-signing-requests-works). Make sure to check this header when receiving the webhooks.

For Laravel applications, you may want to simplify this process by using [spatie/laravel-webhook-client](https://github.com/spatie/laravel-webhook-client).

The webhook payload that is being sent will look like this:

```json
{
    "status": 200,
    "up": true,
    "headers": {
        "X-Header-One": "value"
    },
    "time": "2021-08-23T20:41:28+00:00",
    "response_time_ms": 230,
    "id": "231c3d85-16ea-41bc-a980-9b584b0fc9b3"
}
```

## Docker

The production versions are being automatically bundled into a Docker image. [Head over to quay.io](https://quay.io/repository/opsie/operator) to see the available versions.

```bash
$ docker run quay.io/opsie/operator /app/opsie-status-operator watch:resource \
    --http-url=https://google.com \
    --verbose

Setting the HTTP checks protocol...
Website is up.
[2021-08-20T13:32:20+00:00] HTTP Status: 200
Waiting between requests...
```

## 🐛 Testing

``` bash
vendor/bin/phpunit
```

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔒  Security

If you discover any security related issues, please email alex@renoki.org instead of using the issue tracker.

## 🎉 Credits

- [Alex Renoki](https://github.com/rennokki)
- [All Contributors](../../contributors)
