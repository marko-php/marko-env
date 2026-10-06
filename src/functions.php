<?php

declare(strict_types=1);

// The guard keeps a global env() that another library defines first (for example
// illuminate/support) from becoming a fatal "Cannot redeclare" error. When that
// happens, the other library's env() runs instead of this one. Marko\Config\Env
// is a class, so it never has this problem.
if (!function_exists('env')) {
    /**
     * Get an environment variable with optional default and type coercion.
     *
     * Type coercion handles common string representations:
     * - 'true', '(true)' → true
     * - 'false', '(false)' → false
     * - 'null', '(null)' → null
     * - 'empty', '(empty)' → ''
     *
     * Every call emits E_USER_DEPRECATED naming the variable and the
     * Marko\Config\Env method that replaces it.
     *
     * @deprecated Read environment variables in config files with Marko\Config\Env
     *     (Env::string(), Env::bool(), Env::int(), ...). env() will be removed in Marko 1.0.
     *
     * @param string $key The environment variable name
     * @param mixed $default Default value if the variable is not set
     * @return mixed The environment variable value (with type coercion) or default
     */
    function env(
        string $key,
        mixed $default = null,
    ): mixed {
        // marko/env stays dependency-free, so the replacement class is named as a string.
        $name = var_export($key, true);
        $replacement = match (true) {
            is_bool($default) => sprintf('Env::bool(%s, %s)', $name, $default ? 'true' : 'false'),
            is_int($default) => sprintf('Env::int(%s, %d)', $name, $default),
            is_float($default) => sprintf('Env::float(%s, %s)', $name, var_export($default, true)),
            is_string($default) => sprintf('Env::string(%s, %s)', $name, var_export($default, true)),
            is_array($default) => sprintf('Env::list(%s, [...])', $name),
            default => sprintf('Env::nullableString(%s)', $name),
        };

        trigger_error(
            sprintf(
                'env(%s) is deprecated and will be removed in Marko 1.0. Read it in a config file with Marko\Config\Env instead: %s. Env throws on a value it cannot parse and treats an empty value as unset.',
                $name,
                $replacement,
            ),
            E_USER_DEPRECATED,
        );

        // Check $_ENV first (populated by EnvLoader), then getenv() as fallback
        $value = $_ENV[$key] ?? null;

        if ($value === null) {
            $envValue = getenv($key);
            $value = $envValue === false ? null : $envValue;
        }

        if ($value === null) {
            return $default;
        }

        // Type coercion for common string patterns
        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}
