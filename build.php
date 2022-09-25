<?php
/* =======================
    打包 zip
    $ php build.php {$version}
======================= */

preg_match('/Version:\s(.+)\n/', file_get_contents('aliyun-oss.php'), $matches);
$version = $matches[1];

$zip = new ZipArchive();
$zip->open("aliyun-oss-{$version}.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);

$packageDirs = ['languages', 'view', 'src', 'vendor/aliyuncs'];
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
            $relativePath = str_replace($dirPath, "{$dir}/", $file);
            $zip->addFile($file, 'aliyun-oss/'.$relativePath);
        }
    }
}
foreach ($packageFiles as $file) {
    $filePath = realpath($file);
    $filePath && $zip->addFile($filePath, 'aliyun-oss/'.$file);
}
$zip->close();

echo "Finished aliyun-oss-{$version}.zip\n";