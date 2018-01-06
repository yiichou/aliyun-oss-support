<?php

namespace OSS\WP;


class Config
{
    private static $special_vpc_list = array('oss-cn-hangzhou', 'oss-cn-shanghai', 'oss-cn-qingdao', 'oss-cn-beijing',
                                        'oss-cn-shenzhen', 'oss-cn-hongkong', 'oss-us-west-1', 'oss-ap-southeast-1');

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
    public static $originOptions = array(
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
    );

    public static function init($plugin_path = "")
    {
        $plugin_path && self::$pluginPath = plugin_basename($plugin_path);

        $options = array_merge(self::$originOptions, get_option('oss_options', array()));
        self::$bucket = $options['bucket'];
        self::$accessKeyId = $options['ak'];
        self::$accessKeySecret = $options['sk'];

        $suffix = $options['internal'] ? '-internal.aliyuncs.com' : '.aliyuncs.com';
        self::$endpoint = $options['region'].$suffix;

        if ($options['vpc'] && in_array($options['region'], self::$special_vpc_list))
            self::$endpoint = "vpc100-{$options['region']}.aliyuncs.com";

        if ($options['static_url'])
            self::$staticHost = is_ssl() ? "https://{$options['static_url']}" : "http://{$options['static_url']}";

        if ($options['img_service'] || $options['img_url'])
            self::$enableImgService = true;

        $wp_upload_dir = wp_upload_dir();
        self::$baseDir = $wp_upload_dir['basedir'];
        self::$storePath .= trim($options['path'],'/');
        self::$enableImgStyle = $options['img_style'];
        self::$noLocalSaving = $options['nolocalsaving'];
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

}