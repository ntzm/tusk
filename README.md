# Tusk

## PSR-7 compatible [tus](https://tus.io/) server for PHP

## Available storage drivers

### Filesystem

Stores files in a given directory

```php
$storage = new Tusk\Storage\FilesystemStorage('/tmp/tusk');
```

### S3

Stores files in S3

```php
$storage = new Tusk\Storage\S3Storage(new Aws\S3\S3Client([
    'version' => '2006-03-01',
    'region' => 'eu-west-1',
]), 'bucket-name');
```

See the [documentation for PHP AWS SDK](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_configuration.html) for more information.

## Events

Tusk emits events when certain things happen during a file's lifecycle.
You can hook into these with any [PSR-14](https://www.php-fig.org/psr/psr-14/) compatible event dispatcher implementation.

### `UploadComplete`

`Tusk\Event\UploadComplete` is fired when a file has been uploaded completely.
It has one method, `fileId()` which can be called to get the file's ID.

## Compatibility

Tusk is compatible with tus 1.0.0 and the following extensions:

- [Creation](https://tus.io/protocols/resumable-upload.html#creation)
- [Termination](https://tus.io/protocols/resumable-upload.html#termination)
