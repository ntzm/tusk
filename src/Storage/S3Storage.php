<?php

namespace Tusk\Storage;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Tusk\FileNotFound;
use Webmozart\Assert\Assert;

final class S3Storage implements Storage
{
    /** @var S3Client */
    private $s3;

    /** @var string */
    private $bucket;

    /** @var string */
    private $prefix;

    public function __construct(S3Client $s3, string $bucket, string $prefix = '')
    {
        $this->s3 = $s3;
        $this->bucket = $bucket;
        $this->prefix = $prefix;
    }

    public function getOffset(string $id): int
    {
        try {
            $uploadId = $this->getObjectMetadata($id)['upload-id'];

            $offset = array_sum(array_column($this->s3->listParts([
                'Bucket' => $this->bucket,
                'Key' => $this->prefix . $id,
                'UploadId' => $uploadId,
            ])->toArray()['Parts'] ?? [], 'Size'));

            Assert::integer($offset);

            return $offset;
        } catch (S3Exception $e) {
            if ($e->getStatusCode() === 404) {
                try {
                    return $this->s3->headObject([
                        'Bucket' => $this->bucket,
                        'Key' => $this->prefix . $id,
                    ])->toArray()['ContentLength'];
                } catch (S3Exception $e) {
                    if ($e->getStatusCode() === 404) {
                        throw new FileNotFound();
                    }

                    throw $e;
                }
            }

            throw $e;
        }
    }

    public function append(string $id, $data): void
    {
        try {
            $uploadId = $this->getObjectMetadata($id)['upload-id'];

            $partCount = count($this->s3->listParts([
                'Bucket' => $this->bucket,
                'Key' => $this->prefix . $id,
                'UploadId' => $uploadId,
            ])->toArray()['Parts'] ?? []);

            $this->s3->uploadPart([
                'Bucket' => $this->bucket,
                'Key' => $this->prefix . $id,
                'UploadId' => $uploadId,
                'PartNumber' => $partCount + 1,
                'Body' => $data,
            ]);
        } catch (S3Exception $e) {
            if ($e->getStatusCode() === 404) {
                throw new FileNotFound();
            }

            throw $e;
        }
    }

    public function complete(string $id): void
    {
        try {
            $uploadId = $this->getObjectMetadata($id)['upload-id'];

            $partsResult = $this->s3->listParts([
                'Bucket' => $this->bucket,
                'Key' => $this->prefix . $id,
                'UploadId' => $uploadId,
            ]);

            $this->s3->completeMultipartUpload([
                'Bucket' => $this->bucket,
                'Key' => $this->prefix . $id,
                'UploadId' => $uploadId,
                'MultipartUpload' => [
                    'Parts' => $partsResult['Parts'],
                ],
            ]);

            $this->s3->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $this->prefix . $id . '.meta',
            ]);
        } catch (S3Exception $e) {
            if ($e->getStatusCode() === 404) {
                throw new FileNotFound();
            }

            throw $e;
        }
    }

    public function create(string $id, int $length, ?string $metadata): void
    {
        $result = $this->s3->createMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $this->prefix . $id,
        ]);

        $this->s3->putObject([
            'Bucket' => $this->bucket,
            'Key' => $this->prefix . $id . '.meta',
            'Metadata' => [
                'length' => $length,
                'metadata' => $metadata ?? '',
                'upload-id' => $result['UploadId'],
            ],
        ]);
    }

    public function delete(string $id): void
    {
        try {
            $uploadId = $this->getObjectMetadata($id)['upload-id'];

            $this->s3->abortMultipartUpload([
                'Bucket' => $this->bucket,
                'Key' => $this->prefix . $id,
                'UploadId' => $uploadId,
            ]);

            $this->s3->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $this->prefix . $id . '.meta',
            ]);
        } catch (S3Exception $e) {
            if ($e->getStatusCode() === 404) {
                throw new FileNotFound();
            }

            throw $e;
        }
    }

    public function getLength(string $id): int
    {
        return $this->getObjectMetadata($id)['length'];
    }

    public function getMetaData(string $id): ?string
    {
        return $this->getObjectMetadata($id)['metadata'];
    }

    /**
     * @throws FileNotFound
     *
     * @return array{length: int, metadata: string, upload-id: string}
     */
    private function getObjectMetadata(string $id): array
    {
        try {
            /** @var array{length: int, metadata: string, upload-id: string} $metadata */
            $metadata = $this->s3->headObject([
                'Bucket' => $this->bucket,
                'Key' => $this->prefix . $id . '.meta',
            ])['Metadata'];

            return $metadata;
        } catch (S3Exception $e) {
            if ($e->getStatusCode() === 404) {
                throw new FileNotFound();
            }

            throw $e;
        }
    }
}
