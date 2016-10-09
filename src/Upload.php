<?php

namespace OSS\WP;

use OSS\OssClient;

class Upload
{
    private $oc;

    function __construct(OssClient $ossClient)
    {
        $this->oc = $ossClient;
        $this->ossHeader = array(
            OssClient::OSS_HEADERS => array(
                'Cache-Control' => 'max-age=2592000'
            ),
        );

        add_filter('wp_handle_upload', [$this, 'uploadOriginToOss'], 30);
        add_filter('wp_update_attachment_metadata', [$this, 'uploadThumbToOss'], 60);
        add_filter('wp_save_image_editor_file', [$this, 'uploadEditedImage'], 60, 4);
    }

    /**
     * 上传原文件到 OSS (并根据设定清理本地文件)
     *
     * @param $file
     * @return mixed
     */
    function uploadOriginToOss($file)
    {
        if (isset($_REQUEST["action"]) && in_array($_REQUEST["action"], ['upload-plugin', 'upload-theme']))
            return $file;

        $object = ltrim(str_replace(Config::$baseDir, Config::$storePath, $file['file']), '/');
        $this->oc->multiuploadFile(Config::$bucket, $object, $file['file'], $this->ossHeader);

        if (Config::$noLocalSaving && false === strpos($file['type'], 'image'))
            Delete::deleteLocalFile($file['file']);

        return $file;
    }

    /**
     * 上传生成的缩略图到 OSS (并根据设定清理本地文件)
     *
     * @param $metadata
     * @return mixed
     */
    function uploadThumbToOss($metadata)
    {
        if (isset($metadata['sizes']) && preg_match('/\d{4}\/\d{2}/', $metadata['file'], $m)) {
            foreach ($metadata['sizes'] as $val) {
                $file = Config::monthDir($m[0]) . '/' . $val['file'];

                if (Config::$imgHost == "") {
                    $object = ltrim(str_replace(Config::$baseDir, Config::$storePath, $file), '/');
                    $this->oc->multiuploadFile(Config::$bucket, $object, $file, $this->ossHeader);
                }

                Config::$noLocalSaving && Delete::deleteLocalFile($file);
            }

            Config::$noLocalSaving && Delete::deleteLocalFile(Config::$baseDir.'/'.$metadata['file']);
        }

        return $metadata;
    }

    function uploadEditedImage($override, $filename, $image, $mime_type)
    {
        $image->save($filename, $mime_type);
        $object = ltrim(Config::$storePath.'/'._wp_relative_upload_path($filename), '/');
        $this->oc->multiuploadFile(Config::$bucket, $object, $filename, $this->ossHeader);

        return $override;
    }

}