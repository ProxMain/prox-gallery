<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$paths = [$root . '/prox-gallery.php'];
foreach (['src', 'tests', 'scripts'] as $directory) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory)) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $paths[] = $file->getPathname();
        }
    }
}
foreach ($paths as $path) {
    $output = [];
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1', $output, $status);
    if ($status !== 0) {
        fwrite(STDERR, implode(PHP_EOL, $output) . PHP_EOL);
        exit($status);
    }
}
echo 'PHP syntax valid: ' . count($paths) . ' files.' . PHP_EOL;
