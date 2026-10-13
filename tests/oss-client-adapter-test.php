<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/OssClientAdapter.php';
require dirname(__DIR__) . '/src/Config.php';

if (!function_exists('__')) {
    function __($text, $domain = null) { return $text; }
}
if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}

use AlibabaCloud\Oss\V2\Exception\OperationException;
use AlibabaCloud\Oss\V2\Exception\ServiceException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use OSS\WP\OssClientAdapter;

function check($condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function mockAdapter(array $responses, array &$history, bool $internal = false): OssClientAdapter
{
    $endpoint = $internal ? 'oss-cn-shanghai-internal.aliyuncs.com' : 'oss-cn-shanghai.aliyuncs.com';
    $adapter = new OssClientAdapter('test-access-key', 'test-secret', $endpoint, 'cn-shanghai');
    $property = new ReflectionProperty($adapter, 'client');
    $property->setAccessible(true);
    $client = $property->getValue($adapter);
    $property = new ReflectionProperty($client, 'client');
    $property->setAccessible(true);
    $implementation = $property->getValue($client);
    $property = new ReflectionProperty($implementation, 'sdkOptions');
    $property->setAccessible(true);
    $options = $property->getValue($implementation);
    $options['retry_max_attempts'] = 1;
    $property->setValue($implementation, $options);
    $property = new ReflectionProperty($implementation, 'httpClient');
    $property->setAccessible(true);
    $stack = $property->getValue($implementation)->getConfig('handler');
    $stack->setHandler(new MockHandler($responses));
    $stack->push(Middleware::history($history));
    return $adapter;
}

function expectServiceError(callable $operation, string $code): void
{
    try {
        $operation();
    } catch (OperationException $e) {
        $cause = $e->getPrevious();
        check($cause instanceof ServiceException && $cause->getErrorCode() === $code, 'Wrong service error');
        return;
    }
    throw new RuntimeException('Expected service error was suppressed');
}

$listXml = '<ListBucketResult><Name>test-bucket</Name><Prefix></Prefix><Marker></Marker><MaxKeys>1</MaxKeys><IsTruncated>false</IsTruncated></ListBucketResult>';
$accessDenied = '<Error><Code>AccessDenied</Code><Message>Denied</Message><RequestId>test-request</RequestId></Error>';
$oldId = getenv('OSS_ACCESS_KEY_ID');
$oldSecret = getenv('OSS_ACCESS_KEY_SECRET');

try {
    foreach (['', 'uploads/'] as $prefix) {
        $history = [];
        $adapter = mockAdapter([new Response(200, [], $listXml)], $history);
        $adapter->validateBucketAccess('test-bucket', $prefix);
        check(count($history) === 1, 'Validation must make exactly one request');
        $request = $history[0]['request'];
        parse_str($request->getUri()->getQuery(), $query);
        check($request->getMethod() === 'GET', 'Validation must be read-only');
        check(($query['max-keys'] ?? null) === '1', 'Validation must request at most one object');
        check(($query['prefix'] ?? '') === $prefix, 'Validation prefix mismatch');
        check(!isset($query['bucketInfo']), 'Validation must not call GetBucketInfo');
        check(strpos($request->getHeaderLine('Authorization'), '/cn-shanghai/oss/') !== false, 'Wrong signing region');
    }

    $history = [];
    $adapter = mockAdapter([new Response(200, [], $listXml)], $history, true);
    $adapter->validateBucketAccess('test-bucket');
    check($history[0]['request']->getUri()->getHost() === 'test-bucket.oss-cn-shanghai-internal.aliyuncs.com', 'Internal endpoint mismatch');

    $history = [];
    $adapter = mockAdapter([new Response(403, [], $accessDenied)], $history);
    expectServiceError(function () use ($adapter) {
        $adapter->validateBucketAccess('test-bucket');
    }, 'AccessDenied');

    $history = [];
    $adapter = mockAdapter([new Response(404, [], '<Error><Code>NoSuchBucket</Code><Message>Missing bucket</Message></Error>')], $history);
    expectServiceError(function () use ($adapter) {
        $adapter->validateBucketAccess('test-bucket');
    }, 'NoSuchBucket');

    $history = [];
    $adapter = mockAdapter([new Response(200, ['Content-Length' => '0'])], $history);
    check($adapter->doesObjectExist('test-bucket', 'uploads/photo.jpg'), 'Existing object not detected');
    $request = $history[0]['request'];
    check($request->getMethod() === 'HEAD', 'Object check must not download content');
    check($request->getUri()->getPath() === '/uploads/photo.jpg', 'Object key mismatch');
    check(strpos($request->getUri()->getQuery(), 'objectMeta') !== false, 'Object check must use GetObjectMeta');

    $history = [];
    $adapter = mockAdapter([new Response(404)], $history);
    check(!$adapter->doesObjectExist('test-bucket', 'missing.jpg'), 'Missing object not detected');

    $history = [];
    $adapter = mockAdapter([new Response(403, [], $accessDenied)], $history);
    expectServiceError(function () use ($adapter) {
        $adapter->doesObjectExist('test-bucket', 'forbidden.jpg');
    }, 'AccessDenied');

    $history = [];
    $adapter = mockAdapter([new ConnectException('Test network failure', new Request('HEAD', 'https://example.invalid'))], $history);
    try {
        $adapter->doesObjectExist('test-bucket', 'photo.jpg');
        throw new RuntimeException('Network failure was suppressed');
    } catch (OperationException $e) {
        check(strpos($e->getMessage(), 'Test network failure') !== false, 'Network error detail was lost');
    }

    $history = [];
    $adapter = mockAdapter([new Response(200), new Response(204), new Response(200), new Response(204)], $history);
    $first = $adapter->verifyWritePermissions('test-bucket');
    $second = $adapter->verifyWritePermissions('test-bucket');
    check($first['upload']['status'] === 'passed' && $first['delete']['status'] === 'passed', 'Write permission checks failed');
    check(preg_match('/^aliyun-oss-permission-test-\d{8}-\d{6}-[a-f0-9]{32}\.txt$/', $first['object']) === 1, 'Test object must have a timestamp and random root key');
    check($first['object'] !== $second['object'], 'Repeated checks must use different keys');
    check(count($history) === 4, 'Each write check must upload and delete exactly once');
    foreach ([0, 2] as $index) {
        $upload = $history[$index]['request'];
        $delete = $history[$index + 1]['request'];
        check($upload->getMethod() === 'PUT' && $delete->getMethod() === 'DELETE', 'Wrong write verification operations');
        check($upload->getUri()->getPath() === $delete->getUri()->getPath(), 'Cleanup must target only its own test object');
        check(substr_count($upload->getUri()->getPath(), '/') === 1, 'Test object must be at bucket root');
        check((string) $upload->getBody() === 'Aliyun OSS permission verification.', 'Unexpected test object contents');
    }

    $history = [];
    $adapter = mockAdapter([new Response(200), new Response(403, [], $accessDenied)], $history);
    $results = $adapter->verifyWritePermissions('test-bucket');
    check($results['upload']['status'] === 'passed' && $results['delete']['status'] === 'failed', 'Delete denial must be reported separately');
    check(strpos($results['delete']['message'], 'AccessDenied') !== false, 'Delete error detail was lost');
    check($history[1]['request']->getUri()->getPath() === '/' . $results['object'], 'Residual object key mismatch');

    $history = [];
    $adapter = mockAdapter([new Response(403, [], $accessDenied), new Response(204)], $history);
    $results = $adapter->verifyWritePermissions('test-bucket');
    check($results['upload']['status'] === 'failed' && $results['delete']['status'] === 'skipped', 'Deleting a possibly absent object must not claim verified permission');
    check(count($history) === 2, 'Cleanup must still run after upload failure');

    $history = [];
    $adapter = mockAdapter([new Response(403, [], $accessDenied), new Response(403, [], $accessDenied)], $history);
    $results = $adapter->verifyWritePermissions('test-bucket');
    check($results['upload']['status'] === 'failed' && $results['delete']['status'] === 'failed', 'Both permission failures must be reported');

    $history = [];
    $adapter = mockAdapter([
        new ConnectException('Upload response lost', new Request('PUT', 'https://example.invalid')),
        new Response(204),
    ], $history);
    $results = $adapter->verifyWritePermissions('test-bucket');
    check($results['upload']['status'] === 'failed' && $results['delete']['status'] === 'skipped', 'Ambiguous upload must not claim permission success');
    check(count($history) === 2, 'Lost upload response must trigger cleanup');
    check(strpos($results['upload']['message'], 'Upload response lost') !== false, 'Upload network error was lost');

    $render = new ReflectionMethod(OSS\WP\Config::class, 'renderValidationNotice');
    $render->setAccessible(true);
    foreach ([true, false] as $success) {
        ob_start();
        try {
            $render->invoke(null, $success, 'Permission summary', $success ? '' : '<script>error trace</script>');
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        check(strpos($html, 'is-dismissible') !== false, 'Validation notice must be dismissible');
        check(strpos($html, $success ? 'notice-success' : 'notice-error') !== false, 'Wrong validation severity');
        if (!$success) {
            check(strpos($html, '<details><summary>') !== false, 'Error details must be collapsed by default');
            check(strpos($html, '<script>') === false && strpos($html, '&lt;script&gt;') !== false, 'Trace HTML must be escaped');
        } else {
            check(strpos($html, '<details>') === false, 'Successful notice must not have empty details');
        }
    }

    $file = tempnam(sys_get_temp_dir(), 'oss-unit-');
    check($file !== false, 'Could not create test fixture');
    try {
        check(file_put_contents($file, 'Attachment fixture') !== false, 'Could not write test fixture');
        foreach ([[], ['Cache-Control' => 'max-age=2592000']] as $headers) {
            $history = [];
            $adapter = mockAdapter([new Response(200)], $history);
            $adapter->multiuploadFile('test-bucket', 'uploads/photo.txt', $file, $headers);
            check(count($history) === 1, 'Attachment upload must use one request, not multipart upload');
            $request = $history[0]['request'];
            check($request->getMethod() === 'PUT' && $request->getUri()->getPath() === '/uploads/photo.txt', 'Attachment upload target mismatch');
            check((string) $request->getBody() === 'Attachment fixture', 'File contents not uploaded');
            check($request->getUri()->getQuery() === '', 'Upload must not invoke ACL or tagging APIs');
            if ($headers) {
                check($request->getHeaderLine('x-oss-meta-Cache-Control') === 'max-age=2592000', 'Existing metadata behavior must be preserved');
            }
        }
    } finally {
        check(unlink($file), 'Could not clean up test fixture');
    }

    $history = [];
    $adapter = mockAdapter([new Response(204)], $history);
    $adapter->deleteObject('test-bucket', 'uploads/photo.txt');
    check(count($history) === 1 && $history[0]['request']->getMethod() === 'DELETE', 'Attachment deletion must use DeleteObject');
    check($history[0]['request']->getUri()->getPath() === '/uploads/photo.txt', 'Deletion target mismatch');
    check($history[0]['request']->getUri()->getQuery() === '', 'Deletion must not target a historical version');

    $history = [];
    $adapter = mockAdapter([new Response(403, [], $accessDenied)], $history);
    expectServiceError(function () use ($adapter) {
        $adapter->deleteObject('test-bucket', 'uploads/photo.txt');
    }, 'AccessDenied');

    echo "PASS: 20 cases covering OSS operations, attachment upload/delete and collapsed validation notices.\n";
} finally {
    putenv($oldId === false ? 'OSS_ACCESS_KEY_ID' : 'OSS_ACCESS_KEY_ID=' . $oldId);
    putenv($oldSecret === false ? 'OSS_ACCESS_KEY_SECRET' : 'OSS_ACCESS_KEY_SECRET=' . $oldSecret);
}
