<?php

require dirname(__DIR__) . '/src/Config.php';
require dirname(__DIR__) . '/src/Upload.php';

function wp_get_upload_dir() { return ['basedir' => '/wordpress/uploads']; }
function wp_generate_password($length, $special) { return 'XYZ'; }
function add_action($hook, $callback, $priority = 10, $arguments = 1) { $GLOBALS['hooks'][$hook] = $callback; }
function add_filter($hook, $callback, $priority = 10, $arguments = 1) { $GLOBALS['hooks'][$hook] = $callback; }
function check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class ObjectLookup
{
    public $exists = false;
    public $fail = false;
    public $calls = [];

    public function doesObjectExist($bucket, $object)
    {
        $this->calls[] = [$bucket, $object];
        if ($this->fail) {
            throw new RuntimeException('Metadata permission denied');
        }
        return $this->exists;
    }
}

OSS\WP\Config::$bucket = 'test-bucket';
OSS\WP\Config::$baseDir = '/wordpress/uploads';
OSS\WP\Config::$storePath = '/media';

foreach ([false, true] as $noLocalSaving) {
    $GLOBALS['hooks'] = [];
    OSS\WP\Config::$noLocalSaving = $noLocalSaving;
    new OSS\WP\Upload(new ObjectLookup());
    check(isset($GLOBALS['hooks']['wp_unique_filename']) === $noLocalSaving, 'Remote lookup hook must only run without local saving');
}

$lookup = new ObjectLookup();
$upload = new OSS\WP\Upload($lookup);
check($upload->uniqueFilename('photo.jpg', '.jpg', '/outside') === 'photo.jpg' && !$lookup->calls, 'Outside files must not invoke OSS');
check($upload->uniqueFilename('photo.jpg', '.jpg', '/wordpress/uploads/2026/10') === 'photo.jpg', 'Missing remote object must retain its filename');
check($lookup->calls[0] === ['test-bucket', 'media/2026/10/photo.jpg'], 'Remote lookup must use configured storage path');
$lookup->exists = true;
check($upload->uniqueFilename('photo.jpg', '.jpg', '/wordpress/uploads/2026/10') === 'photo-xyz.jpg', 'Existing remote object must change filename');
$lookup->fail = true;
try {
    $upload->uniqueFilename('photo.jpg', '.jpg', '/wordpress/uploads/2026/10');
    throw new RuntimeException('Permission error was silently ignored');
} catch (RuntimeException $e) {
    check($e->getMessage() === 'Metadata permission denied', 'Permission error must propagate');
}
echo "PASS: 6 cases covering conditional metadata checks, storage paths, collisions and permission errors.\n";
