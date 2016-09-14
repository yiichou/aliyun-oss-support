<?php
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

echo "Finished {$file}\n";