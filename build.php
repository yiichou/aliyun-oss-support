<?php
/* =======================
        打包 zip
======================= */

$zip = new ZipArchive();
$zip->open('aliyun-oss.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

$packageDirs = ['languages', 'view', 'vendor', 'src'];
$packageFiles = [
    'aliyun-oss.php',
    'LICENSE.md',
    'README.md',
    'readme.txt',
    'screenshot.png',
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
            $relativePath = str_replace($dirPath, "{$dir}/", $file);
            $zip->addFile($file, $relativePath);
        }
    }
}
foreach ($packageFiles as $file) {
    $filePath = realpath($file);
    $filePath && $zip->addFile($filePath, $file);
}
$zip->close();

echo "Finished aliyun-oss.zip\n";