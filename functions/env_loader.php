<?php
// functions/env_loader.php

function loadEnv($path)
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed_line = trim($line);
        if (empty($trimmed_line) || strpos($trimmed_line, '#') === 0 || strpos($trimmed_line, '=') === false) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        
        $name = trim($parts[0]);
        $value = trim($parts[1]);

        // Remove quotes if present
        if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
            $value = $matches[1];
        }

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            if (function_exists('putenv')) {
                putenv(sprintf('%s=%s', $name, $value));
            }
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Automatically load .env if it exists in the root
loadEnv(__DIR__ . '/../.env');

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        if (isset($_ENV[$key])) return $_ENV[$key];
        if (isset($_SERVER[$key])) return $_SERVER[$key];

        $value = function_exists('getenv') ? getenv($key) : false;

        return $value === false ? $default : $value;
    }
}
