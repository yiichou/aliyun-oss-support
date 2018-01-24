<?php

namespace OSS\WP;

use OSS\OssClient;

class Upload
{
    private $oc;

    public function __construct(OssClient $ossClient)
    {
        $this->oc = $ossClient;
        $this->ossHeader = array(
            OssClient::OSS_HEADERS => array(
                'Cache-Control' => 'max-age=2592000'
            ),
        );

        add_filter('wp_handle_upload', array($this, 'uploadOriginToOss'), 30);
        add_filter('wp_update_attachment_metadata', array($this, 'uploadThumbToOss'), 60);
        add_filter('wp_save_image_editor_file', array($this, 'uploadEditedImage'), 60, 4);
        if (Config::$noLocalSaving) {
            add_filter('wp_unique_filename', array($this, 'uniqueFilename'), 30, 3);
        }
    }

    /**
     * 确保文件名在目标文件夹中唯一
     *
     * @param $filename
     * @param $ext
     * @param $dir
     * @return string
     */
    public function uniqueFilename($filename, $ext, $dir)
    {
        $ext = strtolower($ext);
        $object = trim(str_replace(Config::$baseDir, Config::$storePath, $dir), '/') . '/' . $filename;
        $doesExist = $this->oc->doesObjectExist(Config::$bucket, $object);
        $doesExist && $filename = rtrim($filename, $ext) . '-' . strtolower(wp_generate_password(3, false)) . $ext;
        return $filename;
    }

    /**
     * 上传原文件到 OSS (并根据设定清理本地文件)
     *
     * @param $file
     * @return mixed
     */
    public function uploadOriginToOss($file)
    {
        if (isset($_REQUEST["action"]) && in_array($_REQUEST["action"], array('upload-plugin', 'upload-theme'))) {
            return $file;
        }

        $object = ltrim(str_replace(Config::$baseDir, Config::$storePath, $file['file']), '/');
        $this->oc->multiuploadFile(Config::$bucket, $object, $file['file'], $this->ossHeader);

        if (Config::$noLocalSaving && false === strpos($file['type'], 'image')) {
            Delete::deleteLocalFile($file['file']);
        }

        return $file;
    }

    /**
     * 上传生成的缩略图到 OSS (并根据设定清理本地文件)
     *
     * @param $metadata
     * @return mixed
     */
    public function uploadThumbToOss($metadata)
    {
        if (isset($metadata['sizes']) && preg_match('/\d{4}\/\d{2}/', $metadata['file'], $m)) {
            $thumbs = array();
            foreach ($metadata['sizes'] as $val) {
                $thumbs[] = Config::monthDir($m[0]) . '/' . $val['file'];
            }

            if (!Config::$enableImgService) {
                foreach ($thumbs as $thumb) {
                    $object = ltrim(str_replace(Config::$baseDir, Config::$storePath, $thumb), '/');
                    $this->oc->multiuploadFile(Config::$bucket, $object, $thumb, $this->ossHeader);
                }
            }

            if (Config::$noLocalSaving) {
                foreach ($thumbs as $thumb) {
                    Delete::deleteLocalFile($thumb);
                }
                Delete::deleteLocalFile(Config::$baseDir.'/'.$metadata['file']);
            }
        }

        return $metadata;
    }

    public function uploadEditedImage($override, $filename, $image, $mime_type)
    {
        $image->save($filename, $mime_type);
        $object = ltrim(Config::$storePath.'/'._wp_relative_upload_path($filename), '/');
        $this->oc->multiuploadFile(Config::$bucket, $object, $filename, $this->ossHeader);

        return $override;
    }
}
