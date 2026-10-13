<?php

require dirname(__DIR__) . '/src/Config.php';
require dirname(__DIR__) . '/src/Delete.php';
require dirname(__DIR__) . '/src/Upload.php';

function add_action($hook, $callback, $priority = 10, $arguments = 1) { $GLOBALS['hooks'][$hook] = $callback; }
function add_filter($hook, $callback, $priority = 10, $arguments = 1) { $GLOBALS['hooks'][$hook] = $callback; }
function get_post_meta($id, $key, $single) { return $GLOBALS['contexts'][$id] ?? ''; }
function get_attached_file($id, $unfiltered = false) { return '/wordpress/uploads/' . $id . '.zip'; }
function check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
class RecordingClient
{
    public $deleted = [];
    public $uploaded = [];
    public function deleteObject($bucket, $key) { $this->deleted[] = [$bucket, $key]; }
    public function multiuploadFile($bucket, $key, $file, $headers) { $this->uploaded[] = [$bucket, $key, $file]; }
}

OSS\WP\Config::$bucket = 'test-bucket';
OSS\WP\Config::$baseDir = '/wordpress/uploads';
OSS\WP\Config::$storePath = '/media';
$GLOBALS['contexts'] = [1 => 'upgrader'];
$cases = 0;
foreach ([false, true] as $noLocalSaving) {
    OSS\WP\Config::$noLocalSaving = $noLocalSaving;
    foreach (['upload-plugin', 'upload-theme', 'cron', 'media'] as $action) {
        $_REQUEST = $action === 'cron' ? [] : ['action' => $action];
        $GLOBALS['hooks'] = [];
        $client = new RecordingClient();
        $delete = new OSS\WP\Delete($client);
        check(isset($GLOBALS['hooks']['delete_attachment']), 'Attachment context must be checked in both modes');
        $delete->deleteRemoteAttachment(1);
        check($delete->deleteRemoteFile('/wordpress/uploads/1.zip') === '/wordpress/uploads/1.zip', 'Cleanup must preserve local deletion path');
        check($client->deleted === [], 'Installer ZIP must not be deleted from OSS');
        $delete->deleteRemoteAttachment(2);
        if (!$noLocalSaving) {
            $delete->deleteRemoteFile('/wordpress/uploads/2.zip');
        }
        $installer = in_array($action, ['upload-plugin', 'upload-theme'], true);
        check(count($client->deleted) === ($installer ? 0 : 1), 'Ordinary media ZIP deletion changed');
        if (!$installer) {
            check($client->deleted[0] === ['test-bucket', 'media/2.zip'], 'Ordinary ZIP deletion target mismatch');
        }
        $upload = new OSS\WP\Upload($client);
        $fixture = ['file' => '/wordpress/uploads/1.zip', 'type' => 'application/zip'];
        if ($installer) {
            check($upload->uploadOriginToOss($fixture) === $fixture && !$client->uploaded, 'Installer ZIP must not upload or remove the local file');
        }
        $cases++;
    }
}
$_REQUEST = ['action' => ['upload-plugin']];
check(!OSS\WP\Config::isInstallerRequest(), 'Non-string action must not match installer action');
$_REQUEST = ['action' => 'upload-plugin'];
$client = new RecordingClient();
$delete = new OSS\WP\Delete($client);
call_user_func($GLOBALS['hooks']['oss_delete_file'], '/wordpress/uploads/1.zip');
check(!$client->deleted, 'Custom delete hook must respect installer exclusion');
echo 'PASS: ' . ($cases + 2) . " cases covering plugin/theme upgrades, scheduled cleanup, media ZIPs and both local-saving modes.\n";
