<?php

namespace OSS\WP;

use OSS\Core\OssException;
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

        add_filter('wp_handle_upload', array($this, 'uploadOriginToOss'), 300);
        add_filter('image_make_intermediate_size', array($this, 'uploadImageToOss'), 901);
        if (Config::$noLocalSaving) {
            add_filter('wp_unique_filename', array($this, 'uniqueFilename'), 30, 3);
        }

        add_action('oss_upload_file', array($this, 'uploadFileToOss'), 9, 3);
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
        if (strpos($dir, wp_get_upload_dir()['basedir']) !== 0) {
            return $filename;
        }
        $ext = strtolower($ext);
        $object = trim(str_replace(Config::$baseDir, Config::$storePath, $dir), '/') . '/' . $filename;
        $doesExist = $this->oc->doesObjectExist(Config::$bucket, $object);
        $doesExist && $filename = rtrim($filename, $ext) . '-' . strtolower(wp_generate_password(3, false)) . $ext;
        return $filename;
    }

    /**
     * 上传原文件到 OSS (并根据设定清理本地文件)
     *
     * @param $upload
     * @return mixed
     */
    public function uploadOriginToOss($upload)
    {
        if (isset($_REQUEST["action"]) && in_array($_REQUEST["action"], array('upload-plugin', 'upload-theme'))) {
            return $upload;
        }

        $object = ltrim(str_replace(Config::$baseDir, Config::$storePath, $upload['file']), '/');
        try {
            $this->oc->multiuploadFile(Config::$bucket, $object, $upload['file'], $this->ossHeader);
        } catch (OssException $e) {
            $upload['error'] = $e->getErrorMessage();
        }

        if (Config::$noLocalSaving && false === strpos($upload['type'], 'image')) {
            Delete::deleteLocalFile($upload['file']);
        }

        return $upload;
    }

    /**
     * 上传( Wordpress 生成的)图片到 OSS (并根据设定清理本地文件)
     *
     * @param $file
     * @return mixed
     * @throws OssException
     */
    public function uploadImageToOss($file)
    {
        $caller = wp_debug_backtrace_summary(null, 4, false)[0];
        if (stristr($caller, '->multi_resize') || stristr($caller, '->make_subsize')) {
            Config::$enableImgService || $this->uploadFileToOss($file);
            Config::$noLocalSaving && Delete::deleteLocalFile($file);
        } else {
            $this->uploadFileToOss($file);
        }
        return $file;
    }

    /**
     * 将文件上传到 OSS 上
     * 通过 do_action: oss_upload_file 手动调用
     * eg. do_action('oss_upload_file', $file)
     *
     * @param string $file 文件的本地路径
     * @param string [$base_dir] 文件本地存储的基础路径，上传 OSS 时会被去掉，default: Config::$baseDir or ''
     * @param string [$oss_dir] 文件在 OSS 上的 存储目录，default: Config::$storePath
     * @throws OssException
     */
    public function uploadFileToOss($file, $base_dir = '', $oss_dir = '')
    {
        empty($base_dir) && path_is_absolute($file) && $base_dir = Config::$baseDir;
        $object = preg_replace('/^' . preg_quote($base_dir, '/') . '/', '', $file);

        $oss_dir = empty($oss_dir) ? Config::$storePath : rtrim($oss_dir, '/');
        $object = trim($oss_dir . '/' . ltrim($object, '/'), '/');

        $this->oc->multiuploadFile(Config::$bucket, $object, $file, $this->ossHeader);
    }
}
