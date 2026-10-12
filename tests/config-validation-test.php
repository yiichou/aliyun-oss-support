<?php

namespace OSS\WP {
    class OssClientAdapter
    {
        public static $calls = [];
        public static $initializationError = false;
        public static $listingError = false;
        public static $writeError = false;
        public static $uploadStatus = 'passed';
        public static $deleteStatus = 'passed';

        public function __construct($key, $secret, $endpoint, $region)
        {
            self::$calls[] = ['construct', $key, $secret, $endpoint, $region];
            if (self::$initializationError) {
                throw new \RuntimeException('Initialization failed');
            }
        }

        public function validateBucketAccess($bucket, $prefix)
        {
            self::$calls[] = ['list', $bucket, $prefix];
            if (self::$listingError) {
                throw new \RuntimeException('Listing denied');
            }
        }

        public function verifyWritePermissions($bucket)
        {
            self::$calls[] = ['write', $bucket];
            if (self::$writeError) {
                throw new \RuntimeException('Write verification interrupted');
            }
            return [
                'object' => 'test-object.txt',
                'upload' => ['status' => self::$uploadStatus, 'message' => self::$uploadStatus === 'failed' ? 'Upload denied' : ''],
                'delete' => ['status' => self::$deleteStatus, 'message' => self::$deleteStatus === 'failed' ? 'Delete denied' : ''],
            ];
        }
    }
}

namespace {
    require dirname(__DIR__) . '/src/Config.php';

    function __($text, $domain = null) { return $text; }
    function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
    function check($condition, $message)
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    $cases = [
        'success' => [],
        'root-prefix' => ['path' => '/'],
        'missing-prefix' => ['path' => null],
        'internal' => ['internal' => true],
        'initialization-failure' => [],
        'listing-failure' => [],
        'write-exception' => [],
        'listing-and-write-exception' => [],
        'upload-failure' => [],
        'delete-failure' => [],
        'both-write-failures' => [],
    ];
    foreach ($cases as $case => $changes) {
        $options = array_merge([
            'ak' => 'test-key', 'sk' => 'test-secret', 'bucket' => 'test-bucket',
            'region' => 'oss-cn-shanghai', 'internal' => false, 'path' => '/uploads/',
        ], $changes);
        if ($case === 'missing-prefix') {
            unset($options['path']);
        }
        OSS\WP\OssClientAdapter::$calls = [];
        OSS\WP\OssClientAdapter::$initializationError = $case === 'initialization-failure';
        OSS\WP\OssClientAdapter::$listingError = in_array($case, ['listing-failure', 'listing-and-write-exception'], true);
        OSS\WP\OssClientAdapter::$writeError = in_array($case, ['write-exception', 'listing-and-write-exception'], true);
        OSS\WP\OssClientAdapter::$uploadStatus = in_array($case, ['upload-failure', 'both-write-failures'], true) ? 'failed' : 'passed';
        OSS\WP\OssClientAdapter::$deleteStatus = $case === 'upload-failure' ? 'skipped'
            : (in_array($case, ['delete-failure', 'both-write-failures'], true) ? 'failed' : 'passed');
        $report = ['details' => 'stale details', 'warning' => 'stale warning'];
        ob_start();
        try {
            $success = OSS\WP\Config::checkOssConfig($options, $report);
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $expectedSuccess = in_array($case, ['success', 'root-prefix', 'missing-prefix', 'internal'], true);
        check($success === $expectedSuccess, 'Wrong result for ' . $case);
        check($output === '', 'Validation must return data, not render a second notice');
        $calls = OSS\WP\OssClientAdapter::$calls;
        $endpoint = $case === 'internal' ? 'oss-cn-shanghai-internal.aliyuncs.com' : 'oss-cn-shanghai.aliyuncs.com';
        check($calls[0] === ['construct', 'test-key', 'test-secret', $endpoint, 'cn-shanghai'], 'Endpoint or signing region mismatch');
        if ($case === 'initialization-failure') {
            check(count($calls) === 1, 'Initialization failure must not send requests');
            check(strpos($report['details'], 'Initialization failed') !== false, 'Initialization error missing');
        } else {
            $prefix = in_array($case, ['root-prefix', 'missing-prefix'], true) ? '' : 'uploads/';
            check($calls[1] === ['list', 'test-bucket', $prefix], 'Storage prefix mismatch');
            check($calls[2] === ['write', 'test-bucket'], 'Write verification must run even when listing fails');
            if (OSS\WP\OssClientAdapter::$listingError) {
                check(strpos($report['details'], 'Listing denied') !== false, 'Listing error lost');
            }
            if (OSS\WP\OssClientAdapter::$writeError) {
                check(strpos($report['details'], 'Write verification interrupted') !== false, 'Write exception lost');
            } else {
                check(strpos($report['details'], 'List objects:') !== false, 'Listing summary missing');
                check(strpos($report['details'], 'Upload test object:') !== false, 'Upload summary missing');
                check(strpos($report['details'], 'Delete test object:') !== false, 'Delete summary missing');
                if ($case === 'upload-failure') {
                    check(strpos($report['details'], 'Not verified: upload failed') !== false, 'Cleanup success must not verify deletion');
                }
            }
        }
        $cleanupFailure = in_array($case, ['delete-failure', 'both-write-failures'], true);
        check(($report['warning'] !== '') === $cleanupFailure, 'Incorrect residual object warning');
        if ($cleanupFailure) {
            check(strpos($report['warning'], 'oss://test-bucket/test-object.txt') !== false, 'Exact cleanup path missing');
        }
    }
    echo "PASS: 11 cases covering validation orchestration, report shape, prefixes, regions and cleanup warnings.\n";
}
