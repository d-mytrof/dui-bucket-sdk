# DuiBucketSDK

**PHP SDK for interacting with the File Bucket microservice**

DuiBucketSDK provides a simple, secure, and flexible interface for uploading, managing, and retrieving files in a bucket-based storage microservice. It supports AES-256-CBC encryption, bucket-level access control (public/private), and presigned URL generation.

---

## Installation

Install via Composer:

```bash
composer require d-mytrof/dui-bucket-sdk:^1.0
```

> **Minimum PHP version**: 8.0

---

## Configuration

1. **Publish the configuration file** (optional):

   ```bash
   php artisan vendor:publish --provider="dmytrof\DuiBucketSDK\Integration\Laravel\ServiceProvider" --tag=config
   ```

2. **Configuration file**: `config/duibucketsdk.php`

   ```php
   <?php
   return [
       // Base URL of the bucket microservice API
       'endpoint' => env('DUI_BUCKET_ENDPOINT', 'https://api.example.com'),

       // Service token (x-api-key), stored in an HttpOnly cookie by your application
       'api_key'  => env('DUI_BUCKET_API_KEY'),

       // Optional: default bucket settings
       'default_bucket' => env('DUI_BUCKET_DEFAULT_BUCKET', 'uploads'),
   ];
   ```

3. **Environment variables**: Add to your `.env`

   ```dotenv
   DUI_BUCKET_ENDPOINT=https://api.my-files.local
   DUI_BUCKET_API_KEY=your_service_token_here
   DUI_BUCKET_DEFAULT_BUCKET=public-files
   ```

---

## Usage

Below examples assume you have an instance of `\dmytrof\DuiBucketSDK\Http\BucketClient` injected or resolved via the service container.

```php
use dmytrof\DuiBucketSDK\Http\BucketClient;

class FileController extends Controller
{
    public function upload(BucketClient $client)
    {
        // Upload a file with encryption and custom bucket
        $response = $client->upload(
            bucket: 'invoices',
            filePath: storage_path('invoices.pdf'),
            options: [
                'encrypt'      => true,
                'visibility'   => 'private', // 'public' or 'private'
                'metadata'     => ['origin' => 'web'],
            ]
        );

        return response()->json($response);
    }
}
```

---

## API Methods

| Method                                                                        | Description                                                                   |
| ----------------------------------------------------------------------------- | ----------------------------------------------------------------------------- |
| `upload(string $bucket, string $filePath, array $options = []): FileResponse` | Uploads a file. Supports encryption, visibility, bucket auto-creation.        |
| `getInfo(string $fileId): FileInfo`                                           | Retrieves metadata: name, ID, size, MIME type, creation timestamp, owner UID. |
| `delete(string $fileId): bool`                                                | Deletes a file. Validates owner UID or role permissions.                      |
| `list(array $filters = [], int $page = 1, int $perPage = 20): FileCollection` | Returns paginated file list. Filters by type, size, name, created\_at, owner. |
| `generatePresignedUrl(string $fileId, int $ttl = 3600): string`               | Generates a time-limited URL for private buckets (default TTL = 1 hour).      |

> **Note**: All methods throw `\dmytrof\DuiBucketSDK\Exceptions\SdkException` on failure.

---

## Authentication & Roles

* **Service token**: Passed as `x-api-key` in an HttpOnly cookie.
* **User token**: Your application should validate user JWTs and enforce the following roles:

  * `user`: upload, read\_own, delete\_own
  * `manager`: upload, read\_own, read\_group, delete\_own
  * `admin`: upload, read\_all, delete\_all, modify owner/bucket
  * `superadmin`: full\_access (system operations, logs, policies)

Access control is enforced server-side by the microservice based on the token's `uid` and `role`.

---

## Encryption

Files are encrypted on the SDK side (temporary folder) using AES-256-CBC before being moved to the final bucket. Ensure the microservice config allows:

* MIME type validation per bucket
* File size limits per bucket (in MB)
* Custom TTL per bucket for presigned URLs

---

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/xyz`)
3. Commit your changes (`git commit -m 'Add xyz'`)
4. Run tests (`composer test`)
5. Push to the branch (`git push origin feature/xyz`)
6. Open a Pull Request

Please adhere to PSR-12 coding standards. No inline comments—only doc-blocks on methods.

---

## License

This project is licensed under the BSD-3-Clause. See [LICENSE](LICENSE) for details.
