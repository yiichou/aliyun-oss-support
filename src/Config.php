<?php

namespace OSS\WP;

use Exception;
use OSS\WP\OssClientAdapter;

class Config
{
    public static $bucket = "";
    public static $accessKeyId = "";
    public static $accessKeySecret = "";
    public static $endpoint = "";
    public static $region = "";
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
        self::$region = preg_replace('/^oss-/', '', $options['region']);

        if ($options['static_url']) {
            self::$staticHost = is_ssl() ? "https://{$options['static_url']}" : "http://{$options['static_url']}";
        }
        self::$safeStaticHost = "https://{$options['bucket']}.{$options['region']}.aliyuncs.com";

        if ($options['img_service'] || $options['img_url']) {
            self::$enableImgService = true;
        }

        if (!empty($options['custom_separator'])) {
            self::$customSeparator = "{$options['custom_separator']}";
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

        if (!is_admin() && $_FILES && !current_user_can( 'edit_posts' ) && !defined( 'REST_REQUEST' )) {
            return;
        }

        try {
            self::$ossClient = new OssClientAdapter(self::$accessKeyId, self::$accessKeySecret, self::$endpoint, self::$region);
        } catch (\Exception $e) {
            $html = "<div id='oss-warning' class='error fade'><p>%s: %s</p></div>";
            echo sprintf($html, __('Aliyun OSS', 'aliyun-oss'), $e->getMessage());
        }
    }

    public static function isInstallerRequest(): bool
    {
        return isset($_REQUEST['action']) && in_array($_REQUEST['action'], ['upload-plugin', 'upload-theme'], true);
    }

    public static function checkOssConfig($options, ?array &$report = null)
    {
        $report = ['details' => '', 'warning' => ''];
        $endPoint = self::getEndPoint($options['region'], $options['internal']);
        try {
            $region = preg_replace('/^oss-/', '', $options['region']);
            $ossClient = new OssClientAdapter($options['ak'], $options['sk'], $endPoint, $region);
        } catch (Exception $e) {
            $report['details'] = __('OSS client initialization failed.', 'aliyun-oss') . "\n" . $e->getMessage();
            return false;
        }

        $prefix = trim($options['path'] ?? '', '/');
        try {
            $ossClient->validateBucketAccess($options['bucket'], $prefix === '' ? '' : $prefix . '/');
            $listing = ['status' => 'passed', 'message' => ''];
        } catch (Exception $e) {
            $listing = ['status' => 'failed', 'message' => $e->getMessage()];
        }
        try {
            $results = $ossClient->verifyWritePermissions($options['bucket']);
        } catch (Exception $e) {
            $report['details'] = __('Upload and delete verification could not be completed.', 'aliyun-oss') . "\n" . $e->getMessage();
            $listingStatus = $listing['status'] === 'passed' ? __('Passed', 'aliyun-oss') : __('Failed', 'aliyun-oss');
            $report['details'] .= "\n" . __('List objects', 'aliyun-oss') . ': ' . $listingStatus . "\n" . $listing['message'];
            return false;
        }

        $checks = [
            __('List objects', 'aliyun-oss') => $listing,
            __('Upload test object', 'aliyun-oss') => $results['upload'],
            __('Delete test object', 'aliyun-oss') => $results['delete'],
        ];
        $success = true;
        $messages = [];
        $details = [];
        foreach ($checks as $label => $result) {
            $passed = $result['status'] === 'passed';
            $success = $success && $passed;
            $status = $passed ? __('Passed', 'aliyun-oss') : __('Failed', 'aliyun-oss');
            if ($result['status'] === 'skipped') {
                $status = __('Not verified: upload failed; cleanup request succeeded.', 'aliyun-oss');
            }
            $messages[] = $label . ': ' . $status;
            if ($result['message'] !== '') {
                $details[] = $label . ":\n" . $result['message'];
            }
        }
        $message = implode(' | ', $messages);
        if ($results['delete']['status'] === 'failed') {
            $report['warning'] = sprintf(
                __('Cleanup failed. A test object may remain at oss://%s/%s. Check and remove it manually.', 'aliyun-oss'),
                $options['bucket'],
                $results['object']
            );
        }
        $report['details'] = $message;
        if ($details) {
            $report['details'] .= "\n\n" . implode("\n\n", $details);
        }
        return $success;
    }

    public static function renderValidationNotice(bool $success, string $summary, string $details = ''): void
    {
        echo '<div class="notice inline is-dismissible '. ($success ? 'notice-success' : 'notice-error') .'"><p>'.
            nl2br(esc_html($summary)) .'</p>';
        if ($details !== '') {
            echo '<details><summary>'. esc_html(__('View details', 'aliyun-oss')) .'</summary><pre style="white-space: pre-wrap; overflow-wrap: anywhere;">'.
                esc_html($details) .'</pre></details>';
        }
        echo '</div>';
    }

    private static function getEndPoint($region, $internal)
    {
        $suffix = $internal ? '-internal.aliyuncs.com' : '.aliyuncs.com';
        return $region . $suffix;
    }
}
