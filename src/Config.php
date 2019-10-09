<?php

namespace OSS\WP;

use OSS\Core\OssException;
use OSS\OssClient;

class Config
{
    public static $bucket = "";
    public static $accessKeyId = "";
    public static $accessKeySecret = "";
    public static $endpoint = "";
    public static $storePath = "/";
    public static $staticHost = "";
    public static $safeStaticHost = "";
    public static $enableImgService = false;
    public static $enableImgStyle = false;
    public static $sourceImgProtect = false;
    public static $customSeparator = "?x-oss-process=style%2F";
    public static $noLocalSaving = false;
    public static $baseDir = "";
    public static $exclude = null;
    public static $ossClient = null;
    public static $pluginPath = "aliyun-oss";
    public static $settingsUrl = "options-general.php?page=aliyun-oss";
    public static $disableUpload = false;
    public static $enableUrlAuth = false;
    public static $urlAuthMethod = "A";
    public static $urlAuthPrimaryKey = "";
    public static $urlAuthAuxKey = "";
    public static $urlAuthExpTime = 1 ;
    public static $enableUrlAuth_debug = false ;
    public static $originOptions = array(
        'bucket' => "",
        'ak' => "",
        'sk' => "",
        'region' => "oss-cn-hangzhou",
        'internal' => false,
        'path' => "",
        'static_url' => "",
        'img_url' => "",
        'img_style' => false,
        'source_img_protect' => false,
        'custom_separator' => "",
        'nolocalsaving' => false,
        'disable_upload' => false,
        'exclude' => "",
        'urlAuth' => false,
        'authMethod' => "A",
        'authPrimaryKey' => "",
        'authAuxKey' => "",
        'authExpTime' => 1,
    );


    public static function init($plugin_path = "")
    {
        $plugin_path && self::$pluginPath = plugin_basename($plugin_path);

        $options = array_merge(self::$originOptions, get_option('oss_options', array()));
        self::$bucket = $options['bucket'];
        self::$accessKeyId = $options['ak'];
        self::$accessKeySecret = $options['sk'];
        self::$endpoint = self::getEndPoint($options['region'], $options['internal']);

        if ($options['static_url']) {
            self::$staticHost = is_ssl() ? "https://{$options['static_url']}" : "http://{$options['static_url']}";
        }
        self::$safeStaticHost = "https://{$options['bucket']}.{$options['region']}.aliyuncs.com";

        if ($options['img_service'] || $options['img_url']) {
            self::$enableImgService = true;
        }

        if (!empty($options['custom_separator'])) {
            self::$customSeparator = "@{$options['custom_separator']}";
        }

        $wp_upload_dir = wp_upload_dir();
        self::$baseDir = $wp_upload_dir['basedir'];
        self::$storePath .= trim($options['path'], '/');
        self::$enableImgStyle = self::$enableImgService && $options['img_style'];
        self::$sourceImgProtect = self::$enableImgStyle && $options['source_img_protect'];
        self::$noLocalSaving = $options['nolocalsaving'];
        isset($options['disable_upload']) && self::$disableUpload = !!$options['disable_upload'];

        !empty($options['exclude']) && self::$exclude = $options['exclude'];
        self:: $enableUrlAuth =  $options['urlAuth'];
        self:: $urlAuthMethod =  $options['authMethod'];
        self:: $urlAuthPrimaryKey =  $options['authPrimaryKey'];
        self:: $urlAuthAuxKey =  $options['authAuxKey'];
        self:: $urlAuthExpTime =  $options['authExpTime'] ;
        self::initOssClient();
    }

    public static function monthDir($time)
    {
        $wp_upload_dir = wp_upload_dir($time);
        return $wp_upload_dir['path'];
    }

    public static function baseUrl()
    {
        $wp_upload_dir = wp_upload_dir();
        return $wp_upload_dir['baseurl'];
    }

    public static function initOssClient()
    {
        if (empty(self::$accessKeyId) || empty(self::$accessKeySecret) || empty(self::$endpoint)) {
            return;
        }

        if (!is_admin() && empty($_FILES)) {
            return;
        }

        try {
            self::$ossClient = new OssClient(self::$accessKeyId, self::$accessKeySecret, self::$endpoint);
        } catch (OssException $e) {
            $html = "<div id='oss-warning' class='error fade'><p>%s: %s</p></div>";
            echo sprintf($html, __('Aliyun OSS', 'aliyun-oss'), $e->getMessage());
        }
    }

    public static function checkOssConfig($options)
    {
        $html = "<div id='oss-warning' class='error fade'><p>%s</p></div>";
        $endPoint = self::getEndPoint($options['region'], $options['internal']);
        try {
            $ossClient = new OssClient($options['ak'], $options['sk'], $endPoint);
            if (!$ossClient->doesBucketExist($options['bucket'])) {
                echo sprintf($html, __('The bucket you provided does not exist.', 'aliyun-oss'));
            } else {
                return true;
            }
        } catch (OssException $e) {
            echo sprintf($html, $e->getErrorMessage() ? $e->getErrorMessage() : $e->getMessage());
        }
    }

    private static function getEndPoint($region, $internal)
    {
        $suffix = $internal ? '-internal.aliyuncs.com' : '.aliyuncs.com';
        return $region . $suffix;
    }
}
