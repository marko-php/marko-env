<?php

declare(strict_types=1);

beforeEach(function () {
    // Store original env state
    $this->originalEnv = $_ENV;

    // env() is deprecated: capture its E_USER_DEPRECATED notices instead of reporting them
    $this->deprecations = [];
    set_error_handler(function (int $level, string $message): bool {
        $this->deprecations[] = $message;

        return true;
    }, E_USER_DEPRECATED);
});

afterEach(function () {
    restore_error_handler();

    // Restore original env state
    $_ENV = $this->originalEnv;

    // Clean up any env vars we set via putenv
    foreach (['TEST_VAR', 'BOOL_VAR', 'NULL_VAR', 'EMPTY_VAR'] as $var) {
        putenv($var);
    }
});

it('returns value from $_ENV', function () {
    $_ENV['TEST_VAR'] = 'from_env';

    expect(env('TEST_VAR'))->toBe('from_env');
});

it('falls back to getenv when not in $_ENV', function () {
    putenv('TEST_VAR=from_getenv');

    expect(env('TEST_VAR'))->toBe('from_getenv');
});

it('returns default when variable not set', function () {
    expect(env('NONEXISTENT_VAR', 'default_value'))->toBe('default_value');
});

it('returns null when variable not set and no default', function () {
    expect(env('NONEXISTENT_VAR'))->toBeNull();
});

it('coerces "true" to boolean true', function () {
    $_ENV['BOOL_VAR'] = 'true';
    expect(env('BOOL_VAR'))->toBeTrue();

    $_ENV['BOOL_VAR'] = 'TRUE';
    expect(env('BOOL_VAR'))->toBeTrue();

    $_ENV['BOOL_VAR'] = '(true)';
    expect(env('BOOL_VAR'))->toBeTrue();

    $_ENV['BOOL_VAR'] = '(TRUE)';
    expect(env('BOOL_VAR'))->toBeTrue();
});

it('coerces "false" to boolean false', function () {
    $_ENV['BOOL_VAR'] = 'false';
    expect(env('BOOL_VAR'))->toBeFalse();

    $_ENV['BOOL_VAR'] = 'FALSE';
    expect(env('BOOL_VAR'))->toBeFalse();

    $_ENV['BOOL_VAR'] = '(false)';
    expect(env('BOOL_VAR'))->toBeFalse();

    $_ENV['BOOL_VAR'] = '(FALSE)';
    expect(env('BOOL_VAR'))->toBeFalse();
});

it('coerces "null" to null', function () {
    $_ENV['NULL_VAR'] = 'null';
    expect(env('NULL_VAR'))->toBeNull();

    $_ENV['NULL_VAR'] = 'NULL';
    expect(env('NULL_VAR'))->toBeNull();

    $_ENV['NULL_VAR'] = '(null)';
    expect(env('NULL_VAR'))->toBeNull();
});

it('coerces "empty" to empty string', function () {
    $_ENV['EMPTY_VAR'] = 'empty';
    expect(env('EMPTY_VAR'))->toBe('');

    $_ENV['EMPTY_VAR'] = 'EMPTY';
    expect(env('EMPTY_VAR'))->toBe('');

    $_ENV['EMPTY_VAR'] = '(empty)';
    expect(env('EMPTY_VAR'))->toBe('');
});

it('returns non-special strings as-is', function () {
    $_ENV['TEST_VAR'] = 'hello';
    expect(env('TEST_VAR'))->toBe('hello');

    $_ENV['TEST_VAR'] = '123';
    expect(env('TEST_VAR'))->toBe('123');

    $_ENV['TEST_VAR'] = 'production';
    expect(env('TEST_VAR'))->toBe('production');
});

it('prefers $_ENV over getenv', function () {
    $_ENV['TEST_VAR'] = 'from_env';
    putenv('TEST_VAR=from_getenv');

    expect(env('TEST_VAR'))->toBe('from_env');
});

it('uses default when value is explicitly null in env', function () {
    // Set 'null' string which gets coerced to actual null
    $_ENV['NULL_VAR'] = 'null';

    // Even though it's set, it becomes null after coercion
    expect(env('NULL_VAR', 'default'))->toBeNull();
});

it('does not use default when value is empty string', function () {
    $_ENV['EMPTY_VAR'] = '';

    // Empty string is a valid value, should not use default
    expect(env('EMPTY_VAR', 'default'))->toBe('');
});

it('does not use default when value coerces to empty string', function () {
    $_ENV['EMPTY_VAR'] = 'empty';

    // 'empty' coerces to '', which is still a set value
    expect(env('EMPTY_VAR', 'default'))->toBe('');
});

describe('deprecation', function () {
    it('emits E_USER_DEPRECATED when called', function () {
        $levels = [];
        set_error_handler(function (int $level) use (&$levels): bool {
            $levels[] = $level;

            return true;
        });

        try {
            env('TEST_VAR');
        } finally {
            restore_error_handler();
        }

        expect($levels)->toBe([E_USER_DEPRECATED]);
    });

    it('emits the deprecation on every call', function () {
        env('TEST_VAR');
        env('TEST_VAR');

        expect($this->deprecations)->toHaveCount(2);
    });

    it('names the variable and Marko\Config\Env in the deprecation message', function () {
        env('APP_NAME', 'Marko');

        expect($this->deprecations[0])
            ->toContain("env('APP_NAME')")
            ->toContain('Marko\Config\Env');
    });

    it('says env() will be removed in 1.0', function () {
        env('TEST_VAR');

        expect($this->deprecations[0])->toContain('will be removed in Marko 1.0');
    });

    it('suggests Env::bool for a boolean default', function () {
        env('APP_DEBUG', false);

        expect($this->deprecations[0])->toContain("Env::bool('APP_DEBUG', false)");
    });

    it('suggests Env::int for an integer default', function () {
        env('DB_PORT', 3306);

        expect($this->deprecations[0])->toContain("Env::int('DB_PORT', 3306)");
    });

    it('suggests Env::float for a float default', function () {
        env('SAMPLE_RATE', 0.5);

        expect($this->deprecations[0])->toContain("Env::float('SAMPLE_RATE', 0.5)");
    });

    it('suggests Env::string for a string default', function () {
        env('DB_HOST', 'localhost');

        expect($this->deprecations[0])->toContain("Env::string('DB_HOST', 'localhost')");
    });

    it('suggests Env::list for an array default', function () {
        env('TRUSTED_HOSTS', ['localhost']);

        expect($this->deprecations[0])->toContain("Env::list('TRUSTED_HOSTS', [...])");
    });

    it('suggests Env::nullableString when no default is given', function () {
        env('API_KEY');

        expect($this->deprecations[0])->toContain("Env::nullableString('API_KEY')");
    });

    it('returns the same value as before the deprecation', function () {
        $_ENV['BOOL_VAR'] = 'off';

        expect(env('BOOL_VAR', true))->toBe('off');
    });
});
