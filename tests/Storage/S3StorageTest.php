<?php

namespace TuskTests\Storage;

use Aws\S3\S3Client;
use PHPUnit\Framework\TestCase;
use Tusk\FileNotFound;
use Tusk\Storage\S3Storage;

final class S3StorageTest extends TestCase
{
    /** @var S3Client */
    private $s3;

    /** @var string */
    private $bucket;

    /** @var string */
    private $keyPrefix;

    /** @var S3Storage */
    private $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $s3Settings = [
            'version' => '2006-03-01',
            'region' => getenv('S3_REGION'),
            'credentials' => [
                'key' => getenv('S3_AUTH_KEY'),
                'secret' => getenv('S3_AUTH_SECRET'),
            ],
        ];

        if (getenv('S3_ENDPOINT') !== false) {
            $s3Settings['endpoint'] = getenv('S3_ENDPOINT');
            $s3Settings['use_path_style_endpoint'] = true;
        }

        $this->s3 = new S3Client($s3Settings);
        $this->bucket = getenv('S3_BUCKET');
        $this->keyPrefix = 'the-prefix/';
        $this->storage = new S3Storage($this->s3, $this->bucket, $this->keyPrefix);

        $this->s3->createBucket(['Bucket' => $this->bucket]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->s3->deleteMatchingObjects($this->bucket, '', '/.*/');
        $this->s3->deleteBucket(['Bucket' => $this->bucket]);
    }

    public function testCreateWithoutMetadata(): void
    {
        $this->storage->create('foo', 5, null);

        $listMultipartUploadsResult = $this->s3->listMultipartUploads([
            'Bucket' => $this->bucket,
            'Prefix' => $this->keyPrefix . 'foo',
        ]);

        $uploads = $listMultipartUploadsResult['Uploads'];

        $this->assertCount(1, $uploads);
        $this->assertSame($this->keyPrefix . 'foo', $uploads[0]['Key']);
        $this->assertSame('STANDARD', $uploads[0]['StorageClass']);

        $uploadId = $uploads[0]['UploadId'];

        $headMetaResult = $this->s3->headObject([
            'Bucket' => $this->bucket,
            'Key' => $this->keyPrefix . 'foo.meta',
        ]);

        $this->assertSame([
            'length' => '5',
            'metadata' => '',
            'upload-id' => $uploadId,
        ], $headMetaResult['Metadata']);
    }

    public function testCreateWithMetadata(): void
    {
        $this->storage->create('foo', 5, 'foo YmFy');

        $headMetaResult = $this->s3->headObject([
            'Bucket' => $this->bucket,
            'Key' => $this->keyPrefix . 'foo.meta',
        ]);

        $this->assertSame('foo YmFy', $headMetaResult['Metadata']['metadata']);
    }

    public function testDeleteNotExists(): void
    {
        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('File with ID foo was not found');

        $this->storage->delete('foo');
    }

    public function testDelete(): void
    {
        $this->storage->create('foo', 5, null);

        $this->storage->delete('foo');

        $listMultipartUploadsResult = $this->s3->listMultipartUploads([
            'Bucket' => $this->bucket,
            'Prefix' => $this->keyPrefix . 'foo',
        ]);

        $uploads = $listMultipartUploadsResult['Uploads'];

        $this->assertEmpty($uploads);

        $this->assertFalse($this->s3->doesObjectExist($this->bucket, $this->keyPrefix . 'foo.meta'));
    }
}
