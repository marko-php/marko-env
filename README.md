# marko/env

Environment variable loading — reads a `.env` file into `$_ENV` at boot so config files can read it with `Marko\Config\Env`.

## Installation

```bash
composer require marko/env
```

## Quick Example

```php
use Marko\Env\EnvLoader;

$envLoader = new EnvLoader();
$envLoader->load(__DIR__);
```

The global `env()` helper was removed in Marko 0.9.0; read values with `Marko\Config\Env` instead.

## Documentation

Full usage, API reference, and examples: [marko/env](https://marko.build/docs/packages/env/)
