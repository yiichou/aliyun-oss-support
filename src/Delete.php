<?php

namespace OSS\WP;

use OSS\OssClient;
use Exception;

class Delete
{
    private $oc;

    public function __construct(OssClient $ossClient)
    {
        $this->oc = $ossClient;

        add_action('wp_delete_file', [$this, 'deleteRemoteFile']);
        Config::$enableImgService && add_action('wp_delete_file', [$this, 'deleteLocalThumbs'], 99);
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
     * 删除本地的缩略图（修正由于启用图片服务导致的原生方法删除不了缩略图）
     * 仅在开启图片服务时启用
     *
     * @param $file
     * @return mixed
     */
    public function deleteLocalThumbs($file)
    {
        if (false === strpos($file, '@')) {
            $file_t = substr($file, 0, strrpos($file, '.'));
            array_map('self::deleteLocalFile', glob($file_t.'-*'));
        }
        return $file;
    }

    /**
     * 删除本地的文件
     *
     * @param $file
     * @return bool
     */
    public static function deleteLocalFile($file)
    {
        try{
            //文件不存在
            if (!@file_exists($file))
                return TRUE;
            //删除文件
            if (!@unlink($file))
                return FALSE;
            return TRUE;
        }
        catch(Exception $ex) {
            return FALSE;
        }
    }



}