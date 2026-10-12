<?php

namespace OSS\WP;

use AlibabaCloud\Oss\V2 as Oss;
use AlibabaCloud\Oss\V2\Config as OssConfig;
use AlibabaCloud\Oss\V2\Credentials\EnvironmentVariableCredentialsProvider;
use AlibabaCloud\Oss\V2\Models\ListObjectsRequest;
use AlibabaCloud\Oss\V2\Models\PutObjectRequest;
use AlibabaCloud\Oss\V2\Models\DeleteObjectRequest;
use Exception;

class OssClientAdapter
{
    private $client;

    public function __construct(string $accessKeyId, string $accessKeySecret, string $endpoint, string $region)
    {
        $config = OssConfig::loadDefault();

        if ($accessKeyId !== '') {
            putenv('OSS_ACCESS_KEY_ID=' . $accessKeyId);
        }
        if ($accessKeySecret !== '') {
            putenv('OSS_ACCESS_KEY_SECRET=' . $accessKeySecret);
        }
        $credentialsProvider = new EnvironmentVariableCredentialsProvider();
        $config->setCredentialsProvider($credentialsProvider);

        $config->setRegion($region);
        $config->setEndpoint($endpoint);

        $this->client = new Oss\Client($config);
    }

    public function validateBucketAccess(string $bucket, string $prefix = ''): void
    {
        $request = new ListObjectsRequest($bucket);
        $request->maxKeys = 1;
        $request->prefix = $prefix;
        $this->client->listObjects($request);
    }

    public function doesObjectExist(string $bucket, string $object): bool
    {
        return $this->client->isObjectExist($bucket, $object);
    }

    public function verifyWritePermissions(string $bucket): array
    {
        $object = 'aliyun-oss-permission-test-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(16)) . '.txt';
        $results = ['object' => $object];
        try {
            $request = new PutObjectRequest($bucket, $object);
            $request->body = Oss\Utils::streamFor('Aliyun OSS permission verification.');
            $this->client->putObject($request);
            $results['upload'] = ['status' => 'passed', 'message' => ''];
        } catch (Exception $e) {
            $results['upload'] = ['status' => 'failed', 'message' => $e->getMessage()];
        }

        // Also clean up when an upload may have succeeded but its response was lost.
        try {
            $this->deleteObject($bucket, $object);
            $results['delete'] = [
                'status' => $results['upload']['status'] === 'passed' ? 'passed' : 'skipped',
                'message' => '',
            ];
        } catch (Exception $e) {
            $results['delete'] = ['status' => 'failed', 'message' => $e->getMessage()];
        }
        return $results;
    }

    public function multiuploadFile(string $bucket, string $object, string $filePath, array $options = []): void
    {
        $stream = Oss\Utils::streamFor(fopen($filePath, 'rb'));
        $request = new PutObjectRequest($bucket, $object);
        $request->body = $stream;

        if (isset($options['Cache-Control'])) {
            if (!is_array($request->metadata)) {
                $request->metadata = [];
            }
            $request->metadata['Cache-Control'] = $options['Cache-Control'];
        }

        $this->client->putObject($request);
    }

    public function deleteObject(string $bucket, string $object): void
    {
        $this->client->deleteObject(new DeleteObjectRequest($bucket, $object));
    }
}