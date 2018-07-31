<?php

namespace OSS\WP;

use OSS\OssClient;
use Exception;

class Delete
{
    private $oc;

    public function __construct(OssClient $ossClient = null)
    {
        $this->oc = $ossClient ? $ossClient : Config::$ossClient;

        add_filter('wp_delete_file', array($this, 'deleteRemoteFile'));
        if (Config::$noLocalSaving) {
            add_filter('wp_update_attachment_metadata', array($this, 'deleteLocalOriginImage'), 60);
        }

        add_action('oss_delete_file', array($this, 'deleteRemoteFile'), 9);
    }

    /**
     * 删除远程服务器上的单个文件
     *
     * @param $file
     * @return mixed
     */
    public function deleteRemoteFile($file)
    {
        if (false === strpos($file, '@')) {
            $del_file = ltrim(str_replace(Config::$baseDir, Config::$storePath, $file), '/');
            $this->oc->deleteObject(Config::$bucket, $del_file);
        }

        return $file;
    }

    /**
     * 删除本地的原图 (本地不保留文件开启时)
     * 由于缩略图等操作时依赖原图,所以原图需要在最后单独删掉
     *
     * @param $metadata
     * @return mixed
     */
    public function deleteLocalOriginImage($metadata)
    {
        self::deleteLocalFile(Config::$baseDir.'/'.$metadata['file']);
        return $metadata;
    }

    /**
     * 删除本地的文件
     *
     * @param $file
     * @return bool
     */
    public static function deleteLocalFile($file)
    {
        try {
            //文件不存在
            if (!@file_exists($file)) {
                return true;
            }
            //删除文件
            if (!@unlink($file)) {
                return false;
            }
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }
}
