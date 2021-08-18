Soketi Network Watcher
=======================

![CI](https://github.com/opsie/operator/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/opsie/operator/branch/master/graph/badge.svg)](https://codecov.io/gh/opsie/operator)
[![StyleCI](https://github.styleci.io/repos/350800968/shield?branch=master)](https://github.styleci.io/repos/350800968)

Monitor the [pWS server](https://github.com/soketi/pws) container for memory allowance and new connections when running in Kubernetes.

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

## Prometheus Export

Using ReactPHP, a HTTP webserver is exposed on port `80` that exports the Prometheus metrics regarding uptime in order to be scraped.

To attach Prometheus labels, pass multiple `key=value` pairs to the `--prometheus-label` flag:

```bash
php artisan watch:resource \
    --http-url=https://google.com \
    --prometheus-identifier=my_app \
    --prometheus-label=user_id=1 \
    --prometheus-label=app_id=1 \
    --verbose
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
