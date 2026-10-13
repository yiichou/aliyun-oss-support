<?php
/* =======================
    打包 zip
    $ php build.php {$version}
======================= */

chdir(__DIR__);
preg_match('/Version:\s(.+)\n/', file_get_contents('aliyun-oss.php'), $matches);
$version = trim($matches[1]);
if (!is_dir('out') && !mkdir('out', 0755, true)) {
    throw new RuntimeException('Cannot create output directory.');
}

$zip = new ZipArchive();
$zipPath = "out/aliyun-oss-{$version}.zip";
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Cannot create plugin ZIP.');
}

$packageDirs = ['languages', 'view', 'src', 'vendor'];
$packageFiles = [
    'aliyun-oss.php',
    'autoload.php',
    'LICENSE.md',
    'README.md',
    'uninstall.php'
];

foreach ($packageDirs as $dir) {
    $dirPath = realpath($dir);
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dirPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $relativePath = $dir . '/' . str_replace('\\', '/', substr($file->getPathname(), strlen($dirPath) + 1));
            $zip->addFile($file, 'aliyun-oss/'.$relativePath);
        }
    }
}
foreach ($packageFiles as $file) {
    $filePath = realpath($file);
    $filePath && $zip->addFile($filePath, 'aliyun-oss/'.$file);
}
$zip->close();

echo "Finished {$zipPath}\n";