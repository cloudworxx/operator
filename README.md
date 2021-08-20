Soketi Network Watcher
=======================

![CI](https://github.com/opsie/operator/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/opsie/operator/branch/master/graph/badge.svg)](https://codecov.io/gh/opsie/operator)
[![StyleCI](https://github.styleci.io/repos/397491616/shield?branch=master)](https://github.styleci.io/repos/397491616)

Monitor any website. Run in Docker or any Kubernetes cluster. Send webhooks.

## 🤝 Supporting

If you are using one or more Renoki Co. open-source packages in your production apps, in presentation demos, hobby projects, school projects or so, spread some kind words about our work or sponsor our work via Patreon. 📦

You will sometimes get exclusive content on tips about Laravel, AWS or Kubernetes on Patreon and some early-access to projects or packages.

[<img src="https://c5.patreon.com/external/logo/become_a_patron_button.png" height="41" width="175" />](https://www.patreon.com/bePatron?u=10965171)

## 🚀 Installation

```bash
composer install --ignore-platform-reqs && cp .env.example .env
```

## Running the operator

```bash
php artisan watch:resource \
    --http-url=https://google.com \
    --interval=5
```

## Increase timeout

The default timeout is `10` seconds, but it may be changed:

```bash
php artisan watch:resource \
    --http-url=https://google.com \
    --timeout=30
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
