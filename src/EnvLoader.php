<?php

namespace ApiCrumbs\Framework;

class EnvLoader
{
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // 1. Skip comments and empty lines
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;

            // 2. Split by first '='
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                
                $name = trim($name);
                $value = trim($value);

                // 3. Strip quotes if they exist (e.g. KEY="Value")
                $value = trim($value, '"\'');

                // 4. Inject into all PHP environment stores
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
}