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

## ID Generation

By default, file IDs are created using the `RandomHexIdGenerator`, which should give you 32-character IDs that look like `69ed96b70ab30c8f046e79b74faf481b`.
If you'd like to change how this works, you can inject a custom class that implements `IdGenerator`.

## Location Generation

When a file upload begins, the server returns a URL that the client can continue to send data to.
By default this is whatever the POST URL is, plus the file ID.
This is handled by the `RelativeLocationGenerator`.

For example, if the POST endpoint's URL was `/files`, the file location would be `files/<id>`.

If you'd like to change this, you can inject a custom class that implements `LocationGenerator`.

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
