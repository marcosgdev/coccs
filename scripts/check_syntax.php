<?php

$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$errors = [];
foreach ($iterator as $file) {
    if (! $file instanceof SplFileInfo || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
        continue;
    }

    $command = PHP_BINARY . ' -l ' . escapeshellarg($path);
    exec($command, $output, $code);
    if ($code !== 0) {
        $errors[] = implode(PHP_EOL, $output);
    }
    $output = [];
}

if ($errors) {
    echo implode(PHP_EOL . PHP_EOL, $errors) . PHP_EOL;
    exit(1);
}

echo "Todos os arquivos PHP passaram no lint.\n";
