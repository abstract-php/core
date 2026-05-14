<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    set_exception_handler(static function (Throwable $exception): void {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');

        echo get_class($exception) . ': ' . $exception->getMessage() . PHP_EOL . PHP_EOL;
        echo $exception->getTraceAsString();
    });
}

function example_path(string $path): string
{
    return __DIR__ . '/' . ltrim($path, '/');
}

function example_output_path(string $path): string
{
    $outputDir = example_path('output');
    if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
        throw new RuntimeException(sprintf('Unable to create examples output directory "%s".', $outputDir));
    }

    return $outputDir . '/' . ltrim($path, '/');
}

function example_print(string $label, string $value): void
{
    echo PHP_EOL . '== ' . $label . ' ==' . PHP_EOL;
    echo $value . PHP_EOL;
}
