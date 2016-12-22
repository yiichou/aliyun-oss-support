<?php

/* =======================
        构建 phar
======================= */
$exts = ['php'];
$dir = __DIR__;
$file = 'aliyun-oss-wp.phar';
$phar = new Phar(__DIR__.'/'.$file, FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME, $file);

$phar->startBuffering();
foreach ($exts as $ext) {
    $phar->buildFromDirectory($dir, '/\.' . $ext . '$/');
}
$phar->delete('build.php');
$phar->delete('aliyun-oss.php');
$phar->delete('uninstall.php');
$phar->setStub("<?php
Phar::mapPhar('{$file}');
require 'phar://{$file}/autoload.php';
__HALT_COMPILER();
?>");
$phar->stopBuffering();

/* =======================
        打包 zip
======================= */

system("sed -i ''  's/autoload.php/aliyun-oss-wp.phar/g' aliyun-oss.php");

$zip = new ZipArchive();
$zip->open('aliyun-oss.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

$packageDirs = ['languages', 'view'];
$packageFiles = [
    'aliyun-oss.php',
    'aliyun-oss-php-sdk-2.2.1.phar',
    'aliyun-oss-wp.phar',
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

system("sed -i ''  's/aliyun-oss-wp.phar/autoload.php/g' aliyun-oss.php");


echo "Finished aliyun-oss.zip\n";