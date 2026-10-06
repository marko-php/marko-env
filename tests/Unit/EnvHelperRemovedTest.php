<?php

declare(strict_types=1);

// The global env() helper was removed in 0.9.0 (#355). Config files read environment
// variables with Marko\Config\Env. These tests keep the helper from coming back.

it('does not define a global env() function', function (): void {
    expect(function_exists('env'))->toBeFalse();
});

it('ships no autoloaded files, only the Marko\Env PSR-4 namespace', function (): void {
    $composer = json_decode(
        (string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($composer['autoload'])->toBe(['psr-4' => ['Marko\\Env\\' => 'src/']]);
});
