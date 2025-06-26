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

### Laravel

1. **Publish the configuration file** (optional):

   ```bash
   php artisan vendor:publish --provider="dmytrof\DuiBucketSDK\Laravel\DuiBucketServiceProvider" --tag=dui-bucket-config
   ```

2. **Configuration file**: `config/dui-bucket.php`

   ```php
   <?php
   return [
       'domain' => env('DUI_BUCKET_DOMAIN', ''),

       // --- API Configuration ---
       'api_url' => env('DUI_BUCKET_ENDPOINT', ''),
       'api_key' => env('DUI_BUCKET_API_KEY', ''),
       'default_bucket' => env('DUI_BUCKET_DEFAULT_BUCKET', 'public'),

       // --- API Key Provider Configuration ---
       'api_key_provider' => env('DUI_BUCKET_API_KEY_PROVIDER', 'env'), // 'env' or 'database'
       'database_client_name' => env('DUI_BUCKET_DB_CLIENT_NAME'),
       'database_model_class' => env('DUI_BUCKET_DB_MODEL_CLASS', 'App\Models\ApiKeyClient'),

       // --- Encryption ---
       'cookie_secret_key' => env('DUI_BUCKET_COOKIE_SECRET_KEY'),
       'cookie_iv_secret' => env('DUI_BUCKET_COOKIE_IV_SECRET'),

       // --- Logging & Debugging ---
       'log_enabled' => env('DUI_BUCKET_LOG_ENABLED', false),
       'log_channel' => env('DUI_BUCKET_LOG_CHANNEL', 'dui_bucket'),

       // --- Misc ---
       'encryption' => env('DUI_BUCKET_ENCRYPTION', false),
       'disable_ssl_verify' => env('DUI_BUCKET_DISABLE_SSL_VERIFY', false),
       'environment' => env('DUI_BUCKET_DEFAULT_ENVIRONMENT', ''),
       'service' => env('DUI_BUCKET_DEFAULT_SERVICE', ''),
   ];
   ```

3. **Environment variables**: Add to your `.env`

   #### Using ENV Provider (default):
   ```dotenv
   DUI_BUCKET_ENDPOINT=https://api.my-files.local
   DUI_BUCKET_API_KEY=your_service_token_here
   DUI_BUCKET_DEFAULT_BUCKET=public-files
   DUI_BUCKET_API_KEY_PROVIDER=env
   ```

   #### Using Database Provider:
   ```dotenv
   DUI_BUCKET_ENDPOINT=https://api.my-files.local
   DUI_BUCKET_DEFAULT_BUCKET=public-files
   DUI_BUCKET_API_KEY_PROVIDER=database
   DUI_BUCKET_DB_CLIENT_NAME=Y22
   DUI_BUCKET_DB_MODEL_CLASS=App\Models\ApiKeyClient
   ```

### Yii2

1. **Component configuration**: In your `config/web.php` or `config/main.php`

   #### Using ENV Provider (default):
   ```php
   'components' => [
       'duiBucket' => [
           'class' => 'dmytrof\DuiBucketSDK\Yii2\DuiBucketComponent',
           'apiUrl' => 'https://api.example.com',
           'apiKey' => 'your_api_key_here',
           'defaultBucket' => 'public',
           'apiKeyProvider' => 'env', // default
           // other config...
       ],
   ]
   ```

   #### Using Database Provider:
   ```php
   'components' => [
       'duiBucket' => [
           'class' => 'dmytrof\DuiBucketSDK\Yii2\DuiBucketComponent',
           'apiUrl' => 'https://api.example.com',
           'defaultBucket' => 'public',
           'apiKeyProvider' => 'database',
           'databaseClientName' => 'Y22',
           'databaseModelClass' => 'models\ApiKeyClient',
           // other config...
       ],
   ]
   ```

2. **Environment variables**: Add to your `.env` or environment configuration

   #### Using ENV Provider:
   ```dotenv
   DUI_BUCKET_ENDPOINT=https://api.my-files.local
   DUI_BUCKET_API_KEY=your_service_token_here
   DUI_BUCKET_DEFAULT_BUCKET=public-files
   DUI_BUCKET_API_KEY_PROVIDER=env
   ```

   #### Using Database Provider:
   ```dotenv
   DUI_BUCKET_ENDPOINT=https://api.my-files.local
   DUI_BUCKET_DEFAULT_BUCKET=public-files
   DUI_BUCKET_API_KEY_PROVIDER=database
   DUI_BUCKET_DB_CLIENT_NAME=Y22
   DUI_BUCKET_DB_MODEL_CLASS=models\ApiKeyClient
   ```

---

## Usage

### Laravel

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

### Yii2

```php
class FileController extends Controller
{
    public function actionUpload()
    {
        $bucketClient = Yii::$app->duiBucket->getClient();

        // Upload a file with encryption and custom bucket
        $response = $bucketClient->upload(
            bucket: 'invoices',
            filePath: '/path/to/invoices.pdf',
            options: [
                'encrypt'      => true,
                'visibility'   => 'private', // 'public' or 'private'
                'metadata'     => ['origin' => 'web'],
            ]
        );

        return $this->asJson($response);
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

## API Key Providers

The SDK supports two built-in API key providers:

### 1. Environment Provider (default)
Retrieves the API key from environment variables. This is the simplest approach for applications with static API keys.

**Configuration**: Set `DUI_BUCKET_API_KEY_PROVIDER=env` and provide `DUI_BUCKET_API_KEY`.

### 2. Database Provider
Retrieves the API key from a database by client name. This is useful for multi-tenant applications or when you need dynamic API key management.

**Configuration**: Set `DUI_BUCKET_API_KEY_PROVIDER=database` and provide:
- `DUI_BUCKET_DB_CLIENT_NAME` - The name of the API client to look up
- `DUI_BUCKET_DB_MODEL_CLASS` - The model class to use (optional)

**Required Model Structure**: Your model should have:
- `name` field for client identification
- `api_key` field containing the actual API key
- `status` field with `STATUS_ACTIVE` constant
- `STATUS_ACTIVE` constant defined in the model

#### Laravel Example Model:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKeyClient extends Model
{
    const STATUS_ACTIVE = 1;

    protected $fillable = ['name', 'api_key', 'status'];
}
```

#### Yii2 Example Model:
```php
<?php

namespace models;

use yii\db\ActiveRecord;

class ApiKeyClient extends ActiveRecord
{
    const STATUS_ACTIVE = 1;

    public static function tableName()
    {
        return 'api_key_clients';
    }
}
```

### Custom API Key Providers

You can create custom API key providers by implementing the `ApiKeyProviderInterface`:

#### Laravel Custom Implementation

```php
use dmytrof\DuiBucketSDK\ApiKey\ApiKeyProviderInterface;
use App\Models\ApiKey;

class CustomApiKeyProvider implements ApiKeyProviderInterface
{
    public function getApiKey(): string
    {
        // Your custom logic here
        $apiKey = ApiKey::where('service', 'bucket')->first();

        if (!$apiKey) {
            throw new \RuntimeException('API key not found');
        }

        return $apiKey->key;
    }
}

// In a service provider:
$this->app->singleton(ApiKeyProviderInterface::class, function () {
    return new CustomApiKeyProvider();
});
```

#### Yii2 Custom Implementation

```php
'components' => [
    'duiBucket' => [
        'class' => 'dmytrof\DuiBucketSDK\Yii2\DuiBucketComponent',
        'apiUrl' => 'https://api.example.com',
        'customApiKeyProvider' => [
            'class' => 'path\to\your\CustomApiKeyProvider',
        ],
        // other config...
    ],
]
```

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
