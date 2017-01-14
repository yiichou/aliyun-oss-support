<?php

namespace OSS\WP;


class Config
{
    public static $bucket = "";
    public static $accessKeyId = "";
    public static $accessKeySecret = "";
    public static $endpoint = "";
    public static $storePath = "/";
    public static $staticHost = "";
    public static $enableImgService = false;
    public static $enableImgStyle = false;
    public static $noLocalSaving = false;

    public static $baseDir = "";

    public static $pluginPath = "aliyun-oss";
    public static $settingsUrl = "options-general.php?page=aliyun-oss";
    public static $originOptions = [
        'bucket'        => "",
        'ak'            => "",
        'sk'            => "",
        'region'        => "oss-cn-hangzhou",
        'internal'      => false,
        'path'          => "",
        'static_url'    => "",
        'img_url'       => "",
        'img_style'     => false,
        'nolocalsaving' => false,
    ];

    public static function init($plugin_path = "")
    {
        $plugin_path && self::$pluginPath = plugin_basename($plugin_path);

        $options = array_merge(self::$originOptions, get_option('oss_options', []));
        self::$bucket = $options['bucket'];
        self::$accessKeyId = $options['ak'];
        self::$accessKeySecret = $options['sk'];

        $suffix = $options['internal'] ? '-internal.aliyuncs.com' : '.aliyuncs.com';
        self::$endpoint = $options['region'].$suffix;

        if ($options['static_url'])
            self::$staticHost = is_ssl() ? "https://{$options['static_url']}" : "http://{$options['static_url']}";

        if ($options['img_service'] || $options['img_url'])
            self::$enableImgService = true;

        self::$baseDir = wp_upload_dir()['basedir'];
        self::$storePath .= trim($options['path'],'/');
        self::$enableImgStyle = $options['img_style'];
        self::$noLocalSaving = $options['nolocalsaving'];
    }

    public static function monthDir($time)
    {
        return wp_upload_dir($time)['path'];
    }

    public static function baseUrl()
    {
        return wp_upload_dir()['baseurl'];
    }

}