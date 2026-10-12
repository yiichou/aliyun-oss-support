<?php

namespace OSS\WP {
    class Config
    {
        public static $valid = false;
        public static $candidate;
        public static $warning = '';

        public static function checkOssConfig($options, ?array &$report = null)
        {
            $report = ['details' => 'Permission check details', 'warning' => self::$warning];
            self::$candidate = $options;
            if ($GLOBALS['writes'] !== 0) {
                throw new \RuntimeException('Settings written before validation');
            }
            return self::$valid;
        }

        public static function renderValidationNotice(bool $success, string $summary, string $details = ''): void
        {
            echo '<div class="notice is-dismissible"><p>'. \esc_html($summary) .'</p><details><summary>View details</summary><pre>'.
                \esc_html($details) .'</pre></details></div>';
        }
    }
}

namespace {
    require dirname(__DIR__) . '/src/Setting.php';

    function get_option($name, $default = false) { return $GLOBALS['saved']; }
    function update_option($name, $options)
    {
        $GLOBALS['writes']++;
        if ($GLOBALS['writeFailure'] || $GLOBALS['saved'] === $options) {
            return false;
        }
        $GLOBALS['saved'] = $options;
        return true;
    }
    function __($text, $domain = null) { return $text; }
    function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
    function check($condition, $message)
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    $baseline = [
        'ak' => 'original-key', 'sk' => 'original-secret', 'bucket' => 'original-bucket',
        'region' => 'oss-cn-hangzhou', 'path' => 'uploads', 'static_url' => '',
        'internal' => false, 'vpc' => false, 'disable_upload' => false,
        'nolocalsaving' => false, 'img_service' => false, 'img_style' => false,
        'source_img_protect' => false, 'custom_separator' => '', 'keep_settings' => false,
        'exclude' => '', 'urlAuth' => false, 'authMethod' => 'A',
        'authPrimaryKey' => '', 'authAuxKey' => '', 'authExpTime' => 1,
    ];
    $setting = (new ReflectionClass(OSS\WP\Setting::class))->newInstanceWithoutConstructor();
    $update = new ReflectionMethod($setting, 'updateSettings');
    $update->setAccessible(true);

    foreach (['invalid-key', 'invalid-secret', 'valid', 'unchanged', 'write-failure', 'cleanup-failure', 'normalized-fields', 'upload-disabled', 'image-service-disabled'] as $case) {
        $GLOBALS['saved'] = $baseline;
        $GLOBALS['writes'] = 0;
        $GLOBALS['writeFailure'] = $case === 'write-failure';
        OSS\WP\Config::$valid = !in_array($case, ['invalid-key', 'invalid-secret', 'cleanup-failure'], true);
        OSS\WP\Config::$warning = $case === 'cleanup-failure' ? 'Cleanup failed: oss://test-bucket/test.txt' : '';
        $_POST = ['access_key' => $case === 'unchanged' ? 'original-key' : 'candidate-key'];
        if ($case === 'invalid-secret') {
            $_POST['access_key_secret'] = 'invalid-secret';
        }
        if ($case === 'unchanged') {
            $GLOBALS['saved']['static_url'] = 'original-bucket.oss-cn-hangzhou.aliyuncs.com';
        }
        if ($case === 'normalized-fields') {
            $_POST += [
                'access_key_secret' => ' new-secret ', 'bucket' => ' new-bucket ',
                'region' => ' oss-cn-shanghai ', 'store_path' => ' uploads/new ',
                'static_host' => 'https://cdn.example.com/path',
                'internal' => '1', 'vpc' => '1', 'no_local_saving' => '1',
                'img_service' => '1', 'img_style' => '1', 'source_img_protect' => '1',
                'custom_separator' => '!', 'keep_settings' => '1',
                'urlAuth' => '1', 'authMethod' => ' B ', 'authExpTime' => '12',
                'authPrimaryKey' => ' new-primary ', 'authAuxKey' => ' new-aux ',
            ];
            $GLOBALS['saved']['img_url'] = 'legacy';
        }
        if ($case === 'upload-disabled') {
            $_POST += ['disable_upload' => '1', 'no_local_saving' => '1'];
        }
        if ($case === 'image-service-disabled') {
            $_POST += ['img_style' => '1', 'source_img_protect' => '1', 'custom_separator' => '!'];
        }
        $before = $GLOBALS['saved'];
        ob_start();
        try {
            $candidate = $update->invoke($setting);
            $message = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        check($candidate === OSS\WP\Config::$candidate, 'Form must retain candidate fields');
        check(substr_count($message, 'class="notice ') === 1, 'Save and validation must use a single notice');
        check(strpos($message, '<details><summary>') !== false && strpos($message, 'Permission check details') !== false, 'Validation results must be in collapsed details');
        if (!OSS\WP\Config::$valid) {
            check($GLOBALS['saved'] === $before && $GLOBALS['writes'] === 0, 'Invalid settings must not be persisted');
            check(strpos($message, 'The settings have not been saved') !== false, 'Missing validation failure feedback');
        } elseif ($GLOBALS['writeFailure']) {
            check($GLOBALS['saved'] === $before, 'Persistence failure must retain old settings');
            check(strpos($message, 'could not be saved') !== false, 'Persistence failure must not report success');
        } else {
            check($GLOBALS['saved'] === $candidate && $GLOBALS['writes'] === 1, 'Valid settings must be persisted after validation');
            check(strpos($message, 'The settings have been saved') !== false, 'Missing success feedback');
        }
        if (!in_array($case, ['invalid-secret', 'normalized-fields'], true)) {
            check($candidate['sk'] === 'original-secret', 'Empty secret must retain existing secret');
        }
        if ($case === 'normalized-fields') {
            foreach (['sk' => 'new-secret', 'bucket' => 'new-bucket', 'region' => 'oss-cn-shanghai',
                'path' => 'uploads/new', 'static_url' => 'cdn.example.com', 'authMethod' => 'B',
                'authExpTime' => 12, 'authPrimaryKey' => 'new-primary', 'authAuxKey' => 'new-aux',
                'internal' => true, 'vpc' => true, 'nolocalsaving' => true, 'img_service' => true,
                'img_style' => true, 'source_img_protect' => true, 'custom_separator' => '!',
                'keep_settings' => true, 'urlAuth' => true] as $key => $expected) {
                check($candidate[$key] === $expected, 'Normalization mismatch: ' . $key);
            }
            check(!isset($candidate['img_url']), 'Legacy image URL must be removed');
        }
        if ($case === 'upload-disabled') {
            check($candidate['disable_upload'] && !$candidate['nolocalsaving'], 'Disabled upload must prevent local cleanup');
        }
        if ($case === 'image-service-disabled') {
            check(!$candidate['img_style'] && !$candidate['source_img_protect'] && $candidate['custom_separator'] === '', 'Disabled image service must reset dependent options');
        }
        if ($case === 'cleanup-failure') {
            check(strpos(substr($message, 0, strpos($message, '<details>')), OSS\WP\Config::$warning) !== false, 'Cleanup path must be visible without expanding details');
        }
    }
    echo "PASS: 9 cases covering save ordering, normalization, option dependencies and visible cleanup warnings.\n";
}
